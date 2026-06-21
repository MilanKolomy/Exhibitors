# Exhibitors — Registrační systém vystavovatelů

Webová aplikace pro registraci vystavovatelů na festivaly. Vystavovatel vyplní formulář (výběr festivalů, stánkové plochy, elektřiny), systém vypočítá cenu, odešle potvrzovací e-mail a uloží záznam do databáze. Administrátor vidí přehled registrací, může filtrovat podle festivalu, exportovat do Excelu a zneplatňovat záznamy.

## Technologie

- **PHP 7.4+** — Slim 4 (router + middleware), PHP-DI (dependency injection)
- **Twig 3** — šablony
- **MySQL** — databáze
- **PHPMailer** — odesílání e-mailů
- **PhpSpreadsheet** — export do XLSX
- **Alpine.js + Tailwind CSS** — frontend

## Požadavky

- PHP >= 7.4
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache s `mod_rewrite` (nebo jiný webserver s URL rewritingem)

## Instalace

```bash
git clone https://github.com/MilanKolomy/Exhibitors.git
cd Exhibitors
composer install
```

Zkopírovat a vyplnit konfiguraci:

```bash
cp .env.example .env
```

Importovat schéma databáze:

```bash
mysql -u <user> -p <databaze> < db.sql
```

## Konfigurace (.env)

| Proměnná | Popis |
|---|---|
| `APP_ENV` | `development` / `production` |
| `APP_BASE_PATH` | Základní cesta aplikace (např. `/Exhibitors` nebo prázdné pro root) |
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | Přístup k databázi |
| `RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET_KEY` | Google reCAPTCHA v2 klíče |
| `ADMIN_USER` / `ADMIN_PASS_HASH` | Přihlašovací údaje do administrace (hash generovaný přes `password_hash()`) |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | SMTP konfigurace |
| `MAIL_FROM` / `MAIL_FROM_NAME` | Odesílatel e-mailů |

Vygenerování hesla pro administraci:

```php
echo password_hash('moje-heslo', PASSWORD_BCRYPT);
```

## Struktura projektu

```
├── config/
│   ├── container.php     # DI kontejner (PHP-DI)
│   ├── festivals.php     # Seznam festivalů
│   ├── fields.php        # Definice polí formuláře
│   ├── pricing.php       # Ceník stánků a elektřiny
│   ├── routes.php        # Definice routování
│   └── settings.php      # Načítání .env, helper funkce
├── lang/
│   ├── cs/               # České překlady
│   └── en/               # Anglické překlady
├── public/
│   ├── .htaccess         # Apache rewrite rules
│   ├── index.php         # Vstupní bod aplikace
│   └── assets/           # Statické soubory (obrázky)
├── src/
│   ├── Controllers/
│   │   ├── Admin/        # DashboardController, AuthController
│   │   ├── AresController.php
│   │   └── RegistrationController.php
│   ├── Middleware/
│   │   ├── AdminAuthMiddleware.php
│   │   └── LocaleMiddleware.php
│   ├── Models/
│   │   ├── Exhibitor.php
│   │   └── ExhibitorFestival.php
│   ├── Services/
│   │   ├── AresService.php     # Načítání firmy z ARES (IČO)
│   │   ├── CaptchaService.php
│   │   ├── ExportService.php   # Generování XLSX exportů
│   │   └── MailService.php
│   └── helpers.php
├── templates/
│   ├── admin/            # Šablony administrace
│   ├── layouts/          # Základní layouty (app, admin)
│   └── registration/     # Formulář, potvrzení, podmínky
├── assets/
│   └── img/              # Logo a obrázky
├── db.sql                # Schéma databáze
└── .env.example          # Vzorová konfigurace
```

## Administrace

Přihlášení na `/admin/login`. Chráněné sekce vyžadují session.

| Funkce | URL |
|---|---|
| Přehled registrací | `/admin/` |
| Filtr podle festivalu | `/admin/?festival=<id>` |
| Export registrací (XLSX) | `/admin/export` |
| Export podle festivalů (XLSX) | `/admin/export-festivals` |
| Zneplatnit vystavovatele | `POST /admin/exhibitor/<id>/invalidate` |

Zneplatnění nastaví `deleted_at` timestamp — záznam se nesmaže, pouze zmizí ze seznamu a exportů.

## Databáze

Schéma je v `db.sql`. Hlavní tabulky:

- `exhibitors` — registrace vystavovatelů
- `exhibitor_festivals` — vazba vystavovatel ↔ festival (stánek, elektřina, cena)
