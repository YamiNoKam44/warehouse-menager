<?php

declare(strict_types=1);

namespace App\Tests\Functional\Stock;

use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Stock\Application\Dto\ReceiptData;
use App\Stock\Application\Dto\ReceiptDocumentUploads;
use App\Stock\Application\Exception\StockReceiptAccessDenied;
use App\Stock\Application\Service\StockReceiptService;
use App\Stock\Domain\Model\StockReceipt;
use App\Stock\Domain\Model\VatRate;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class StockReceiptTest extends WebTestCase
{
    private const string PASSWORD = 'bezpieczne-haslo';
    private const string RECEIPT_URL = '/stock/receipts/create';

    private KernelBrowser $client;
    private User $operator;
    private Warehouse $assignedWarehouse;
    private Warehouse $unassignedWarehouse;
    private Article $article;

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->clearDatabase();
        $this->clearStoredDocuments();

        $this->createUser('admin', UserRole::ADMIN);
        $this->operator = $this->createUser('operator', UserRole::USER);

        $warehouses = self::getContainer()->get(WarehouseRepository::class);
        $this->assignedWarehouse = Warehouse::create('Magazyn główny', [$this->operator]);
        $this->unassignedWarehouse = Warehouse::create('Magazyn obcy', []);
        $warehouses->save($this->assignedWarehouse);
        $warehouses->save($this->unassignedWarehouse);

        $articles = self::getContainer()->get(ArticleRepository::class);
        $this->article = Article::create('Cement', 'kg');
        $articles->save($this->article);
    }

    protected function tearDown(): void
    {
        $this->clearDatabase();
        $this->clearStoredDocuments();

        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        parent::tearDown();
    }

    public function testUserSeesOnlyAssignedWarehousesAndCanReceiveArticle(): void
    {
        $this->logIn('operator');

        $crawler = $this->client->request('GET', self::RECEIPT_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf(
            '#stock_receipt_warehouse option[value="%d"]',
            $this->assignedWarehouse->id(),
        ));
        self::assertSelectorNotExists(sprintf(
            '#stock_receipt_warehouse option[value="%d"]',
            $this->unassignedWarehouse->id(),
        ));
        self::assertSelectorTextContains(
            '#stock_receipt_article',
            'Cement — jednostka: kg',
        );
        self::assertSelectorExists('#stock_receipt_documents[multiple]');
        self::assertSelectorExists(
            '#stock_receipt_documents[accept*=".pdf"][accept*=".xml"]',
        );
        self::assertSelectorExists('#stock_receipt_vatRate option[value="0"]');
        self::assertSelectorExists('#stock_receipt_vatRate option[value="5"]');
        self::assertSelectorExists('#stock_receipt_vatRate option[value="8"]');
        self::assertSelectorExists('#stock_receipt_vatRate option[value="23"]');

        $this->client->request(
            'POST',
            self::RECEIPT_URL,
            [
                'stock_receipt' => [
                    'warehouse' => (string) $this->assignedWarehouse->id(),
                    'article' => (string) $this->article->id(),
                    'quantity' => '2,5',
                    'vatRate' => '23',
                    'unitNetPrice' => '12,50',
                    '_token' => $crawler->filter('input[name="stock_receipt[_token]"]')->attr('value'),
                ],
            ],
            [
                'stock_receipt' => [
                    'documents' => [
                        $this->upload('faktura.pdf', "%PDF-1.4\n% test\n"),
                        $this->upload('faktura.xml', "<?xml version=\"1.0\"?><invoice/>"),
                    ],
                ],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects(self::RECEIPT_URL.'?saved=1');

        $this->client->followRedirect();
        self::assertSelectorTextContains('.notice', 'Przyjęcie towaru zostało zapisane.');

        $receipt = $this->testConnection()->fetchAssociative(
            'SELECT * FROM stock_receipts',
        );
        self::assertIsArray($receipt);
        self::assertSame((string) $this->assignedWarehouse->id(), (string) $receipt['warehouse_id']);
        self::assertSame((string) $this->article->id(), (string) $receipt['article_id']);
        self::assertSame((string) $this->operator->id(), (string) $receipt['received_by_id']);
        self::assertSame('2.500', $receipt['quantity']);
        self::assertSame('kg', $receipt['unit_of_measure']);
        self::assertSame('23', (string) $receipt['vat_rate']);
        self::assertSame('12.50', $receipt['unit_net_price']);

        $documents = $this->testConnection()->fetchAllAssociative(
            'SELECT stored_name, original_name, type FROM stock_receipt_documents ORDER BY id',
        );
        self::assertCount(2, $documents);
        self::assertSame('faktura.pdf', $documents[0]['original_name']);
        self::assertSame('pdf', $documents[0]['type']);
        self::assertSame('faktura.xml', $documents[1]['original_name']);
        self::assertSame('xml', $documents[1]['type']);

        foreach ($documents as $document) {
            self::assertMatchesRegularExpression(
                '/\A[a-f0-9]{32}\.(pdf|xml)\z/',
                $document['stored_name'],
            );
            self::assertFileExists($this->storageDirectory().'/'.$document['stored_name']);
            self::assertFileDoesNotExist(
                self::getContainer()->getParameter('kernel.project_dir')
                .'/public/'.$document['stored_name'],
            );
        }
    }

    public function testFormRejectsZeroQuantity(): void
    {
        $this->logIn('operator');

        $crawler = $this->client->request('GET', self::RECEIPT_URL);

        $this->client->request(
            'POST',
            self::RECEIPT_URL,
            [
                'stock_receipt' => [
                    'warehouse' => (string) $this->assignedWarehouse->id(),
                    'article' => (string) $this->article->id(),
                    'quantity' => '0',
                    'vatRate' => '23',
                    'unitNetPrice' => '10',
                    '_token' => $crawler->filter('input[name="stock_receipt[_token]"]')->attr('value'),
                ],
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            'form[name="stock_receipt"] > .alert',
            'maksymalnie 3 miejscami po przecinku.',
        );
        self::assertSelectorTextContains(
            'form[name="stock_receipt"]',
            'maksymalnie 3 miejscami po przecinku.',
        );
        self::assertSame(
            0,
            (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM stock_receipts'),
        );
    }

    public function testFormRejectsUnsupportedVatRate(): void
    {
        $this->logIn('operator');

        $crawler = $this->client->request('GET', self::RECEIPT_URL);

        $this->client->request(
            'POST',
            self::RECEIPT_URL,
            [
                'stock_receipt' => [
                    'warehouse' => (string) $this->assignedWarehouse->id(),
                    'article' => (string) $this->article->id(),
                    'quantity' => '1',
                    'vatRate' => '17',
                    'unitNetPrice' => '10',
                    '_token' => $crawler->filter('input[name="stock_receipt[_token]"]')->attr('value'),
                ],
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('form[name="stock_receipt"] > .alert');
        self::assertSelectorExists('#stock_receipt_vatRate_error1');
        self::assertSelectorTextContains(
            'form[name="stock_receipt"]',
            'Wybierz jedną z dostępnych stawek VAT.',
        );
        self::assertSame(
            0,
            (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM stock_receipts'),
        );
    }

    public function testFormRejectsMoreThanFourDocuments(): void
    {
        self::assertGreaterThan(
            StockReceipt::MAX_DOCUMENTS,
            (int) ini_get('max_file_uploads'),
            'Limit transportowy PHP musi być wyższy od limitu dokumentów w aplikacji.',
        );

        $this->logIn('operator');
        $crawler = $this->client->request('GET', self::RECEIPT_URL);
        $documents = [];

        for ($number = 1; $number <= StockReceipt::MAX_DOCUMENTS + 1; ++$number) {
            $documents[] = $this->upload(
                sprintf('faktura-%d.xml', $number),
                sprintf('<?xml version="1.0"?><invoice id="%d"/>', $number),
            );
        }

        $this->client->request(
            'POST',
            self::RECEIPT_URL,
            [
                'stock_receipt' => [
                    'warehouse' => (string) $this->assignedWarehouse->id(),
                    'article' => (string) $this->article->id(),
                    'quantity' => '1',
                    'vatRate' => '23',
                    'unitNetPrice' => '10',
                    '_token' => $crawler->filter('input[name="stock_receipt[_token]"]')->attr('value'),
                ],
            ],
            ['stock_receipt' => ['documents' => $documents]],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            'form[name="stock_receipt"]',
            'Możesz dołączyć maksymalnie 4 pliki.',
        );
        self::assertSame(
            0,
            (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM stock_receipts'),
        );
    }

    public function testApplicationRejectsReceiptForUnassignedWarehouse(): void
    {
        $data = new ReceiptData(
            (int) $this->unassignedWarehouse->id(),
            (int) $this->article->id(),
            '1',
            VatRate::STANDARD,
            '10',
            ReceiptDocumentUploads::fromIterable(new \EmptyIterator()),
        );

        $service = self::getContainer()->get(StockReceiptService::class);

        $this->expectException(StockReceiptAccessDenied::class);

        $service->receive((int) $this->operator->id(), $data);
    }

    public function testAdministratorSeesEveryWarehouse(): void
    {
        $this->logIn('admin');

        $this->client->request('GET', self::RECEIPT_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf(
            '#stock_receipt_warehouse option[value="%d"]',
            $this->assignedWarehouse->id(),
        ));
        self::assertSelectorExists(sprintf(
            '#stock_receipt_warehouse option[value="%d"]',
            $this->unassignedWarehouse->id(),
        ));
    }

    private function logIn(string $login): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form[action="/login"]')->form([
            'login' => $login,
            'password' => self::PASSWORD,
        ]);

        $this->client->submit($form);
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
    }

    private function createUser(string $login, UserRole $role): User
    {
        $passwordHasher = self::getContainer()->get(PasswordHasher::class);
        $users = self::getContainer()->get(UserRepository::class);
        $user = User::register($login, $passwordHasher->hash(self::PASSWORD), $role);
        $users->save($user);

        return $user;
    }

    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'receipt-test-');

        if (false === $path || false === file_put_contents($path, $content)) {
            throw new \RuntimeException('Nie udało się przygotować pliku testowego.');
        }

        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    private function clearDatabase(): void
    {
        $connection = $this->testConnection();
        $connection->executeStatement('DELETE FROM stock_receipt_documents');
        $connection->executeStatement('DELETE FROM stock_receipts');
        $connection->executeStatement('DELETE FROM stock_issues');
        $connection->executeStatement('DELETE FROM warehouse_users');
        $connection->executeStatement('DELETE FROM warehouses');
        $connection->executeStatement('DELETE FROM articles');
        $connection->executeStatement('DELETE FROM identity_users');
    }

    private function clearStoredDocuments(): void
    {
        $paths = glob($this->storageDirectory().'/*');

        if (false === $paths) {
            return;
        }

        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function storageDirectory(): string
    {
        return (string) self::getContainer()->getParameter('stock_receipt_document_directory');
    }

    private function testConnection(): Connection
    {
        $connection = self::getContainer()->get(Connection::class);
        $databaseName = (string) $connection->getDatabase();

        if (!str_ends_with($databaseName, '_test')) {
            throw new \LogicException(sprintf('Testy nie mogą modyfikować bazy "%s".', $databaseName));
        }

        return $connection;
    }
}
