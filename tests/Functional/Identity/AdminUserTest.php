<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AdminUserTest extends WebTestCase
{
    private const string PASSWORD = 'bezpieczne-haslo';
    private const string NEW_PASSWORD = 'jeszcze-bezpieczniejsze-haslo';
    private const string AUTOCOMPLETE_URL = '/admin/autocomplete/warehouse_autocomplete_field';

    private KernelBrowser $client;
    private User $operator;
    private Warehouse $mainWarehouse;
    private Warehouse $auxiliaryWarehouse;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->clearDatabase();

        $this->createUser('admin', UserRole::ADMIN);
        $this->operator = $this->createUser('operator', UserRole::USER);
        $this->mainWarehouse = $this->createWarehouse('Magazyn główny');
        $this->auxiliaryWarehouse = $this->createWarehouse('Magazyn pomocniczy');
    }

    protected function tearDown(): void
    {
        $this->clearDatabase();

        parent::tearDown();
    }

    public function testRegularUserCannotManageUsers(): void
    {
        $this->logIn('operator');

        $this->client->request('GET', '/admin/users');
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request('GET', self::AUTOCOMPLETE_URL);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdministratorCanCreateAndEditUserWithWarehouseAssignments(): void
    {
        for ($number = 1; $number <= 4; ++$number) {
            $this->createWarehouse('Magazyn dodatkowy '.$number);
        }

        $this->logIn('admin');

        $crawler = $this->client->request('GET', '/admin/users/create');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a.sidebar__link[href="/admin/users"][aria-current="page"]');
        self::assertSelectorExists(
            '#user_assignedWarehouses[data-controller~="symfony--ux-autocomplete--autocomplete"]',
        );
        self::assertSelectorExists(
            '#user_assignedWarehouses[data-symfony--ux-autocomplete--autocomplete-max-results-value="5"]',
        );
        self::assertSelectorExists(
            '#user_assignedWarehouses[data-symfony--ux-autocomplete--autocomplete-url-value="'.self::AUTOCOMPLETE_URL.'"]',
        );
        self::assertSelectorNotExists('[name="user[role]"]');

        $this->client->request('GET', self::AUTOCOMPLETE_URL);
        self::assertResponseIsSuccessful();

        $autocompleteData = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );
        self::assertCount(5, $autocompleteData['results']);

        $this->client->request('POST', '/admin/users/create', [
            'user' => [
                'login' => 'nowy.user',
                'plainPassword' => self::PASSWORD,
                'assignedWarehouses' => [(string) $this->mainWarehouse->id()],
                '_token' => $crawler->filter('input[name="user[_token]"]')->attr('value'),
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        self::assertResponseRedirects('/admin/users');

        $this->client->followRedirect();
        self::assertSelectorTextContains('table', 'nowy.user');
        self::assertSelectorTextNotContains('table', 'Magazyn główny');

        $createdUser = $this->userRow('nowy.user');
        $createdUserId = (int) $createdUser['id'];
        $originalHash = (string) $createdUser['password_hash'];

        self::assertSame('ROLE_USER', $createdUser['role']);
        self::assertSame('bcrypt', password_get_info($originalHash)['algoName']);
        self::assertTrue(password_verify(self::PASSWORD, $originalHash));
        self::assertSame(1, $this->assignmentCount($createdUserId, (int) $this->mainWarehouse->id()));

        $crawler = $this->client->request('GET', sprintf('/admin/users/%d/edit', $createdUserId));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf(
            '#user_assignedWarehouses option[value="%d"][selected]',
            $this->mainWarehouse->id(),
        ));
        self::assertSelectorExists('#user_plainPassword:not([required])');

        $this->client->request('POST', sprintf('/admin/users/%d/edit', $createdUserId), [
            'user' => [
                'login' => 'edytowany.user',
                'plainPassword' => '',
                'assignedWarehouses' => [(string) $this->auxiliaryWarehouse->id()],
                '_token' => $crawler->filter('input[name="user[_token]"]')->attr('value'),
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);

        $editedUser = $this->userRow('edytowany.user');
        self::assertSame($originalHash, $editedUser['password_hash']);
        self::assertSame(0, $this->assignmentCount($createdUserId, (int) $this->mainWarehouse->id()));
        self::assertSame(1, $this->assignmentCount($createdUserId, (int) $this->auxiliaryWarehouse->id()));

        $crawler = $this->client->request('GET', sprintf('/admin/users/%d/edit', $createdUserId));
        $this->client->request('POST', sprintf('/admin/users/%d/edit', $createdUserId), [
            'user' => [
                'login' => 'edytowany.user',
                'plainPassword' => self::NEW_PASSWORD,
                'assignedWarehouses' => [(string) $this->auxiliaryWarehouse->id()],
                '_token' => $crawler->filter('input[name="user[_token]"]')->attr('value'),
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);

        $newHash = (string) $this->userRow('edytowany.user')['password_hash'];
        self::assertNotSame($originalHash, $newHash);
        self::assertSame('bcrypt', password_get_info($newHash)['algoName']);
        self::assertTrue(password_verify(self::NEW_PASSWORD, $newHash));
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

    private function createWarehouse(string $name): Warehouse
    {
        $warehouse = Warehouse::create($name, []);
        self::getContainer()->get(WarehouseRepository::class)->save($warehouse);

        return $warehouse;
    }

    /** @return array{id: int|string, password_hash: string, role: string} */
    private function userRow(string $login): array
    {
        $row = $this->testConnection()->fetchAssociative(
            'SELECT id, password_hash, role FROM identity_users WHERE login = ?',
            [$login],
        );

        if (false === $row) {
            self::fail(sprintf('Nie znaleziono użytkownika "%s".', $login));
        }

        return $row;
    }

    private function assignmentCount(int $userId, int $warehouseId): int
    {
        return (int) $this->testConnection()->fetchOne(
            'SELECT COUNT(*) FROM warehouse_users WHERE user_id = ? AND warehouse_id = ?',
            [$userId, $warehouseId],
        );
    }

    private function clearDatabase(): void
    {
        $connection = $this->testConnection();
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
