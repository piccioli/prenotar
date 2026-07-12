# PRD: CI/CD e Strategia di Branching per Prenotar (release, hotfix, backup DB)

## 1. Introduzione/Overview

Prenotar oggi ha solo il branch `main`, senza `develop`, e una singola pipeline GitHub Actions (`.github/workflows/ci.yml`) che esegue Pint + Larastan + Pest su ogni pull request e su push a `main`. Non esiste automazione di continuous delivery, non esiste un hook di pre-commit, e il flusso di hotfix in produzione non è formalizzato.

Questo PRD definisce la strategia di branching a tre livelli (pre-commit locale → PR verso develop → PR verso main), il deploy continuo automatico verso l'ambiente develop e verso produzione (con backup del DB come primo step), e il flusso di hotfix con relativo versionamento.

## 2. Goals

- Ogni commit locale passa tutti i test prima di essere accettato (pre-commit hook).
- Ogni PR verso `develop` e verso `main` esegue la suite QA completa via GitHub Actions.
- Ogni push su `develop` aggiorna automaticamente l'ambiente develop (build, migration, eventuali seeder).
- Ogni push su `main` esegue un backup del DB di produzione prima di migration/deploy, con possibilità di rollback dal dump.
- Gli hotfix su produzione seguono il flusso: branch da `main` → PR verso `main` + PR verso `develop` → bump di patch version.
- Il versionamento (major/minor) è deciso e taggato su `develop`, sincronizzato tramite tag git + `CHANGELOG.md`.

## 3. User Stories

### US-001: Hook di pre-commit locale
**Descrizione:** Come developer, voglio che al commit vengano lanciati automaticamente Pint, Larastan e Pest (`sail composer qa`), così da non poter commettere codice che rompe la QA.

**Criteri di accettazione:**
- [ ] Script di hook Git versionato nel repo (es. `.githooks/pre-commit`), attivabile con `git config core.hooksPath .githooks` (dato che `.git/hooks` non è versionabile)
- [ ] Il commit viene bloccato se `./vendor/bin/sail composer qa` fallisce
- [ ] Documentata in `CLAUDE.md`/README l'attivazione dell'hook su una nuova macchina di sviluppo

### US-002: Rinominare lo stack "staging" in "develop"
**Descrizione:** Come developer, voglio che tutti i riferimenti a "staging" nel repo vengano rinominati in "develop" (file, variabili, dominio), perché l'ambiente develop e lo stack staging esistente sono lo stesso ambiente — da ora in poi si usa solo la nomenclatura "develop".

**Criteri di accettazione:**
- [ ] `docker-compose.staging.yml` rinominato in `docker-compose.develop.yml`
- [ ] `.env.staging.example` rinominato in `.env.develop.example`, con `APP_ENV=develop`
- [ ] Variabili/prefissi rinominati coerentemente: `DB_DATABASE=prenotar_staging` → `prenotar_develop`, `CACHE_PREFIX=prenotar_staging_` → `prenotar_develop_`, `MAILPIT_UI_PORT` e commenti associati, ecc.
- [ ] `DEPLOY.md`: sezione "Ambiente staging (UAT)" rinominata in "Ambiente develop", comandi ed esempi Nginx aggiornati coerentemente
- [ ] `CHANGELOG.md`: aggiunta voce che documenta il rename
- [ ] Nessun riferimento residuo a "staging" nel repo (verifica con grep)
- [ ] Nuovo dominio pubblico `develop.prenotar.montagnaservizi.it` configurato (DNS + certificato TLS + vhost Nginx), in sostituzione di `staging.prenotar.montagnaservizi.it`

### US-003: Branch develop e CI su PR verso develop e main
**Descrizione:** Come developer, voglio che ogni PR verso `develop` o verso `main` esegua automaticamente Pint + Larastan + Pest via GitHub Actions, per non introdurre regressioni né nel branch di integrazione né in produzione.

**Criteri di accettazione:**
- [ ] Branch `develop` creato a partire da `main`
- [ ] `.github/workflows/ci.yml` continua a girare su ogni `pull_request` (il trigger attuale non è filtrato per branch target, quindi copre già PR verso develop e verso main) — verificato esplicitamente e documentato
- [ ] `CLAUDE.md`/README documentano che tutto lo sviluppo parte da `develop`, non da `main`

### US-004: Continuous Delivery automatico verso l'ambiente develop
**Descrizione:** Come developer/tester, voglio che ogni push su `develop` aggiorni automaticamente l'ambiente develop (build immagine, migration, eventuali seeder), per avere sempre una versione pubblica aggiornata da testare.

**Criteri di accettazione:**
- [ ] Nuovo workflow GitHub Actions (`cd-develop.yml`) triggerato su push a `develop`
- [ ] Il workflow gira su un self-hosted runner **dedicato all'ambiente develop** (runner separato da quello di produzione, entrambi registrati sulla stessa macchina fisica)
- [ ] Step: pull codice, build immagine, restart stack (`docker-compose.develop.yml`), `php artisan migrate --force`, eventuale `db:seed` se configurato
- [ ] Verifica manuale: push di test su `develop`, aggiornamento visibile sull'ambiente entro pochi minuti

### US-005: Continuous Delivery verso produzione con backup DB pre-deploy
**Descrizione:** Come admin tecnico, voglio che ogni push su `main` esegua un backup del database di produzione prima di qualunque migration/deploy, per poter fare rollback in caso di problemi.

**Criteri di accettazione:**
- [ ] Nuovo workflow GitHub Actions (`cd-production.yml`) triggerato su push a `main`
- [ ] Il workflow gira su un self-hosted runner **dedicato alla produzione** (separato da quello develop)
- [ ] Primo step: dump del DB MariaDB salvato con timestamp in una directory dedicata
- [ ] Retention: mantenuti solo gli **ultimi 5 dump locali**, i più vecchi rimossi automaticamente ad ogni nuovo backup — nessun upload esterno (S3) per ora
- [ ] Step successivi: build immagine, `migrate --force`, restart stack (nessuna modifica ai dati salvo quanto previsto dalle migration)
- [ ] Documentato in `DEPLOY.md` il comando di rollback manuale da un dump

### US-006: Flusso di hotfix su produzione
**Descrizione:** Come developer, voglio poter aprire un branch di hotfix direttamente da `main` per risolvere un bug urgente in produzione, senza aspettare il ciclo normale di release da `develop`.

**Criteri di accettazione:**
- [ ] Procedura documentata (`CLAUDE.md` o `CONTRIBUTING`): branch `hotfix/*` da `main` → fix → PR verso `main` (CI verde) → merge → CD produzione parte automaticamente
- [ ] Alla chiusura dell'hotfix viene aperta anche una PR verso `develop` per non perdere la fix nel flusso normale
- [ ] Il merge di un hotfix su `main` corrisponde sempre a un bump di patch version in `CHANGELOG.md` e a un nuovo tag git

### US-007: Versionamento su develop con tag + CHANGELOG
**Descrizione:** Come developer, voglio che ogni nuova versione (minor/major) sia decisa e taggata sul branch `develop`, poi promossa in produzione tramite PR verso `main`, per avere un'unica fonte di verità sul versionamento.

**Criteri di accettazione:**
- [ ] Procedura documentata: bump versione in `CHANGELOG.md` + tag git eseguiti su `develop` prima della PR verso `main`
- [ ] La PR `develop` → `main` non modifica il numero di versione (già deciso su `develop`)
- [ ] Le patch release (hotfix) sono l'unica eccezione: nascono e vengono taggate direttamente su `main` (vedi US-005), poi backportate su `develop`

### US-008: Setup iniziale macchina di produzione con ambienti develop + produzione puliti
**Descrizione:** Come admin tecnico, voglio ripartire da una macchina pulita (`root@116.203.88.140`, chiave SSH già autorizzata) rimuovendo i container Docker della vecchia installazione, per poi installare da zero prima l'ambiente develop e poi quello di produzione, entrambi raggiungibili pubblicamente, con i due runner GitHub Actions registrati.

**Criteri di accettazione:**
- [ ] Checklist in `DEPLOY.md` per la pulizia della macchina (rimozione container/volumi della vecchia installazione, con conferma esplicita richiesta prima di comandi distruttivi)
- [ ] Checklist per l'installazione pulita: develop prima, poi produzione
- [ ] Due self-hosted runner GitHub Actions installati e registrati sulla macchina (uno per develop, uno per produzione) come step della checklist
- [ ] Accesso alla macchina documentato: `root@116.203.88.140` via SSH a chiave (già autorizzata, nessuno step aggiuntivo di distribuzione chiave necessario)
- [ ] Elenco esplicito delle operazioni manuali richieste all'utente (DNS per `develop.prenotar.montagnaservizi.it`, certificati TLS, ecc.) con relative istruzioni

## 4. Functional Requirements

- FR-1: Un hook pre-commit locale deve eseguire la suite QA completa e bloccare il commit in caso di fallimento.
- FR-2: Ogni pull request (verso `develop` o verso `main`) deve eseguire Pint + Larastan + Pest via GitHub Actions.
- FR-3: Ogni push su `develop` deve triggerare un deploy automatico dell'ambiente develop, incluse le migration.
- FR-4: Ogni push su `main` deve triggerare, in ordine: backup DB produzione → migration → deploy.
- FR-5: I backup del DB di produzione devono avere retention fissa: ultimi 5 dump locali, nessun upload esterno.
- FR-6: Gli hotfix devono poter essere aperti da `main`, richiudersi con PR verso `main` E verso `develop`, e corrispondere sempre a un bump di patch version.
- FR-7: Le minor/major release devono essere decise e taggate su `develop`, non su `main`.
- FR-8: I workflow di CD devono girare su due self-hosted runner separati (uno per develop, uno per produzione), entrambi registrati sulla macchina `116.203.88.140`.
- FR-9: Tutti i riferimenti a "staging" nel repo (file, variabili, documentazione) devono essere rinominati in "develop".

## 5. Non-Goals (Out of Scope)

- Deploy multi-region o multi-server (tutto resta su una singola macchina condivisa develop+produzione).
- Sistema di alerting/notifiche (Slack/email) sugli esiti dei deploy.
- Migrazione/importazione una tantum del DB di produzione reale nell'ambiente develop (citata nella nota come "eventuale", non come requisito fermo).
- Semantic versioning automatico (bump resta manuale via CHANGELOG + tag).

## 6. Design Considerations

N/A — nessun impatto UI.

## 7. Technical Considerations

- Lo stack "staging" esistente (`docker-compose.staging.yml`, `staging.prenotar.montagnaservizi.it`) **è** l'ambiente develop: va rinominato (US-002), non ricostruito da zero. Il dominio pubblico cambia in `develop.prenotar.montagnaservizi.it`.
- I due runner self-hosted (develop e produzione) girano sulla stessa macchina fisica (`116.203.88.140`) ma vanno registrati come runner distinti (label diverse, es. `self-hosted-develop` / `self-hosted-production`) così i workflow `cd-develop.yml` e `cd-production.yml` restano isolati anche se condividono l'host.
- Accesso alla macchina: SSH come `root` su `116.203.88.140`, chiave pubblica già autorizzata — nessuna autenticazione a password, nessuno step di distribuzione chiave richiesto in checklist.
- I secret usati dai workflow CD (chiave SSH, credenziali DB, ecc.) devono essere gestiti come GitHub Actions secrets, mai committati.

## 8. Success Metrics

- Nessun commit con test falliti raggiunge il branch `develop` (bloccato da pre-commit + CI).
- Tempo tra push su `develop` e aggiornamento ambiente visibile: pochi minuti, senza intervento manuale.
- Ogni deploy in produzione ha un backup DB associato verificabile (log del workflow + file dump presente).
- Almeno un ciclo di hotfix completo (branch → PR main → PR develop → tag patch) eseguito con successo come prova.

## 9. Open Questions

Nessuna — tutte le domande aperte sono state risolte:
- Dominio develop: `develop.prenotar.montagnaservizi.it` (nuovo, in sostituzione di `staging.prenotar.montagnaservizi.it`).
- Accesso macchina: SSH come `root@116.203.88.140`, chiave già autorizzata.
