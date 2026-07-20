<?php

declare(strict_types=1);

namespace App\Tests\Functional\Article;

use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Stock\Domain\Model\StockIssue;
use App\Stock\Domain\Repository\StockIssueRepository;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AdminArticleTest extends WebTestCase
{
    private const string PASSWORD = 'bezpieczne-haslo';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->clearDatabase();

        $this->createUser('admin', UserRole::ADMIN);
        $this->createUser('operator', UserRole::USER);
    }

    protected function tearDown(): void
    {
        $this->clearDatabase();

        parent::tearDown();
    }

    public function testRegularUserCannotManageArticles(): void
    {
        $this->logIn('operator');

        $this->client->request('GET', '/admin/articles');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdministratorCanCreateAndEditArticle(): void
    {
        $this->logIn('admin');

        $crawler = $this->client->request('GET', '/admin/articles/create');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form.article-form')->form([
            'name' => '  Taśma   pakowa  ',
            'unit_of_measure' => ' szt. ',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects('/admin/articles');

        $this->client->followRedirect();
        self::assertSelectorTextContains('table', 'Taśma pakowa');
        self::assertSelectorTextContains('table', 'szt.');

        $articleId = (int) $this->testConnection()->fetchOne(
            'SELECT id FROM articles WHERE name = ?',
            ['Taśma pakowa'],
        );
        self::assertGreaterThan(0, $articleId);

        $crawler = $this->client->request('GET', sprintf('/admin/articles/%d/edit', $articleId));
        $form = $crawler->filter('form.article-form')->form([
            'name' => 'Taśma wzmacniana',
            'unit_of_measure' => 'rolka',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);

        $this->client->followRedirect();
        self::assertSelectorTextContains('table', 'Taśma wzmacniana');
        self::assertSelectorTextContains('table', 'rolka');

        $deleteForm = $this->client->getCrawler()->filter('form.delete-form')->form();
        $this->client->submit($deleteForm);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects('/admin/articles');

        $this->client->followRedirect();
        self::assertSelectorTextContains('.empty-state', 'Nie dodano jeszcze żadnych artykułów.');
        self::assertSame(0, (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM articles'));
    }

    public function testAdministratorCannotDeleteArticleUsedInStockOperation(): void
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

        $crawler = $this->client->request('GET', '/admin/articles');
        $deleteForm = $crawler
            ->filter(sprintf('form[action="/admin/articles/%d/delete"]', $article->id()))
            ->form();
        $this->client->submit($deleteForm);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects('/admin/articles');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'operacji magazynowej.');
        self::assertSame(
            1,
            (int) $this->testConnection()->fetchOne('SELECT COUNT(*) FROM articles'),
        );
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

    private function createUser(string $login, UserRole $role): void
    {
        $passwordHasher = self::getContainer()->get(PasswordHasher::class);
        $users = self::getContainer()->get(UserRepository::class);

        $users->save(User::register(
            $login,
            $passwordHasher->hash(self::PASSWORD),
            $role,
        ));
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
