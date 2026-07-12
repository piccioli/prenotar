# Prenotar — Primer per Claude Code

## Identità del progetto

**Nome**: Prenotar (PRENO-tazione T-orri AR-rampicata)
**Scopo**: Sistema di prenotazione delle 2 torri di arrampicata mobili CityWall (CST, €38.131,10 cad.) del CAI GR Lombardia.
**Scope**: Solo CAI GR Lombardia — 152 sezioni + 77 sottosezioni, ~230 account totali.
**URL produzione**: `https://prenotar.montagnaservizi.com`
**URL develop**: `https://prenotar.develop.montagnaservizi.com`
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
| Queue | Redis + Laravel Horizon |
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

## Hook di pre-commit (QA obbligatoria)

Il repository include `.githooks/pre-commit`, che lancia `./vendor/bin/sail composer qa` (Pint + Larastan + Pest) e blocca il commit se la QA fallisce o se i container Sail non sono in esecuzione.

Attivazione **una tantum** su ogni macchina di sviluppo:

```bash
git config core.hooksPath .githooks
```

---

## Branching e workflow

- Tutto lo sviluppo parte dal branch `develop`, non da `main`: i branch di feature si creano da `develop` e le PR vanno aperte verso `develop`.
- `main` riceve PR **solo** da `develop` (release) o da branch `hotfix/*` (fix urgenti in produzione — vedi "Flusso di hotfix").
- `.github/workflows/ci.yml` lancia Pint + Larastan + Pest su ogni `pull_request:` (nessun filtro `branches`), quindi copre automaticamente sia le PR verso `develop` sia quelle verso `main`.

---

## Flusso di hotfix

Per un bug urgente in produzione, senza aspettare il normale ciclo di release da `develop`:

1. Branch `hotfix/<nome>` creato direttamente da `main` (non da `develop`).
2. Fix implementato sul branch `hotfix/<nome>`.
3. PR aperta verso `main` — coperta dalla CI esistente (`.github/workflows/ci.yml`, nessun filtro `branches` sul trigger `pull_request:`), deve risultare verde prima del merge.
4. Merge della PR su `main` → il workflow `cd-production.yml` (vedi Fase 7 / US-007) parte automaticamente: backup del DB, build, migrazione, restart dello stack di produzione.
5. Alla chiusura dell'hotfix va aperta **anche** una PR `hotfix/<nome>` → `develop`, per non perdere il fix nel flusso normale di sviluppo (altrimenti verrebbe sovrascritto dalla prossima release `develop` → `main`).

Il merge di un hotfix su `main` corrisponde **sempre** a un bump di **patch version** (terzo numero, es. `1.2.3` → `1.2.4`) in `CHANGELOG.md` e alla creazione di un nuovo tag git sul commit di merge su `main`.

---

## Versionamento

- Il bump di versione (major/minor, primo o secondo numero, es. `1.2.3` → `1.3.0` o `2.0.0`) va deciso **sul branch `develop`**: aggiornare `CHANGELOG.md` (spostare le voci da `## [Non rilasciato]` alla nuova versione) e creare il tag git corrispondente **prima** di aprire la PR `develop` → `main`.
- La PR `develop` → `main` **non deve modificare** il numero di versione: è già stato deciso e taggato su `develop`, il merge su `main` porta solo il codice già versionato.
- L'unica eccezione è la **patch release da hotfix**, taggata direttamente su `main` invece che su `develop` — vedi "Flusso di hotfix" qui sopra.

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

## Stato del deploy (infrastruttura)

**Versione corrente**: `v0.10.1` (tag su `main`).

**Server**: `root@116.203.88.140` (Hetzner Cloud, Ubuntu 24.04) — un solo host per entrambi gli ambienti.

| Ambiente | URL | TLS | Deploy automatico |
|----------|-----|-----|--------------------|
| Produzione | `https://prenotar.montagnaservizi.com` (porta 443) | Let's Encrypt, Certbot in-container | push su `main` → `.github/workflows/cd-production.yml` (con backup DB pre-deploy) |
| Develop | `https://prenotar.develop.montagnaservizi.com:8443` | Let's Encrypt, webroot condiviso con produzione (solo prod pubblica le porte 80/443) | push su `develop` → `.github/workflows/cd-develop.yml` |

**CI/CD**: due runner self-hosted GitHub Actions **online**, ciascuno legato al proprio branch tramite label:
- `prenotar-production` (label `production`) — checkout in `/opt/actions-runner-production/_work/prenotar/prenotar`, branch `main`
- `prenotar-develop` (label `develop`) — checkout in `/opt/actions-runner-develop/_work/prenotar/prenotar`, branch `develop`
- Checkout admin per operazioni manuali (backup, rinnovo certificati, ecc.): `/root/prenotar`, branch `main`

**Dati**:
- Produzione: DB migrato ma **vuoto** — nessun import Excel reale eseguito (da fare al go-live effettivo).
- Develop: seedato con `LocalDevSeeder` (229 sezioni/sottosezioni reali, admin/GR demo, password `password` per tutti gli account).

**Bloccato**: credenziali SMTP Gmail reali non ancora disponibili — mailer configurato ma non attivabile in produzione (vedi `.env.production.example` e `DEPLOY.md` §2).

Dettagli operativi completi (setup da zero, emissione/rinnovo certificati, rollback, troubleshooting): vedi `DEPLOY.md`.

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
| 7 | Job archiviazione + reminder + Horizon | ✅ |
| 8 | UAT + polish + deploy develop | ⏳ (infra di deploy live, vedi "Stato del deploy" — mancano UAT reale e import Excel produzione) |

---

## Cosa NON fare automaticamente

- **Niente `git push`** senza conferma esplicita dell’utente, **salvo** quando chiede una **minor release** (`versione X.Y.0`, changelog, tag): in quel caso eseguire anche `git push origin <branch>` e `git push origin vX.Y.0` (o `git push origin --tags` se servono più tag insieme).
- **Niente `git push --force`** mai.
- **Niente mock del DB** nei test feature — usare `RefreshDatabase` su MariaDB reale.
- **Niente import Excel** con dati reali in test — usare factory.
- **Niente `php artisan` da host** — solo via `./vendor/bin/sail artisan`.
- **Niente hard delete** di prenotazioni senza conferma esplicita (operazione riservata all'admin autenticato).
- **Niente modifica** di `DOCUMENTI PER LA PROGETTAZIONE/` — è materiale di progettazione, non codice.
