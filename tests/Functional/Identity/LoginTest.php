<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Identity\Application\CreateUser;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->testConnection()->executeStatement('DELETE FROM identity_users');

        self::getContainer()
            ->get(CreateUser::class)
            ->execute('operator', 'bezpieczne-haslo');
    }

    protected function tearDown(): void
    {
        $this->testConnection()->executeStatement('DELETE FROM identity_users');

        parent::tearDown();
    }

    public function testPasswordIsStoredAsBcrypt(): void
    {
        $hash = $this->testConnection()->fetchOne(
            'SELECT password_hash FROM identity_users WHERE login = ?',
            ['operator'],
        );

        self::assertSame('bcrypt', password_get_info((string) $hash)['algoName']);
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('http://localhost/login');
    }

    public function testUserCanLogInUsingDatabaseAdapter(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertSelectorNotExists('aside.sidebar');

        $form = $crawler->filter('form[action="/login"]')->form([
            'login' => 'OPERATOR',
            'password' => 'bezpieczne-haslo',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();

        self::assertSelectorTextContains('h1', 'Witaj, operator');
        self::assertSelectorCount(1, 'main#main-content');
        self::assertSelectorCount(1, 'aside.sidebar nav[aria-label="Główna nawigacja"]');
        self::assertSelectorCount(1, 'a.sidebar__link[aria-current="page"]');
        self::assertSelectorExists('form.sidebar__logout-form[method="post"]');
    }

    public function testInvalidPasswordReturnsGenericMessage(): void
    {
        $this->assertAuthenticationFailsGenerically('operator', 'nieprawidlowe');
    }

    public function testUnknownLoginReturnsTheSameGenericMessage(): void
    {
        $this->assertAuthenticationFailsGenerically('nieistniejacy-uzytkownik', 'nieprawidlowe');
    }

    public function testUserCanLogOut(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form[action="/login"]')->form([
            'login' => 'operator',
            'password' => 'bezpieczne-haslo',
        ]);

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();
        $this->client->submit($crawler->filter('form[action="/logout"]')->form());

        self::assertResponseRedirects('/login');
    }

    private function assertAuthenticationFailsGenerically(string $login, string $password): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form[action="/login"]')->form([
            'login' => $login,
            'password' => $password,
        ]);

        $this->client->submit($form);
        self::assertResponseRedirects('/login');

        $this->client->followRedirect();
        self::assertSelectorTextSame(
            '[role="alert"]',
            'Logowanie nie powiodło się. Spróbuj ponownie.',
        );
        self::assertSelectorTextNotContains('[role="alert"]', $login);
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
