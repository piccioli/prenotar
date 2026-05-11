# Changelog

Tutte le modifiche rilevanti al progetto sono elencate in questo file.

Il formato segue le idee di [Keep a Changelog](https://keepachangelog.com/it/1.1.0/); le versioni rispettano il [Semantic Versioning](https://semver.org/lang/it/).

## [Non rilasciato]

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
