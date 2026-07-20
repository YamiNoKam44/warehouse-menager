# Warehouse Manager

Prosta aplikacja magazynowa napisana w Symfony. Pozwala administratorowi zarządzać
użytkownikami, magazynami i artykułami, a przypisanym użytkownikom przyjmować oraz
wydawać towar.

Przy przyjęciu można dołączyć maksymalnie 4 faktury w formacie PDF lub XML.
Interfejs jest oparty na Twig i Symfony Forms. JavaScript jest używany tylko przez
autocomplete z Symfony UX.

## Uruchomienie lokalne

Do uruchomienia projektu potrzebne są Docker oraz Docker Compose. Porty `60000`
i `60002` powinny być wolne.

W katalogu projektu wykonaj kolejno:

```bash
docker compose up -d --build
docker compose exec php-fpm composer install
docker compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php-fpm php bin/console doctrine:fixtures:load --no-interaction
```

Ostatnia komenda ładuje dane przykładowe i wcześniej czyści bazę. Używaj jej tylko
na lokalnym środowisku, gdy nie ma w bazie danych, które chcesz zachować.

Po zakończeniu instalacji aplikacja jest dostępna pod adresem:

[http://localhost:60000/login](http://localhost:60000/login)

### Konta testowe

| Login | Hasło | Uprawnienia |
| --- | --- | --- |
| `admin` | `warehouse123` | administrator |
| `operator` | `warehouse123` | użytkownik |

Fixture’y tworzą tylko powyższe konta. Żeby przejść cały scenariusz magazynowy:

1. Zaloguj się jako `admin`.
2. Utwórz artykuł.
3. Utwórz magazyn i przypisz do niego użytkownika `operator`.
4. Wykonaj przyjęcie towaru.
5. Zaloguj się jako `operator` i sprawdź przypisany magazyn.

## Przydatne komendy

Zatrzymanie i ponowne uruchomienie kontenerów:

```bash
docker compose stop
docker compose start
```

Podgląd stanu kontenerów i logów:

```bash
docker compose ps
docker compose logs -f
```

W obecnej konfiguracji MySQL nie korzysta z osobnego wolumenu na dane. Polecenie
`docker compose down` usunie kontener bazy razem z jego lokalną zawartością. Do
zwykłego wyłączania projektu używaj `docker compose stop`.

Nowego użytkownika można również utworzyć z konsoli. Komenda zapyta o hasło i nie
wyświetli go podczas wpisywania:

```bash
docker compose exec php-fpm php bin/console app:user:create operator2
```

## Połączenie z bazą w PhpStorm

Dodaj nowe źródło danych MySQL i użyj następujących ustawień:

| Ustawienie | Wartość |
| --- | --- |
| Host | `127.0.0.1` |
| Port | `60002` |
| Baza | `warehouse` |
| Użytkownik | `test` |
| Hasło | `test` |

Usługi uruchomione wewnątrz Dockera łączą się z bazą przez host `mysql` i port
`3306`. Port `60002` służy wyłącznie do połączeń z systemu gospodarza, na przykład
z PhpStorma.

## Testy

Testy korzystają z osobnej bazy `warehouse_test`. Przy pierwszym uruchomieniu trzeba
ją utworzyć i wykonać migracje:

```bash
docker compose exec php-fpm php bin/console doctrine:database:create --if-not-exists --env=test
docker compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction --env=test
docker compose exec php-fpm php bin/phpunit
```

Podstawowe sprawdzenie konfiguracji i szablonów:

```bash
docker compose exec php-fpm php bin/console lint:yaml config
docker compose exec php-fpm php bin/console lint:twig templates
docker compose exec php-fpm php bin/console lint:container
docker compose exec php-fpm php bin/console doctrine:schema:validate
```

## GitHub Actions

Workflow `.github/workflows/ci.yml` uruchamia się dla pull requestów, zmian na
gałęzi `main`, kolejki merge oraz ręcznie. Wykonuje dwa niezależne sprawdzenia:

- `Tests` instaluje zależności, sprawdza je przez `composer audit`, wykonuje
  migracje na testowym MySQL, waliduje konfigurację i uruchamia PHPUnit,
- `OWASP Top 10` analizuje kod przez Semgrep z regułami OWASP.

Żeby nie można było scalić pull requesta z pominięciem tych kontroli, w GitHubie
wejdź w `Settings` → `Rules` → `Rulesets`, utwórz regułę dla `main` i włącz
`Require status checks to pass`. Jako wymagane statusy wybierz `Tests` oraz
`OWASP Top 10`. Statusy pojawią się na liście po pierwszym wykonaniu workflow.

Automatyczny skan pomaga znaleźć typowe podatności, ale nie zastępuje przeglądu
uprawnień, logiki biznesowej ani testów bezpieczeństwa uruchomionej aplikacji.
Deployment nie jest skonfigurowany, ponieważ projekt nie ma jeszcze wskazanego
środowiska docelowego.

## Układ kodu

Kod jest podzielony według obszarów: `Identity`, `Article`, `Warehouse` i `Stock`.
W każdym z nich znajdują się trzy warstwy:

- `Domain` zawiera modele oraz interfejsy repozytoriów i nie zależy od Symfony ani Doctrine,
- `Application` opisuje przypadki użycia, DTO i porty aplikacyjne,
- `Infrastructure` zawiera kontrolery, formularze i adaptery, między innymi Doctrine,
  Symfony Security oraz zapis plików.

To rozdzielenie pozwala utrzymać logikę magazynową poza kodem frameworka, bez
rozbudowywania projektu o dodatkowe abstrakcje, które nie są obecnie potrzebne.

## Dane środowiskowe

Hasła z tego README i wartości z plików `.env` są przeznaczone wyłącznie do pracy
lokalnej. Na innym środowisku należy ustawić własne `APP_SECRET`, `DATABASE_URL`
oraz dane dostępowe użytkowników.
