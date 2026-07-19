# Cégértékelő

Symfony review-aggregátor mini-alkalmazás (Trustindex felvételi feladat). Felhasználók
véleményt írhatnak cégekről; a vélemények nyilvánosak és listázva vannak a főoldalon, egy külön
oldal pedig cégenkénti bontásban mutatja az összesített statisztikákat (vélemények száma,
átlagos értékelés).

## Funkciók

- Új vélemény beküldése (cégnév, 1–5 csillagos értékelés, szöveg, email cím), validációval és
  sikeres mentés után flash üzenettel.
- Vélemények listázása a főoldalon (cégnév, csillagos értékelés, csonkított szöveg, dátum).
- Vélemény részletező oldal.
- `/companies` oldal: cégenkénti vélemény-szám és átlagos értékelés, átlag szerint csökkenő
  sorrendben.
- **Bónusz:** keresés cégnév alapján a főoldalon.
- **Bónusz (extra):** OWASP-alapú védelmi intézkedések — lásd [Biztonsági megfontolások](#biztonsági-megfontolások).

## Technológiai stack

- PHP 8.3, Symfony 7.4
- Doctrine ORM + Doctrine Migrations
- MySQL 8.0 (Docker konténerben)
- Twig + Bootstrap 5 (CDN) + egyedi CSS
- Symfony Forms + Validator
- PHPUnit

## Előfeltételek

- PHP 8.2+ (a fejlesztés PHP 8.3-mal történt), Composer
- Docker + Docker Compose (a MySQL adatbázishoz)
- [Symfony CLI](https://symfony.com/download) (opcionális, a `symfony serve` parancshoz — enélkül
  is futtatható `php -S` vagy bármilyen más helyi PHP szerverrel)

## Telepítés és futtatás

```bash
# 1. Függőségek telepítése
composer install

# 2. Adatbázis konténer elindítása
docker compose up -d database

# 3. Local env fájl létrehozása (nincs verziókezelve, mert a jelszót/kapcsolati adatokat tartalmazza)
echo 'DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"' > .env.local

# 4. Migrációk lefuttatása
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Alkalmazás indítása
symfony serve
# vagy: php -S 127.0.0.1:8000 -t public
```

Az app ezután a `http://127.0.0.1:8000` címen érhető el.

### Adatbázis létrehozás és migrációk

Az adatbázis maga a `docker compose up -d database` paranccsal jön létre (MySQL konténer, az
`app` adatbázissal és felhasználóval — lásd `compose.yaml`). A tábláké a Doctrine Migrations
feladata: a `migrations/` mappában lévő migráció (`doctrine:migrations:diff` paranccsal
generálva) hozza létre a `review` táblát:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

A teszt adatbázis (`app_test`) és a hozzá szükséges jogosultság automatikusan létrejön a
konténer első indításakor (`docker/mysql/init/01-create-test-database.sql`, amit a MySQL image
a `docker-entrypoint-initdb.d` mechanizmuson keresztül fut le) — nincs szükség külön manuális
lépésre. Csak a migrációt kell lefuttatni rá is:

```bash
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

## Tesztelés

```bash
php bin/phpunit
```

3 teszt fut le hibamentesen:
- `tests/Repository/ReviewRepositoryTest.php` (unit teszt) — a `findCompanyStatistics()`
  átlagszámítási és rendezési logikáját teszteli, tranzakcióba csomagolva (nem szennyezi az
  adatbázist ismételt futtatás esetén).
- `tests/Controller/ReviewControllerTest.php` (funkcionális teszt) — a vélemény-beküldési
  folyamatot és a rate limitert teszteli.
- `tests/EventSubscriber/SecurityHeadersSubscriberTest.php` (funkcionális teszt) — a biztonsági
  response headerek jelenlétét ellenőrzi.

## Kódstílus

```bash
composer cs-check   # dry-run, csak megmutatja mit javítana
composer cs-fix      # ténylegesen alkalmazza a javításokat
```

A `.php-cs-fixer.dist.php` a Symfony kódstílus szabályokat (`@Symfony` ruleset) alkalmazza.

## Biztonsági megfontolások

Ez az app tartalmaz néhány szándékos, OWASP-hoz igazodó védelmi intézkedést mint "extra" bónuszt
(2.6. feladat), a Symfony által alapból biztosítottak mellett.

**Kifejezetten ehhez az apphoz épített védelmek:**

- **Rate limiting a vélemény-beküldésen** ([OWASP A04:2021 – Insecure Design][a04] /
  [API4:2023 – Unrestricted Resource Consumption][api4]) — a `/reviews/new` legfeljebb 5
  beküldési kísérletet enged 10 percenként, IP-cím alapján (sliding window), Symfony
  `rate-limiter` komponenssel (`config/packages/rate_limiter.yaml`,
  `src/Controller/ReviewController.php`). Ez korlátozza a spam/tömeges hamis vélemény
  visszaéléseket, ami egy review-aggregátor platform esetén valós probléma. Az ellenőrzés minden
  beküldési kísérletnél lefut — érvényesnél és érvénytelennél is —, így a hibás adatokkal való
  elárasztás ellen is védelmet nyújt, nem csak a sikeres beküldések ellen.
- **Biztonsági response headerek** ([OWASP A05:2021 – Security Misconfiguration][a05]) —
  `src/EventSubscriber/SecurityHeadersSubscriber.php` minden válaszhoz hozzáadja az
  `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy: strict-origin-when-cross-origin` headereket, valamint egy
  `Content-Security-Policy`-t. A CSP szándékosan szigorú: sehol nincs `unsafe-inline` (még
  stílusoknál sem — az egyetlen hely, ahol korábban kellett volna, a vélemény-részletező oldal,
  CSS osztályra lett átalakítva), a scriptek `'self'`-only, és csak a Bootstrap CDN eredet van
  engedélyezve stílusokhoz/fontokhoz.

**Amit a keretrendszer már alapból biztosít (érdemes kimondani, nem csak feltételezni):**

- **CSRF védelem** ([OWASP A01:2021 – Broken Access Control][a01] kategória, cross-site request
  forgery) — minden Symfony Form, így a `ReviewType` is, automatikusan tartalmaz CSRF tokent;
  nincs hozzá extra kód, de valóban be van kapcsolva és érvényesítve van.
- **XSS védelem** ([OWASP A03:2021 – Injection][a03]) — a Twig alapból minden kimenetet
  automatikusan escape-el. Egyetlen sablon sem használja a `|raw` filtert, így a felhasználó
  által beküldött vélemény szöveg/cégnév nem tud HTML/script kódot injektálni az oldalakba.
- **SQL injection védelem** ([OWASP A03:2021 – Injection][a03]) — a `ReviewRepository`
  mindenhol Doctrine query builder-t használ kötött paraméterekkel (`setParameter(...)`),
  beleértve a cégnév-keresést is (`findLatest()`); sehol nincs nyers SQL string összefűzés az
  appban.

[a01]: https://owasp.org/Top10/A01_2021-Broken_Access_Control/
[a03]: https://owasp.org/Top10/A03_2021-Injection/
[a04]: https://owasp.org/Top10/A04_2021-Insecure_Design/
[a05]: https://owasp.org/Top10/A05_2021-Security_Misconfiguration/
[api4]: https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/

## Munkaidő napló

A fejlesztés során AI-asszisztenst (Claude Code) használtam a feladatleírásban jelzett
lehetőséggel élve — a döntéseket, a kódot és az architektúrát végig átnéztem és jóváhagytam.

| Feladat | Idő |
|---|---|
| Projekt scaffold (Symfony skeleton, Doctrine, Twig, PHPUnit, MySQL Docker környezet) | ~1.0 óra |
| Adatmodell (Review entitás, migráció, ReviewRepository) | ~0.5 óra |
| Funkcionalitás (form, lista, részletező oldal, cégstatisztika, keresés) | ~1.5 óra |
| Egyedi stílus (Bootstrap + saját CSS) | ~0.5 óra |
| Bónusz: OWASP védelmi intézkedések (rate limiting, security headerek) | ~1.0 óra |
| Tesztek (unit + funkcionális) | ~0.5 óra |
| Kódminőség (php-cs-fixer, DRY átnézés) | ~0.25 óra |
| README + dokumentáció | ~0.25 óra |
| **Összesen** | **~5.5 óra** |
