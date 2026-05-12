# Changelog

Tutte le modifiche rilevanti al progetto sono elencate in questo file.

Il formato segue le idee di [Keep a Changelog](https://keepachangelog.com/it/1.1.0/); le versioni rispettano il [Semantic Versioning](https://semver.org/lang/it/).

## [Non rilasciato]

## [0.8.0] - 2026-05-12

### Aggiunto

- **Fase 7 — code, reminder e Horizon**: **Laravel Horizon** (config, `HorizonServiceProvider`, accesso da pannello admin, snapshot schedulato); worker Horizon in **Docker produzione** e servizio in **Sail** (`compose.yaml`); coda default Redis in `config/queue.php`.
- **Job**: `ArchiveCompletedReservations`; `SendReminderT10` e `SendReminderT2gg` con notifiche dedicate; flag su `prenotazioni` per evitare invii duplicati (migrazione).
- **Comando** `prenotazioni:nightly` (`RunNightlyTasks`) che accoda i job notturni; **scheduler** in `routes/console.php` (esecuzione giornaliera `05:00` `Europe/Rome`, `onOneServer` / `withoutOverlapping`).
- **Dominio**: evento `PrenotazioneConclusa` e transizione **concludi** in **state machine**; in pannello **GR**, azione manuale **«Segna come conclusa»** su prenotazioni dopo invio assicurazione (in aggiunta al job notturno).
- **Test** su Horizon (accesso admin), job di archiviazione e reminder, transizione concludi.

### Modificato

- **`DEPLOY.md`**: istruzioni aggiornate per stack con Horizon e job notturni.

## [0.7.0] - 2026-05-12

### Aggiunto

- **Fase 6 — pannello admin** (`/admin`): log attività (Spatie Activity Log), risorse Filament per utenti, torri e prenotazioni; integrazione **impersonate** e miglioramenti operativi al pannello tecnico.

### Modificato

- **Filament (admin / GR / sezione)**: colonne e campi data usano `placeholder('—')` al posto di `default('—')` dove la data è opzionale, per evitare errori di formattazione con valori non data.
- **UserResource (admin)**: rimossa la colonna «Sezione» ridondante rispetto al nominativo account; **filtro per sezione** invariato.

### Altri

- **LocalDevSeeder**: asset di demo (es. foto torri) e descrizioni più ricche per le torri in sviluppo locale.

## [0.6.0] - 2026-05-12

### Aggiunto

- **Fase 5 — PDF e assicurazione**: servizio `PdfGenerator` con template Blade **Richiesta parete** e **Modulo 3**; azioni GR su `ViewPrenotazione` per scaricare PDF, caricare PDF firmato (Spatie Media Library) e inviare il Modulo 3 all’assicurazione (`Modulo3Mail`, vista email dedicata).
- **State machine**: transizioni e validazioni per caricamento PDF firmato e invio assicurazione; eventi `PrenotazionePdfFirmatoCaricato`, `PrenotazioneInviataAssicurazione`; listener per notifica sezione e invio Modulo 3.
- **Test** di copertura su generazione PDF, caricamento PDF firmato, invio assicurazione e flusso workflow GR.

### Modificato

- **Prenotazione**: PDF firmato gestito come media collection invece del campo `pdf_firmato_path` (migrazione di rimozione colonna).

### Altri

- **LocalDevSeeder**: impostazioni GR di default, permessi aggiornati e asset di demo (documenti GR) per sviluppo locale.

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
