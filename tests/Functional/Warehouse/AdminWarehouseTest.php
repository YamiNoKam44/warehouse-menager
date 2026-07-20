<?php

declare(strict_types=1);

namespace App\Tests\Functional\Warehouse;

use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Port\UnitOfWork;
use App\Stock\Domain\Model\StockIssue;
use App\Stock\Domain\Repository\StockIssueRepository;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminWarehouseTest extends WebTestCase
{
    private const string PASSWORD = 'bezpieczne-haslo';
    private const string AUTOCOMPLETE_URL = '/admin/autocomplete/user_autocomplete_field';

    private KernelBrowser $client;
    private User $operator;
    private User $magazynier;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->clearDatabase();

        $this->createUser('admin', UserRole::ADMIN);
        $this->operator = $this->createUser('operator', UserRole::USER);
        $this->magazynier = $this->createUser('magazynier', UserRole::USER);
    }

    protected function tearDown(): void
    {
        $this->clearDatabase();

        parent::tearDown();
    }

    public function testRegularUserCannotManageWarehouses(): void
    {
        $this->logIn('operator');

        $this->client->request(Request::METHOD_GET, '/admin/warehouses');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request(Request::METHOD_GET, self::AUTOCOMPLETE_URL);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdministratorCanCreateEditAndDeleteWarehouse(): void
    {
        for ($number = 1; $number <= 4; ++$number) {
            $this->createUser(sprintf('uzytkownik%d', $number), UserRole::USER);
        }

        $this->logIn('admin');

        $crawler = $this->client->request(Request::METHOD_GET, '/admin/warehouses/create');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists(
            '#warehouse_assignedUsers[data-controller~="symfony--ux-autocomplete--autocomplete"]',
        );
        self::assertSelectorExists(
            '#warehouse_assignedUsers[data-symfony--ux-autocomplete--autocomplete-max-results-value="5"]',
        );
        self::assertSelectorExists(
            sprintf(
                '#warehouse_assignedUsers[data-symfony--ux-autocomplete--autocomplete-url-value="%s"]',
                self::AUTOCOMPLETE_URL,
            ),
        );
        self::assertSelectorCount(0, '#warehouse_assignedUsers option');
        self::assertSelectorNotExists('script[src="/scripts/warehouse-user-select.js"]');

        $csrfToken = $crawler->filter('input[name="warehouse[_token]"]')->attr('value');

        $this->client->request(Request::METHOD_GET, self::AUTOCOMPLETE_URL);
        self::assertResponseIsSuccessful();

        $autocompleteData = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );
        self::assertCount(5, $autocompleteData['results']);

        $this->client->request(Request::METHOD_POST, '/admin/warehouses/create', [
            'warehouse' => [
                'name' => '  Magazyn   główny  ',
                'assignedUsers' => [(string) $this->operator->id()],
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects('/admin/warehouses');

        $this->client->followRedirect();
        self::assertSelectorTextContains('table', 'Magazyn główny');
        self::assertSelectorTextNotContains('table', 'operator');

        $warehouseId = (int) $this->testConnection()->fetchOne(
            'SELECT id FROM warehouses WHERE name = ?',
            ['Magazyn główny'],
        );
        self::assertGreaterThan(0, $warehouseId);
        self::assertSame(1, (int) $this->testConnection()->fetchOne(
            'SELECT COUNT(*) FROM warehouse_users WHERE warehouse_id = ? AND user_id = ?',
            [$warehouseId, $this->operator->id()],
        ));

        $crawler = $this->client->request(Request::METHOD_GET, sprintf('/admin/warehouses/%d/edit', $warehouseId));
        self::assertSelectorExists(sprintf(
            '#warehouse_assignedUsers option[value="%d"][selected]',
            $this->operator->id(),
        ));

        $this->client->request(Request::METHOD_POST, sprintf('/admin/warehouses/%d/edit', $warehouseId), [
            'warehouse' => [
                'name' => 'Magazyn pomocniczy',
                'assignedUsers' => [(string) $this->magazynier->id()],
                '_token' => $crawler->filter('input[name="warehouse[_token]"]')->attr('value'),
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        $this->client->followRedirect();
        self::assertSelectorTextContains('table', 'Magazyn pomocniczy');
        self::assertSelectorTextNotContains('table', 'magazynier');
        self::assertSelectorTextNotContains('table', 'operator');

        $deleteForm = $this->client->getCrawler()->filter('form.delete-form')->form();
        $this->client->submit($deleteForm);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.empty-state', 'Nie dodano jeszcze żadnych magazynów.');
        self::assertSame(0, (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM warehouses'));
        self::assertSame(0, (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM warehouse_users'));
    }

    public function testAdministratorCannotDeleteWarehouseUsedInStockOperation(): void
    {
        $this->logIn('admin');

        $users = self::getContainer()->get(UserRepository::class);
        $admin = $users->findByLogin('admin');
        self::assertInstanceOf(User::class, $admin);

        $articles = self::getContainer()->get(ArticleRepository::class);
        $article = Article::create('Cement', 'kg');
        $articles->save($article);

        $warehouses = self::getContainer()->get(WarehouseRepository::class);
        $warehouse = Warehouse::create('Magazyn', new \EmptyIterator());
        $warehouses->save($warehouse);

        $issues = self::getContainer()->get(StockIssueRepository::class);
        $issues->save(StockIssue::create(
            $warehouse,
            $article,
            $admin,
            '1',
            new \DateTimeImmutable(),
        ));
        self::getContainer()->get(UnitOfWork::class)->commit();

        $crawler = $this->client->request(Request::METHOD_GET, '/admin/warehouses');
        $deleteForm = $crawler
            ->filter(sprintf('form[action="/admin/warehouses/%d/delete"]', $warehouse->id()))
            ->form();
        $this->client->submit($deleteForm);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects('/admin/warehouses');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'operacji magazynowej.');
        self::assertSame(
            1,
            (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM warehouses'),
        );
    }

    private function logIn(string $login): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/login');
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
        self::getContainer()->get(UnitOfWork::class)->commit();

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
