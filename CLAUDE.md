# Prenotar — Primer per Claude Code

## Identità del progetto

**Nome**: Prenotar (PRENO-tazione T-orri AR-rampicata)
**Scopo**: Sistema di prenotazione delle 2 torri di arrampicata mobili CityWall (CST, €38.131,10 cad.) del CAI GR Lombardia.
**Scope**: Solo CAI GR Lombardia — 152 sezioni + 77 sottosezioni, ~230 account totali.
**URL produzione**: `https://prenotar.montagnaservizi.it`
**Deploy**: checklist in [`DEPLOY.md`](./DEPLOY.md); template env [`.env.production.example`](./.env.production.example).
**Repository**: `prenotar`
**Documento di riferimento** (locale, non versionato): `./DOCUMENTI PER LA PROGETTAZIONE/PIANO_REALIZZAZIONE.md`

---

## Stack tecnico

| Layer | Scelta |
|-------|--------|
| Framework | Laravel 11 (PHP 8.4 via Sail) |
| Admin/panel | Filament 3 |
| Database | MariaDB 10.11 |
| Frontend dinamico | Livewire 3 + Alpine.js + TailwindCSS (via Filament) |
| Mail dev | MailPit (porta 1025 SMTP, porta 8025 UI) |
| Mail prod | SMTP Montagna Servizi (via variabili .env) |
| PDF | barryvdh/laravel-dompdf (Fase 0); valutare spatie/laravel-pdf in Fase 5 |
| File storage | driver `local` in dev → S3-compatibile in prod |
| Excel import | maatwebsite/excel |
| Permessi | spatie/laravel-permission |
| Media | spatie/laravel-medialibrary |
| Settings | spatie/laravel-settings |
| Impersonate | stechstudio/filament-impersonate |
| Queue | Redis + (Horizon in Fase 7) |
| Container dev | Laravel Sail (Docker Compose) |
| Container prod | `docker-compose.production.yml` + `Dockerfile` multi-stage (Nginx, PHP-FPM, MariaDB, Redis, queue, scheduler) |
| Code quality | Pint (preset laravel) + Larastan level 6 + Pest |

---

## Comandi quotidiani

```bash
# Avvia ambiente completo
./vendor/bin/sail up -d

# Ferma
./vendor/bin/sail down

# Artisan
./vendor/bin/sail artisan <comando>

# Composer
./vendor/bin/sail composer <comando>

# QA completa (lint + static analysis + test)
./vendor/bin/sail composer qa

# Solo lint (verifica senza modificare)
./vendor/bin/sail composer pint

# Applica fix lint
./vendor/bin/sail composer pint:fix

# Solo static analysis
./vendor/bin/sail composer phpstan

# Solo test
./vendor/bin/sail composer test
```

**URL locali**:
- App: `http://localhost`
- MailPit UI: `http://localhost:8026`
- MariaDB: porta `3306` (host `127.0.0.1` da fuori container)

> Usare SEMPRE Sail per eseguire comandi PHP/Composer. Non usare `php artisan` o `composer` da host — produce drift di estensioni PHP.

---

## Convenzioni codice

- `declare(strict_types=1)` in testa a ogni file PHP.
- Naming **italiano** per le entità di dominio: `Sezione`, `Sottosezione`, `Torre`, `Prenotazione`, `PrenotazioneAllegato`, `PrenotazioneHistory`.
- Naming **inglese** per utility, services, jobs, mail: `ExcelImportService`, `PrenotazioneStateMachine`, `SendInsuranceEmail`, ecc.
- Commenti solo quando il "perché" è non ovvio. Niente docblock descrittivi del "cosa".
- Test in `tests/Feature/` (RefreshDatabase, mai mock del DB) e `tests/Unit/`.
- Nessun commit diretto su `main` — PR + CI verde obbligatori.

---

## Mappa dei pannelli Filament

| URL | Pannello | Ruolo | Fase |
|-----|----------|-------|------|
| `/admin` | Admin tecnico | `admin` | Fase 6 |
| `/gr` | Responsabili GR Lombardia | `gr_manager` | Fase 4 |
| `/sezione` | Presidenti sezione/sottosezione | `sezione`, `sottosezione` | Fase 3 |

I pannelli sono **mutuamente esclusivi**: nessun ruolo eredita le funzionalità di un altro.
L'admin accede alle funzionalità operative **solo via impersonate** (plugin `stechstudio/filament-impersonate`).

---

## Ruoli

| Ruolo | Chi | Responsabilità |
|-------|-----|----------------|
| `admin` | Responsabile tecnico | Gestione tecnica: utenti, sync Excel, audit log, impersonate. NON crea prenotazioni. |
| `gr_manager` | Presidente GR + delegati | Approva/rifiuta, genera PDF, invia assicurazione. NON crea prenotazioni proprie. |
| `sezione` | Presidente sezione (152) | Crea e gestisce le prenotazioni della propria sezione. |
| `sottosezione` | Presidente sottosezione (77) | Come `sezione` ma per la propria sottosezione. |

---

## State machine prenotazioni

```
BOZZA
  │ (sezione invia + allega delibera)
INVIATA
  ├─ (GR approva) ──────────────────→ APPROVATA
  └─ (GR rifiuta) ──────────────────→ ANNULLATA → archivio
       │ (entro T-10: sezione carica PDF firmato)
INVIATO_PDF_FIRMATO
  │ (T-48h: GR genera Modulo 3 + invia assicurazione)
INVIATO_ASSICURAZIONE
  │ (data fine evento passata — job notturno)
CONCLUSO → archivio
```

Ogni transizione registra un record in `prenotazione_history` (autore + timestamp + note).

---

## Bug noti da NON replicare

(Da piattaforma esistente — vedi §8 PIANO_REALIZZAZIONE.md)

| ID | Bug attuale | Fix in Prenotar |
|----|-------------|-----------------|
| BUG-01 | Ordinamento prenotazioni dalla più lontana | Default `data_inizio_prenotazione DESC` |
| BUG-02 | Prenotazioni passate/annullate in vista principale | Auto-archiviazione + tab "Archivio" separato |
| BUG-03 | Secondo campo info parete sovrascrive il primo | State Livewire con array, no shared state |
| BUG-04 | Data riconsegna diverge tra form e calendario | Single source of truth Eloquent |
| BUG-05 | Sottosezioni trattate come sezioni padre | Modello `Sottosezione` distinto, label "S.SEZ. X" |
| BUG-06 | Nessun impersonate per supporto | Plugin filament-impersonate (solo admin) |
| BUG-07 | Calendario duplicato per torre | Calendario unico con 2 colori |
| BUG-08 | Indirizzo deposito non visibile | Campo `torri.indirizzo_deposito` visibile ovunque |
| BUG-09 | Soccorso Alpino non selezionabile | Enum include `soccorso_alpino` |

---

## Roadmap fasi (anti-scope creep)

| Fase | Contenuto | Status |
|------|-----------|--------|
| **0** | Setup (questo file) | ✅ |
| 1 | Migrazioni + modelli + seeders + policy | ✅ |
| 2 | Excel import + auth + fallback email | ✅ |
| 3 | Pannello /sezione + wizard prenotazione + calendario | ✅ |
| 4 | Pannello /gr + state machine + notifiche email | ✅ |
| 5 | Template PDF (Richiesta parete + Modulo 3) | ✅ |
| 6 | Pannello /admin + impersonate UI + audit log | ✅ |
| 7 | Job archiviazione + reminder + Horizon | ⏳ |
| 8 | UAT + polish + deploy staging | ⏳ |

---

## Cosa NON fare automaticamente

- **Niente `git push`** senza conferma esplicita dell'utente.
- **Niente `git push --force`** mai.
- **Niente mock del DB** nei test feature — usare `RefreshDatabase` su MariaDB reale.
- **Niente import Excel** con dati reali in test — usare factory.
- **Niente `php artisan` da host** — solo via `./vendor/bin/sail artisan`.
- **Niente hard delete** di prenotazioni senza conferma esplicita (operazione riservata all'admin autenticato).
- **Niente modifica** di `DOCUMENTI PER LA PROGETTAZIONE/` — è materiale di progettazione, non codice.
