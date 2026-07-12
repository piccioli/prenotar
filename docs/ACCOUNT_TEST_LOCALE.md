# Accesso locale e account di test (Prenotar)

## Come accedere in locale

1. Avvia l'ambiente Sail dalla root del progetto:

   ```bash
   ./vendor/bin/sail up -d
   ```

2. Se una porta risulta già occupata da un altro progetto Docker (es. `3306` o `1025`), individua il container in conflitto e fermalo:

   ```bash
   docker ps --format 'table {{.Names}}\t{{.Ports}}' | grep -E '3306|1025'
   docker stop <nome-container-in-conflitto>
   ```

3. Verifica che i container siano tutti su (`laravel.test`, `mariadb`, `redis`, `horizon`, `mailpit`):

   ```bash
   docker ps --filter "name=prenotar" --format 'table {{.Names}}\t{{.Status}}'
   ```

4. URL locali:

   | Pannello | URL |
   |----------|-----|
   | Sezione/Sottosezione | http://localhost/sezione/login |
   | GR Lombardia | http://localhost/gr/login |
   | Admin | http://localhost/admin/login |
   | MailPit (mail intercettate) | http://localhost:8026 |

> `APP_URL` in `.env` locale è `http://localhost` — non usare domini `prenotar.develop.*`/`prenotar.montagnaservizi.com` in sviluppo: puntano (quando risolvono) all'ambiente remoto, non a questa istanza.

---

## Account di test (seed locale)

Gli account sotto sono quelli creati da `database/seeders/LocalDevSeeder.php`, eseguibile con:

```bash
./vendor/bin/sail artisan migrate:fresh --seed
# oppure, se le sezioni sono già importate:
./vendor/bin/sail artisan db:seed --class=LocalDevSeeder
```

**Password unica per tutti gli account: `password`**

### Admin

| Ruolo | Email | Password |
|-------|-------|----------|
| `admin` | `admin@local.test` | `password` |

### GR Lombardia

| Ruolo | Email | Password |
|-------|-------|----------|
| `gr_manager` | `gr@local.test` | `password` |

Presidente GR configurato nei settings di test: **Emilio Aldeghi** (nato a Lecco, 11/04/1958), con firma e documento d'identità precaricati.

### Sezioni (152 totali, ruolo `sezione`)

Tutte le email sono quelle reali importate dall'Excel di progettazione, con password reimpostata a `password`. Esempi utilizzabili:

| Sezione | Email |
|---------|-------|
| SEZ. ABBIATEGRASSO | `abbiategrasso@cai.it` |
| SEZ. ALBIATE | `albiate@cai.it` |
| SEZ. APRICA | `aprica@cai.it` |

### Sottosezioni (77 totali, stesso ruolo `sezione` ma con `sottosezione_id` valorizzato)

| Sottosezione | Email |
|--------------|-------|
| S.SEZ. BERBENNO | `infocaiberbenno@gmail.com` |
| S.SEZ. PONTE IN VALTELLINA | `9116109@grlomct.it` |
| S.SEZ. TEGLIO (diventata Sezione) | `9116143@grlomct.it` |

> Nota tecnica: nel DB di test non esiste un ruolo Spatie separato `sottosezione` — sia sezioni che sottosezioni hanno ruolo `sezione`; la distinzione è data dal campo `sottosezione_id` (valorizzato) vs `sezione_id`.

Per trovare altri account specifici (es. una sezione precisa), da tinker:

```bash
./vendor/bin/sail artisan tinker --execute="
echo App\Models\User::where('email', 'like', '%nome_sezione%')->value('email');
"
```

### Prenotazioni demo

Il seeder crea automaticamente alcune prenotazioni demo (prefisso nome `[DEV] `) visibili nel calendario, in stati diversi della state machine (Inviata, Approvata, PDF firmato, Assicurazione inviata), utili per testare viste e transizioni senza crearle manualmente.

---

## Riferimenti

- Roadmap fasi e stack tecnico: [`CLAUDE.md`](../CLAUDE.md)
- Deploy produzione/develop: [`DEPLOY.md`](../DEPLOY.md)
