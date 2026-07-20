<?php

declare(strict_types=1);

namespace App\Tests\Functional\Stock;

use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Stock\Application\Dto\IssueData;
use App\Stock\Application\Exception\StockIssueAccessDenied;
use App\Stock\Application\Service\StockIssueService;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class StockIssueTest extends WebTestCase
{
    private const string PASSWORD = 'bezpieczne-haslo';
    private const string ISSUE_URL = '/stock/issues/create';

    private KernelBrowser $client;
    private User $operator;
    private Warehouse $assignedWarehouse;
    private Warehouse $unassignedWarehouse;
    private Article $article;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->clearDatabase();

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

        parent::tearDown();
    }

    public function testUserSeesOnlyAssignedWarehousesAndCanIssueArticle(): void
    {
        $this->logIn('operator');

        $crawler = $this->client->request('GET', self::ISSUE_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(
            '.sidebar__link[aria-current="page"][href="'.self::ISSUE_URL.'"]',
        );
        self::assertSelectorExists(sprintf(
            '#stock_issue_warehouse option[value="%d"]',
            $this->assignedWarehouse->id(),
        ));
        self::assertSelectorNotExists(sprintf(
            '#stock_issue_warehouse option[value="%d"]',
            $this->unassignedWarehouse->id(),
        ));
        self::assertSelectorTextContains(
            '#stock_issue_article',
            'Cement — jednostka: kg',
        );

        $this->client->request('POST', self::ISSUE_URL, [
            'stock_issue' => [
                'warehouse' => (string) $this->assignedWarehouse->id(),
                'article' => (string) $this->article->id(),
                'quantity' => '2,5',
                '_token' => $crawler->filter('input[name="stock_issue[_token]"]')->attr('value'),
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects(self::ISSUE_URL.'?saved=1');

        $this->client->followRedirect();
        self::assertSelectorTextContains('.notice', 'Wydanie towaru zostało zapisane.');

        $issue = $this->testConnection()->fetchAssociative('SELECT * FROM stock_issues');
        self::assertIsArray($issue);
        self::assertSame((string) $this->assignedWarehouse->id(), (string) $issue['warehouse_id']);
        self::assertSame((string) $this->article->id(), (string) $issue['article_id']);
        self::assertSame((string) $this->operator->id(), (string) $issue['issued_by_id']);
        self::assertSame('2.500', $issue['quantity']);
        self::assertSame('kg', $issue['unit_of_measure']);
    }

    public function testFormRejectsZeroQuantity(): void
    {
        $this->logIn('operator');

        $crawler = $this->client->request('GET', self::ISSUE_URL);

        $this->client->request('POST', self::ISSUE_URL, [
            'stock_issue' => [
                'warehouse' => (string) $this->assignedWarehouse->id(),
                'article' => (string) $this->article->id(),
                'quantity' => '0',
                '_token' => $crawler->filter('input[name="stock_issue[_token]"]')->attr('value'),
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            'form[name="stock_issue"] > .alert',
            'maksymalnie 3 miejscami po przecinku.',
        );
        self::assertSelectorTextContains(
            'form[name="stock_issue"]',
            'maksymalnie 3 miejscami po przecinku.',
        );
        self::assertSame(
            0,
            (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM stock_issues'),
        );
    }

    public function testApplicationRejectsIssueForUnassignedWarehouse(): void
    {
        $data = new IssueData(
            (int) $this->unassignedWarehouse->id(),
            (int) $this->article->id(),
            '1',
        );
        $service = self::getContainer()->get(StockIssueService::class);

        $this->expectException(StockIssueAccessDenied::class);

        $service->issue((int) $this->operator->id(), $data);
    }

    public function testAdministratorSeesEveryWarehouse(): void
    {
        $this->logIn('admin');

        $this->client->request('GET', self::ISSUE_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf(
            '#stock_issue_warehouse option[value="%d"]',
            $this->assignedWarehouse->id(),
        ));
        self::assertSelectorExists(sprintf(
            '#stock_issue_warehouse option[value="%d"]',
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
