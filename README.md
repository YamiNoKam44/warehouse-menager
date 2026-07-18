# Warehouse Manager

Pierwszy pionowy fragment aplikacji zawiera konfigurację MySQL, moduł Identity w architekturze hexagonalnej oraz logowanie formularzowe Symfony bez JavaScriptu.

## Uruchomienie

```bash
docker compose up -d
docker compose exec -T php-fpm composer install
docker compose exec -T php-fpm php bin/console doctrine:migrations:migrate --no-interaction
```

Aplikacja jest dostępna pod adresem [http://localhost:60000/login](http://localhost:60000/login).

Utworzenie pojedynczego użytkownika odbywa się przez interaktywną komendę. Hasło jest pobierane w ukrytym polu i zapisywane jako bcrypt:

```bash
docker compose exec php-fpm php bin/console app:user:create operator
```

### Użytkownicy developerscy

Fixtures tworzą dwa przykładowe konta:

- `operator` z rolą `ROLE_USER`,
- `admin` z rolą `ROLE_ADMIN`.

Hasło obu kont to `warehouse123`. Dane te są dostępne wyłącznie w środowiskach `dev` i `test`.

```bash
docker compose exec -T php-fpm php bin/console doctrine:fixtures:load --no-interaction
```

Polecenie fixtures czyści istniejące dane przed załadowaniem przykładów. Nie należy uruchamiać go na bazie zawierającej potrzebne dane.

Danych produkcyjnych nie należy wpisywać do repozytorium. W środowisku docelowym trzeba nadpisać co najmniej `APP_SECRET` i `DATABASE_URL`.

## Moduł Identity

- `Domain` — czysty model `User`, reguły loginu oraz port repozytorium; bez Symfony i Doctrine.
- `Application` — przypadek użycia tworzenia użytkownika i port hashowania hasła.
- `Infrastructure` — adapter Doctrine z mapowaniem XML, adapter Symfony Security, kontrolery HTTP i komenda CLI.

Domena nie implementuje `UserInterface`. Symfony uwierzytelnia przez osobny `SecurityUser` oraz `DatabaseUserProvider`, który korzysta z domenowego portu repozytorium.

## Testy

Testy funkcjonalne używają osobnej bazy `warehouse_test`:

```bash
docker compose exec -T php-fpm php bin/console doctrine:database:create --if-not-exists --env=test
docker compose exec -T php-fpm php bin/console doctrine:migrations:migrate --no-interaction --env=test
docker compose exec -T php-fpm php bin/phpunit
```

Dodatkowa walidacja konfiguracji:

```bash
docker compose exec -T php-fpm php bin/console lint:yaml config
docker compose exec -T php-fpm php bin/console lint:twig templates
docker compose exec -T php-fpm php bin/console lint:container
docker compose exec -T php-fpm php bin/console doctrine:schema:validate
docker compose exec -T php-fpm php bin/console doctrine:schema:validate --env=test
```
