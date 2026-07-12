# Deploy in produzione (Prenotar)

La produzione è pensata per essere eseguita **interamente con Docker** (Nginx, PHP-FPM 8.4, MariaDB 10.11, Redis, worker code, scheduler). Lo stack è definito da `Dockerfile` (multi-stage) e `docker-compose.production.yml`.

Health check HTTP: `GET /up` (Laravel 11).

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
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml up -d
```

- **HTTP**: porta host `80` di default. Per cambiarla: `HTTP_PUBLISH=8080` nel `.env` o in shell prima di `up`.
- **TLS**: termina HTTPS davanti a questo stack (es. reverse proxy aziendale, Traefik, Caddy) oppure estendi il compose con un servizio che espone 443.
- **Dominio pubblico** (es. `https://prenotar.montagnaservizi.it`): il proxy esterno deve inoltrare verso la porta pubblicata dallo stack (default `80` su `HTTP_PUBLISH`) impostando `Host`, `X-Forwarded-Proto: https`, `X-Forwarded-For` e gli altri header previsti dalla tua infrastruttura. In `.env` usa `APP_URL=https://prenotar.montagnaservizi.it` e `TRUSTED_PROXIES=*` (o gli IP del proxy) come nel template.

Esempio sintetico **Nginx** (TLS gestito da questo server; upstream = stack Docker sulla porta host `80`):

```nginx
server {
    listen 443 ssl http2;
    server_name prenotar.montagnaservizi.it;
    # ssl_certificate /path/fullchain.pem;
    # ssl_certificate_key /path/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
    }
}
```

---

## 4. Prima installazione database

Dopo il primo `up` (con `APP_KEY` già presente nel `.env`):

```bash
docker compose -f docker-compose.production.yml exec app php artisan migrate --force
```

**Mai** `migrate:fresh` in produzione.

Opzionale: migrazioni automatiche ad ogni avvio del container `app` — imposta `AUTORUN_MIGRATIONS=1` nel `.env` (sconsigliato senza backup e revisione).

---

## 5. Servizi inclusi

| Servizio   | Ruolo |
|------------|--------|
| `nginx`    | Static da `public/`, FastCGI verso PHP-FPM |
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
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml up -d
docker compose -f docker-compose.production.yml exec app php artisan migrate --force
```

Se cambiano solo variabili in `.env`, dopo `up` conviene ricreare la cache nel container `app`:

```bash
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear
docker compose -f docker-compose.production.yml restart app
```

(`restart app` riesegue l’entrypoint con `AUTORUN_OPTIMIZE=1` di default.)

### CD automatico verso produzione

Ogni `push` sul branch `main` innesca il workflow `.github/workflows/cd-production.yml`, eseguito da un runner self-hosted con label `production` posizionato direttamente sulla macchina di produzione (nessuna connessione SSH nel workflow). Prima di qualunque build/migrate, il workflow esegue un **dump del database** (`mariadb-dump`) in `/var/backups/prenotar/`, con nome file `prenotar-<data>-<ora>.sql`, e applica una retention che mantiene solo gli ultimi 5 backup (i più vecchi vengono rimossi automaticamente). Solo dopo il backup il workflow esegue `build`, `up -d` e `migrate --force` sullo stack `docker-compose.production.yml`.

Per verificare l'esito: tab **Actions** del repository su GitHub, workflow "CD Production" → ultima esecuzione sul branch `main`.

### Rollback manuale da un backup

In caso di problemi dopo un deploy, ripristinare l'ultimo dump salvato prima del deploy:

```bash
docker compose -f docker-compose.production.yml exec -T mariadb sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < /var/backups/prenotar/prenotar-<data>-<ora>.sql
```

Sostituire `<data>-<ora>` con il timestamp del backup da cui ripristinare (`ls -t /var/backups/prenotar/` per individuare il più recente). Dopo il ripristino, riavviare lo stack applicativo per assicurarsi che cache e code ripartano allineate ai dati ripristinati:

```bash
docker compose -f docker-compose.production.yml restart app horizon scheduler
```

---

## 7. Log e diagnostica

```bash
docker compose -f docker-compose.production.yml logs -f app horizon scheduler nginx
docker compose -f docker-compose.production.yml exec app php artisan about
docker compose -f docker-compose.production.yml exec app php artisan horizon:status
```

Dashboard Horizon: accessibile solo agli admin su `/horizon`. Per un graceful restart di Horizon dopo deploy:

```bash
docker compose -f docker-compose.production.yml exec app php artisan horizon:terminate
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

- Subdominio `develop.prenotar.montagnaservizi.it` puntato all'IP server.
- File `.env.develop` valorizzato (copia da `.env.develop.example`).

### Configurazione reverse-proxy Nginx (develop)

```nginx
server {
    listen 443 ssl http2;
    server_name develop.prenotar.montagnaservizi.it;
    # ssl_certificate /path/fullchain.pem;
    # ssl_certificate_key /path/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
    }

    # UI Mailpit protetta da basic auth: consultazione email develop senza tunnel SSH.
    location /mailpit/ {
        auth_basic "Mailpit develop";
        auth_basic_user_file /etc/nginx/.htpasswd-mailpit-develop;

        proxy_pass http://127.0.0.1:${MAILPIT_UI_PORT:-8027}/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Il file `/etc/nginx/.htpasswd-mailpit-develop` **non va committato nel repo** (credenziali dedicate, distinte da quelle applicative). Va generato una tantum sul server:

```bash
htpasswd -c /etc/nginx/.htpasswd-mailpit-develop <utente>
```

Responsabilità: l'**admin tecnico** genera il file e, se necessario, ne ruota manualmente le credenziali — non esiste un processo di rotazione periodica automatica.

### Build e avvio

```bash
docker compose -f docker-compose.develop.yml --env-file .env.develop build
docker compose -f docker-compose.develop.yml --env-file .env.develop up -d
```

### Prima inizializzazione (UAT)

```bash
docker compose -f docker-compose.develop.yml --env-file .env.develop exec app php artisan migrate --force
docker compose -f docker-compose.develop.yml --env-file .env.develop exec app php artisan db:seed --class=LocalDevSeeder
```

Il `LocalDevSeeder` importa le 152 sezioni + 77 sottosezioni da Excel reale, imposta password `password` su tutti gli account, crea admin + GR dev e popola le impostazioni del presidente GR con firma e documento d'identità.

**Credenziali UAT**:
- Admin: `admin@local.test` / `password`
- GR: `gr@local.test` / `password`
- Sezioni: tutte con password `password` (email da Excel reale)

Mail interceptata da Mailpit — UI raggiungibile senza tunnel SSH su `https://develop.prenotar.montagnaservizi.it/mailpit/` (basic auth, vedi sopra), oppure direttamente su `http://127.0.0.1:8027` sul server.

### Aggiornamento develop

```bash
git pull
docker compose -f docker-compose.develop.yml --env-file .env.develop build
docker compose -f docker-compose.develop.yml --env-file .env.develop up -d
docker compose -f docker-compose.develop.yml --env-file .env.develop exec app php artisan migrate --force
```

### CD automatico verso develop

Ogni `push` sul branch `develop` innesca il workflow `.github/workflows/cd-develop.yml`, eseguito da un runner self-hosted con label `develop` posizionato direttamente sulla macchina develop (nessuna connessione SSH nel workflow). Il workflow esegue in sequenza gli stessi comandi della sezione "Aggiornamento develop" sopra: `build`, `up -d` e `migrate --force`. Il seeder (`db:seed`) **non** viene lanciato automaticamente: resta un'operazione manuale da eseguire quando serve reinizializzare i dati di test.

Per verificare l'esito: tab **Actions** del repository su GitHub, workflow "CD Develop" → ultima esecuzione sul branch `develop`.

### Reset completo develop

```bash
docker compose -f docker-compose.develop.yml --env-file .env.develop down -v
docker compose -f docker-compose.develop.yml --env-file .env.develop up -d
docker compose -f docker-compose.develop.yml --env-file .env.develop exec app php artisan migrate --force
docker compose -f docker-compose.develop.yml --env-file .env.develop exec app php artisan db:seed --class=LocalDevSeeder
```

**Mai** usare `down -v` sulla produzione — cancella tutti i dati.

---

## Sviluppo locale

Resta **Laravel Sail** (`./vendor/bin/sail`). Non usare `docker-compose.production.yml` per il day-to-day in locale.

---

## Deploy senza Docker (opzionale)

Se in futuro servisse una VM solo PHP+Nginx senza container, i comandi Artisan di ottimizzazione e le stesse variabili `.env` restano validi; in quel caso `DB_HOST` / `REDIS_HOST` tipicamente `127.0.0.1` e servono process manager per worker e cron per `schedule:run`.
