# Changelog

Tutte le modifiche rilevanti al progetto sono elencate in questo file.

Il formato segue le idee di [Keep a Changelog](https://keepachangelog.com/it/1.1.0/); le versioni rispettano il [Semantic Versioning](https://semver.org/lang/it/).

## [Non rilasciato]

## [0.5.0] - 2026-05-12

### Aggiunto

- **Modifica date prenotazione** (pannello GR): flusso `changeDates` nella state machine, evento `PrenotazioneDateModificate`, notifica email, pagina **Impostazioni** GR e test di copertura.
- **Produzione — Mailpit**: servizio opzionale in `docker-compose.production.yml` per invii di prova; aggiornamenti a `DEPLOY.md` e al template `.env`.
- **Build Docker**: variabile `COMPOSER_INSTALL_DEV` per installare dipendenze di sviluppo nell’immagine (seed demo con Faker).

### Modificato

- **LocalDevSeeder**: risoluzione file Excel da `DOCUMENTI` o `storage/app/private` (`.xls` e file unico in private); skip demo se Faker non è disponibile.
- **PHP-FPM in produzione**: permessi su `public/` per `storage:link` / `filament:optimize`; log FPM su file in `storage/logs` per evitare errori di permesso su `/proc/self/fd/2` dopo `gosu`.

### Altri

- **`.gitignore`**: directory `messages/` esclusa dal repository.

## [0.4.0] - 2026-05-11

### Aggiunto

- **Deploy produzione**: guida [`DEPLOY.md`](./DEPLOY.md), template [`.env.production.example`](./.env.production.example), variabile opzionale `TRUSTED_PROXIES` e trust proxy in `bootstrap/app.php` dietro reverse proxy.
- **Stack Docker produzione**: `Dockerfile` multi-stage (asset Vite, Composer, PHP-FPM, immagine Nginx), `docker-compose.production.yml` (MariaDB 10.11, Redis 7, worker, `schedule:work`, volumi DB/storage), script entrypoint in `docker/php/`, config Nginx in `docker/nginx/`, `.dockerignore`.

## [0.3.0] - 2026-05-11

### Aggiunto

- **Fase 3 — pannello sezione** (`/sezione`): wizard prenotazione e calendario torri.
- **State machine prenotazioni** e sistema di notifiche collegato alle transizioni.
- **LocalDevSeeder**: sezioni e sottosezioni CAI Lombardia, utenti admin e GR per sviluppo locale.
- **README**: istruzioni `migrate:fresh --seed` e account di sviluppo.

## [0.2.0] - 2026-05-11

### Aggiunto

- **Import Excel**: `ExcelImportService`, DTO esito import, comando Artisan `sync:excel`; pagina Filament **Importa Excel** e risorsa in sola lettura **ExcelImport** nel pannello admin.
- **Notifiche primo accesso**: `SetPasswordNotification` con CC di fallback, evento/listener collegati.
- **Pannelli Filament** (placeholder Fase 2): provider **GR** (`/gr`) e **Sezione** (`/sezione`).
- **Accesso ai pannelli**: `User::canAccessPanel`, middleware **EnsureContactEmail**, pagina **FirstAccess** per completare i dati di contatto.
- **Login / reset password** con branding (nome app e logo placeholder).
- **AdminDevSeeder**: utente admin di sviluppo solo in `local` / `testing`.
- **Test** su import Excel, flusso auth, accesso ai pannelli e redirect al primo accesso.

## [0.1.0] - 2026-05-10

### Aggiunto

- Ambiente di sviluppo con **Laravel Sail** (PHP 8.4), **MariaDB 10.11**, **Redis**, **Mailpit**.
- Stack applicativo: **Laravel 11**, **Filament 3**, **Livewire**, **Tailwind** (via Filament).
- Pacchetti dominio: **Spatie** (permission, medialibrary, settings), import **Excel**, **Impersonate** per Filament, generazione **PDF** (Dompdf).
- Qualità codice: **Laravel Pint**, **Larastan** (livello 6), **Pest**; script Composer `qa` (pint + phpstan + test).
- **GitHub Actions**: CI con Pint, Larastan e Pest su MariaDB 10.11.
- **Dominio prenotazioni**: migrazioni e modelli per sezioni, sottosezioni, torri, prenotazioni, storico, import Excel; enum `PrenotazioneStatus` e `ResponsabileTipo`.
- **Autorizzazioni**: ruoli (`admin`, `gr_manager`, `sezione`, `sottosezione`), permessi granulari, **policy** su prenotazioni, utenti, torri, sezioni e sottosezioni.
- **Impostazioni GR** (Spatie Settings) con valori predefiniti; **seeder** ruoli/permessi e torri (Torre 1 e Torre 2).
- **Test** automatici su relazioni, policy, seeders, settings e media library.
- Documentazione operativa in **`CLAUDE.md`** (stack, comandi Sail, pannelli, state machine, roadmap); **README** orientato a Prenotar.
- **Licenza MIT** e metadati di **versione applicativa** (`APP_VERSION`, `APP_VERSION_DATE`) in configurazione.
