# Deploy in produzione (Prenotar)

La produzione è pensata per essere eseguita **interamente con Docker** (Nginx, PHP-FPM 8.4, MariaDB 10.11, Redis, worker code, scheduler). Lo stack è definito da `Dockerfile` (multi-stage) e `docker-compose.production.yml`.

Health check HTTP: `GET /up` (Laravel 11).

---

## 0. Setup iniziale macchina (root@116.203.88.140)

> **Solo documentazione**: quanto segue è una checklist **manuale** per l'admin tecnico. Nessuno script del repository né alcun comando eseguito da un agente automatico si connette a `root@116.203.88.140` — resta sempre un'azione umana deliberata sul server reale.

Accesso: SSH come `root@116.203.88.140`, chiave pubblica già autorizzata (nessuno step di distribuzione chiave necessario).

1. **Pulizia della vecchia installazione** — ⚠️ **distruttivo, da confermare esplicitamente prima di procedere** (rimuove container e volumi, quindi tutti i dati esistenti sulla macchina):
   ```bash
   docker compose -p prenotar -f docker-compose.production.yml down -v
   docker system prune -a --volumes   # solo dopo aver verificato che non ci siano dati da conservare
   ```
2. **Creazione dei volumi condivisi per i certificati TLS** (usati da entrambi gli stack, vedi §3 "HTTPS con Certbot"):
   ```bash
   docker volume create prenotar_shared_certbot_conf
   docker volume create prenotar_shared_certbot_www
   ```
3. **Installazione stack produzione** (deve partire per prima: pubblica le porte 80/443 necessarie anche alla validazione del certificato develop): segue le sezioni 1-6 di questo file (`docker-compose.production.yml` + `.env`, dominio `prenotar.montagnaservizi.com`).
4. **Installazione stack develop**: segue la checklist della sezione "Ambiente develop (UAT)" più sotto in questo file (`docker-compose.develop.yml` + `.env.develop`, dominio `prenotar.develop.montagnaservizi.com`, porta HTTPS `8443`).
5. **Registrazione dei due self-hosted runner GitHub Actions**, con le label usate dai workflow esistenti (`develop` per `.github/workflows/cd-develop.yml`, `production` per `.github/workflows/cd-production.yml` — vedi §6 "CD automatico verso produzione" e "CD automatico verso develop"):
   ```bash
   # Runner develop (directory dedicata, es. /opt/actions-runner-develop)
   ./config.sh --url https://github.com/<org>/prenotar --token <TOKEN> --labels develop --name prenotar-develop
   ./svc.sh install && ./svc.sh start

   # Runner produzione (directory separata, es. /opt/actions-runner-production)
   ./config.sh --url https://github.com/<org>/prenotar --token <TOKEN> --labels production --name prenotar-production
   ./svc.sh install && ./svc.sh start
   ```

**Operazioni manuali richieste all'utente** (non eseguibili da un agente automatico):
- Puntamento DNS per `prenotar.montagnaservizi.com` e `prenotar.develop.montagnaservizi.com` verso l'IP del server.
- Emissione/rinnovo dei certificati TLS per entrambi i domini (`scripts/certbot-certonly.sh` / `scripts/certbot-renew.sh`, vedi §3).
- Verifica che la chiave SSH sia già autorizzata su `root@116.203.88.140` (nessuno step di distribuzione chiave necessario).

---

## 1. Prerequisiti sul server

- Docker Engine e Docker Compose plugin (v2).
- File `.env` nella root del repository (vedi sotto): **non committare** segreti.

---

## 2. Variabili d’ambiente (`.env`)

1. Copia il template: `cp .env.production.example .env`
2. Imposta almeno:
   - `APP_KEY` (`php artisan key:generate --show` in locale, oppure `docker compose ... run --rm app php artisan key:generate --show` dopo il primo build).
   - `APP_DEBUG=false`, `APP_URL` con **https** se il TLS è davanti allo stack (reverse proxy o CDN).
   - `TRUSTED_PROXIES=*` se c’è un proxy esterno che termina TLS o inoltra header `X-Forwarded-*`.
   - `DB_PASSWORD`, `DB_ROOT_PASSWORD` (root solo per il container MariaDB), `DB_DATABASE`, `DB_USERNAME` (devono coincidere con `MARIADB_*` nel compose — stessi valori in `.env`).
   - Posta: `docker-compose.production.yml` non include più Mailpit — invio reale via **SMTP Gmail**. Valori nel template: `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`, `MAIL_USERNAME`/`MAIL_PASSWORD` (app password Gmail dedicata, o relay SMTP Google Workspace).
     **BLOCCATO**: credenziali SMTP Gmail non ancora disponibili, responsabile e data non assegnati — il mailer va lasciato configurato come sopra ma non è attivabile (invio fallirà) finché `MAIL_USERNAME`/`MAIL_PASSWORD` non vengono valorizzate. La verifica end-to-end dell'invio reale resta un passo manuale futuro, da eseguire solo dopo la consegna delle credenziali.
3. Con **solo** Docker Compose i host DB/Redis sono già `mariadb` e `redis` (vedi `.env.production.example`).
4. **Allegati**: `FILESYSTEM_DISK=local` persiste sotto `storage/app` nel volume `app_storage`. Per S3 valorizza `AWS_*` (vedi anche `config/filesystems.php`).

---

## 3. Build e avvio

Dalla root del progetto:

```bash
docker compose -p prenotar -f docker-compose.production.yml build
docker compose -p prenotar -f docker-compose.production.yml up -d
```

- **HTTP**: porta host `80` di default. Per cambiarla: `HTTP_PUBLISH=8080` nel `.env` o in shell prima di `up`.
- **TLS**: gestito **direttamente dal container Nginx** dello stack, con **Certbot** (Let's Encrypt) — nessun reverse proxy esterno a livello host. Vedi sezione seguente.
- **Dominio pubblico**: `https://prenotar.montagnaservizi.com`. In `.env` usa `APP_URL=https://prenotar.montagnaservizi.com` e `SESSION_SECURE_COOKIE=true` (già nel template).

### HTTPS con Certbot (Nginx nello stack)

Prerequisiti: record **DNS** (A/AAAA) del dominio verso il server; porte host **80** e **443** raggiungibili da Internet (per la validazione HTTP-01 di Let's Encrypt); volumi condivisi `prenotar_shared_certbot_conf` / `prenotar_shared_certbot_www` già creati (§0, punto 2).

> Solo la **produzione** pubblica le porte 80/443 dell'host. Il dominio develop (`prenotar.develop.montagnaservizi.com`, porta 8443) condivide lo stesso volume/webroot: la sua validazione ACME passa comunque da qui — vedi sezione "Ambiente develop" più sotto.

1. Nel `.env` valorizza almeno `CERTBOT_DOMAIN=prenotar.montagnaservizi.com`, `CERTBOT_EMAIL`, `APP_URL=https://prenotar.montagnaservizi.com`, `SESSION_SECURE_COOKIE=true`.
2. Build e avvio: `docker compose -p prenotar -f docker-compose.production.yml build && docker compose -p prenotar -f docker-compose.production.yml up -d` (Nginx espone `80` e `443` e serve `/.well-known/acme-challenge/` dal volume `certbot_www`).
3. **Prima emissione** del certificato di produzione (dalla root del repo):

   ```bash
   ./scripts/certbot-certonly.sh prenotar.montagnaservizi.com tua-email@esempio.it
   ```

   Equivalente manuale:

   ```bash
   docker compose -p prenotar -f docker-compose.production.yml --profile tools run --rm certbot certonly \
     --webroot --webroot-path=/var/www/certbot \
     -d "prenotar.montagnaservizi.com" --email "tua-email@esempio.it" \
     --agree-tos --non-interactive
   CERTBOT_DOMAIN=prenotar.montagnaservizi.com docker compose -p prenotar -f docker-compose.production.yml up -d --force-recreate nginx
   ```

   Dopo il primo certificato, l'entrypoint di Nginx abilita il **redirect HTTP→HTTPS** per `CERTBOT_DOMAIN` e il **virtual host TLS** (certificati in sola lettura da `certbot_conf`). Il redirect usa `CERTBOT_PUBLIC_HTTPS_PORT` (= `HTTPS_PUBLISH`, es. `8443` per develop) per puntare alla porta pubblica corretta — senza, lo stack develop redirigerebbe alla porta 443 (occupata dalla produzione, dominio/certificato sbagliati).

4. **Rinnovo** (Let's Encrypt, ~90 giorni): cron sul server, ad esempio due volte al giorno:

   ```bash
   0 3,15 * * * cd /percorso/prenotar && ./scripts/certbot-renew.sh >>/var/log/prenotar-certbot.log 2>&1
   ```

   `certbot-renew.sh` rinnova **entrambi** i certificati (produzione e develop, stesso volume condiviso) e ricarica i Nginx di entrambi gli stack (quello develop solo se attivo).

Il servizio Compose `certbot` usa il profilo **`tools`** e non parte con `up` di default; serve solo per `docker compose ... run --rm certbot`.

---

## 4. Prima installazione database

Dopo il primo `up` (con `APP_KEY` già presente nel `.env`):

```bash
docker compose -p prenotar -f docker-compose.production.yml exec app php artisan migrate --force
```

**Mai** `migrate:fresh` in produzione.

Opzionale: migrazioni automatiche ad ogni avvio del container `app` — imposta `AUTORUN_MIGRATIONS=1` nel `.env` (sconsigliato senza backup e revisione).

---

## 5. Servizi inclusi

| Servizio   | Ruolo |
|------------|--------|
| `nginx`    | Static da `public/`, FastCGI verso PHP-FPM; TLS 443 + Certbot webroot |
| `certbot`  | Immagine ufficiale (profilo `tools`): `certonly` / `renew` sui volumi condivisi `certbot_*` |
| `app`      | `php-fpm`, cache config/route/view/event + `filament:optimize` all’avvio (disattivabile con `AUTORUN_OPTIMIZE=0`) |
| `horizon`  | `php artisan horizon` — gestisce le queue Redis (sostituisce `queue:work`). Dashboard su `/horizon` (solo admin). |
| `scheduler`| `php artisan schedule:work` — cron notturno `prenotazioni:nightly` (archiviazione + reminder) alle 05:00 Europe/Rome |
| `mariadb`  | Dati in volume `mariadb_data` |
| `redis`    | Code, sessioni, cache; volume `redis_data` (AOF) |

Allegati e file privati medialibrary: volume **`app_storage`** montato su `storage/app` per `app`, `horizon` e `scheduler`.

---

## 6. Aggiornamento a una nuova versione

```bash
git pull   # o desplieg artifact
docker compose -p prenotar -f docker-compose.production.yml build
docker compose -p prenotar -f docker-compose.production.yml up -d
docker compose -p prenotar -f docker-compose.production.yml exec app php artisan migrate --force
```

Se cambiano solo variabili in `.env`, dopo `up` conviene ricreare la cache nel container `app`:

```bash
docker compose -p prenotar -f docker-compose.production.yml exec app php artisan optimize:clear
docker compose -p prenotar -f docker-compose.production.yml restart app
```

(`restart app` riesegue l’entrypoint con `AUTORUN_OPTIMIZE=1` di default.)

### CD automatico verso produzione

Ogni `push` sul branch `main` innesca il workflow `.github/workflows/cd-production.yml`, eseguito da un runner self-hosted con label `production` posizionato direttamente sulla macchina di produzione (nessuna connessione SSH nel workflow). Prima di qualunque build/migrate, il workflow esegue un **dump del database** (`mariadb-dump`) in `/var/backups/prenotar/`, con nome file `prenotar-<data>-<ora>.sql`, e applica una retention che mantiene solo gli ultimi 5 backup (i più vecchi vengono rimossi automaticamente). Solo dopo il backup il workflow esegue `build`, `up -d` e `migrate --force` sullo stack `docker-compose.production.yml`.

Per verificare l'esito: tab **Actions** del repository su GitHub, workflow "CD Production" → ultima esecuzione sul branch `main`.

### Rollback manuale da un backup

In caso di problemi dopo un deploy, ripristinare l'ultimo dump salvato prima del deploy:

```bash
docker compose -p prenotar -f docker-compose.production.yml exec -T mariadb sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < /var/backups/prenotar/prenotar-<data>-<ora>.sql
```

Sostituire `<data>-<ora>` con il timestamp del backup da cui ripristinare (`ls -t /var/backups/prenotar/` per individuare il più recente). Dopo il ripristino, riavviare lo stack applicativo per assicurarsi che cache e code ripartano allineate ai dati ripristinati:

```bash
docker compose -p prenotar -f docker-compose.production.yml restart app horizon scheduler
```

---

## 7. Log e diagnostica

```bash
docker compose -p prenotar -f docker-compose.production.yml logs -f app horizon scheduler nginx
docker compose -p prenotar -f docker-compose.production.yml exec app php artisan about
docker compose -p prenotar -f docker-compose.production.yml exec app php artisan horizon:status
```

Dashboard Horizon: accessibile solo agli admin su `/horizon`. Per un graceful restart di Horizon dopo deploy:

```bash
docker compose -p prenotar -f docker-compose.production.yml exec app php artisan horizon:terminate
# Il container `horizon` si riavvia automaticamente (restart: unless-stopped)
```

---

## 8. Build immagine (solo CI / registry)

Immagine applicativa (PHP-FPM):

```bash
docker build --target app -t prenotar-app:0.4.0 .
```

Immagine Nginx (solo `public/` + config):

```bash
docker build --target nginx -t prenotar-nginx:0.4.0 .
```

---

## 9. Verifiche post-deploy

- `curl -fsS http://<host>/up` → 200.
- Login `/admin`, `/gr`, `/sezione` con account reali (nessun account di sviluppo `*@local.test` in prod).
- Mail di prova (SMTP Gmail — **bloccato** finché le credenziali non sono disponibili, vedi §2); job in coda processati dai log di `queue`.

---

## 10. Sicurezza

- `APP_DEBUG=false` in produzione.
- Backup periodici del volume `mariadb_data` e di `app_storage` (o bucket S3 se in uso).
- Ruota `APP_KEY` solo consapevolmente (implica invalidazione sessioni/cifratura esistente).

---

## Ambiente develop (UAT)

Stack Docker separato sullo stesso host del server produzione. Volumi, porte e database distinti — no interferenza con prod.

### Prerequisiti

- Subdominio `prenotar.develop.montagnaservizi.com` puntato all'IP server (stesso IP della produzione).
- File `.env.develop` valorizzato (copia da `.env.develop.example`).
- Stack **produzione già attivo** (pubblica le porte 80/443 necessarie alla validazione ACME, vedi sotto) e volumi condivisi `prenotar_shared_certbot_conf` / `prenotar_shared_certbot_www` già creati (§0).

### HTTPS (Certbot condiviso con la produzione)

Non esiste un Nginx a livello host: ogni stack termina TLS nel proprio container Nginx. Poiché solo la produzione pubblica le porte 80/443, la validazione HTTP-01 per il dominio develop passa dal webroot condiviso dello stack produzione — nessuna configurazione aggiuntiva lato Nginx, solo il volume `certbot_conf` condiviso (già cablato in `docker-compose.develop.yml`).

**Prima emissione** del certificato develop (dalla root del repo, con la produzione già `up`):

```bash
./scripts/certbot-certonly.sh prenotar.develop.montagnaservizi.com tua-email@esempio.it develop
```

Lo script richiede il certificato passando dal webroot di produzione, poi ricrea il Nginx **develop** (`CERTBOT_DOMAIN=prenotar.develop.montagnaservizi.com`) per applicarlo. Il sito develop risulta raggiungibile su `https://prenotar.develop.montagnaservizi.com:8443`.

Il **rinnovo** è cumulativo con quello di produzione: vedi `scripts/certbot-renew.sh` in §3.

**Mailpit**: nessuna UI esposta pubblicamente via Nginx (non c'è un host-proxy davanti agli stack). Consultare la UI via tunnel SSH:

```bash
ssh -L 8027:127.0.0.1:8027 root@116.203.88.140
# poi apri http://127.0.0.1:8027 in locale
```

### Build e avvio

```bash
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop build
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop up -d
```

### Prima inizializzazione (UAT)

```bash
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec app php artisan migrate --force
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec app php artisan db:seed --force
```

`db:seed` (senza `--class`) esegue `DatabaseSeeder`, che lancia in ordine `RolesAndPermissionsSeeder` → `TorriSeeder` → `AdminDevSeeder` → `LocalDevSeeder`. Quest'ultimo importa le 152 sezioni + 77 sottosezioni da Excel reale, imposta password `password` su tutti gli account, crea admin + GR dev e popola le impostazioni del presidente GR con firma e documento d'identità. **Non** lanciare `db:seed --class=LocalDevSeeder` da solo: dipende dai ruoli creati da `RolesAndPermissionsSeeder` e fallisce (o importa utenti senza ruolo) se questi non esistono già.

**Credenziali UAT**:
- Admin: `admin@local.test` / `password`
- GR: `gr@local.test` / `password`
- Sezioni: tutte con password `password` (email da Excel reale)

Mail intercettata da Mailpit — UI raggiungibile solo via tunnel SSH (vedi sopra) o direttamente su `http://127.0.0.1:8027` sul server.

### Aggiornamento develop

```bash
git pull
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop build
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop up -d
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec app php artisan migrate --force
```

### CD automatico verso develop

Ogni `push` sul branch `develop` innesca il workflow `.github/workflows/cd-develop.yml`, eseguito da un runner self-hosted con label `develop` posizionato direttamente sulla macchina develop (nessuna connessione SSH nel workflow). Il workflow esegue in sequenza gli stessi comandi della sezione "Aggiornamento develop" sopra: `build`, `up -d` e `migrate --force`. Il seeder (`db:seed`) **non** viene lanciato automaticamente: resta un'operazione manuale da eseguire quando serve reinizializzare i dati di test.

Per verificare l'esito: tab **Actions** del repository su GitHub, workflow "CD Develop" → ultima esecuzione sul branch `develop`.

### Reset completo develop

```bash
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop down -v
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop up -d
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec app php artisan migrate --force
docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec app php artisan db:seed --force
```

**Mai** usare `down -v` sulla produzione — cancella tutti i dati.

---

## Sviluppo locale

Resta **Laravel Sail** (`./vendor/bin/sail`). Non usare `docker-compose.production.yml` per il day-to-day in locale.

---

## Deploy senza Docker (opzionale)

Se in futuro servisse una VM solo PHP+Nginx senza container, i comandi Artisan di ottimizzazione e le stesse variabili `.env` restano validi; in quel caso `DB_HOST` / `REDIS_HOST` tipicamente `127.0.0.1` e servono process manager per worker e cron per `schedule:run`.
