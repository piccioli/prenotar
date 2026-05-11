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
   - Posta: con `docker-compose.production.yml` è incluso **Mailpit** (cattura SMTP, nessun invio rete). Valori consigliati nel template: `MAIL_HOST=mailpit`, `MAIL_PORT=1025`, `MAIL_SCHEME=null`. Interfaccia web su `http://127.0.0.1:8025` sul server (porta sovrascrivibile con `MAILPIT_UI_PORT` nel compose); per SMTP reale in futuro sostituire `MAIL_*` e rimuovere il servizio `mailpit` dal compose se non serve più.
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
| `queue`    | `php artisan queue:work redis` |
| `scheduler`| `php artisan schedule:work` |
| `mariadb`  | Dati in volume `mariadb_data` |
| `redis`    | Code, sessioni, cache; volume `redis_data` (AOF) |
| `mailpit`  | SMTP di sviluppo/cattura (porta 1025 interna); UI su `127.0.0.1:8025` (host) |

Allegati e file privati medialibrary: volume **`app_storage`** montato su `storage/app` per `app`, `queue` e `scheduler`.

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

---

## 7. Log e diagnostica

```bash
docker compose -f docker-compose.production.yml logs -f app queue scheduler nginx
docker compose -f docker-compose.production.yml exec app php artisan about
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
- Mail di prova; job in coda processati dai log di `queue`.

---

## 10. Sicurezza

- `APP_DEBUG=false` in produzione.
- Backup periodici del volume `mariadb_data` e di `app_storage` (o bucket S3 se in uso).
- Ruota `APP_KEY` solo consapevolmente (implica invalidazione sessioni/cifratura esistente).

---

## Sviluppo locale

Resta **Laravel Sail** (`./vendor/bin/sail`). Non usare `docker-compose.production.yml` per il day-to-day in locale.

---

## Deploy senza Docker (opzionale)

Se in futuro servisse una VM solo PHP+Nginx senza container, i comandi Artisan di ottimizzazione e le stesse variabili `.env` restano validi; in quel caso `DB_HOST` / `REDIS_HOST` tipicamente `127.0.0.1` e servono process manager per worker e cron per `schedule:run`.
