# Prenotar

**Prenotar — Prenotazione torri di arrampicata GR Lombardia**

Sistema di prenotazione delle torri di arrampicata mobili CityWall del CAI Gruppo Regionale Lombardia, realizzato da [Montagna Servizi scpa](https://www.montagnaservizi.it).

## Avvio rapido

```bash
# 1. Clona il repository
git clone <url-repo> prenotar && cd prenotar

# 2. Copia le variabili d'ambiente
cp .env.example .env

# 3. Avvia l'ambiente Docker (app + MariaDB + MailPit + Redis)
./vendor/bin/sail up -d

# 4. Genera chiave applicazione e applica le migrazioni con dati di sviluppo
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed

# 5. Verifica
open http://localhost        # App Laravel
open http://localhost:8025   # MailPit (mail dev)
```

> **Reset completo** (ri-applica migrazioni e ri-importa tutti i dati):
> ```bash
> ./vendor/bin/sail artisan migrate:fresh --seed
> ```

## Account di sviluppo

Dopo `migrate:fresh --seed` sono disponibili questi account (tutti con password `password`):

| Ruolo | Email | Pannello |
|---|---|---|
| Admin | `admin@local.test` | `http://localhost/admin` |
| GR Lombardia | `gr@local.test` | `http://localhost/gr` |
| Sezione (es. Abbiategrasso) | `abbiategrasso@cai.it` | `http://localhost/sezione` |

Il seeder importa automaticamente tutte le 152 sezioni e 77 sottosezioni CAI Lombardia
dall'Excel di progettazione (se presente in `DOCUMENTI PER LA PROGETTAZIONE/`) e imposta
`password` come password su tutti i 230 account.

## QA

```bash
./vendor/bin/sail composer qa        # lint + static analysis + test
./vendor/bin/sail composer pint:fix  # applica fix stile codice
```

## Documentazione tecnica

- **Guida operativa**: [`CLAUDE.md`](./CLAUDE.md) — stack, comandi, convenzioni, mappa pannelli, roadmap fasi.
- **Piano di realizzazione**: `DOCUMENTI PER LA PROGETTAZIONE/PIANO_REALIZZAZIONE.md` (locale, non versionato).

## Tecnologie principali

Laravel 11 · Filament 3 · MariaDB 10.11 · Livewire 3 · Redis · Docker/Sail · Pest · Larastan

---

*© Montagna Servizi scpa — Uso riservato CAI GR Lombardia*
