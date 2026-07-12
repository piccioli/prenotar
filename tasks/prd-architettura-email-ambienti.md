# PRD: Architettura Email per Ambienti (locale / develop / produzione)

## 1. Introduzione/Overview

Prenotar oggi gestisce la posta in modo diverso a seconda dell'ambiente: in locale (Sail) usa MailPit senza problemi; lo stack "staging" esistente (`docker-compose.staging.yml`) include MailPit ma la UI è raggiungibile solo via tunnel SSH (bind `127.0.0.1`); lo stack di produzione (`docker-compose.production.yml`, `.env.production.example`) è oggi configurato anch'esso con MailPit come "cattura" — nessuna email raggiunge davvero i destinatari.

Questo PRD copre la configurazione infrastrutturale della posta nei tre ambienti (locale, develop, produzione): dove gira MailPit, come è raggiungibile, e come la produzione passa a un invio reale via SMTP Gmail. Non tratta il contenuto/template delle email.

## 2. Goals

- MailPit consultabile via browser sia in locale sia nell'ambiente develop, senza tunnel SSH.
- Produzione invia email reali tramite SMTP Gmail, non più catturate da MailPit.
- Accesso a MailPit su develop protetto da basic auth.
- Nessuna regressione sul comportamento locale già funzionante (Sail + MailPit, UI porta 8026).

## 3. User Stories

### US-001: Esporre MailPit UI su develop via sotto-URL
**Descrizione:** Come developer/tester, voglio consultare le email inviate dall'ambiente develop tramite un sotto-URL dell'app (es. `https://<dominio-develop>/mailpit`), senza dover aprire un tunnel SSH.

**Criteri di accettazione:**
- [ ] Nginx instrada `/mailpit` verso l'interfaccia web del container MailPit dello stack develop
- [ ] L'accesso a `/mailpit` richiede basic auth (credenziali dedicate, non quelle applicative)
- [ ] Documentato in `DEPLOY.md` come configurare e raggiungere l'endpoint
- [ ] Verifica manuale: invio email di test dall'app develop, ricezione visibile su `/mailpit` con login richiesto

### US-002: Basic auth per MailPit su develop
**Descrizione:** Come admin tecnico, voglio che l'interfaccia MailPit su develop sia protetta da credenziali dedicate, per non esporre pubblicamente il contenuto delle email (anche se develop non contiene dati reali).

**Criteri di accettazione:**
- [ ] Credenziali basic auth configurabili via variabile d'ambiente, non hardcoded
- [ ] Config Nginx con `auth_basic` sulla location `/mailpit`
- [ ] Documentata la procedura di generazione/rotazione password (es. `htpasswd`)
- [ ] Responsabilità di generazione e rotazione assegnata all'admin tecnico (Alessio); nessun processo di rotazione periodica automatica, solo rotazione manuale se necessario

### US-003: Passaggio produzione a SMTP Gmail reale
**Descrizione:** Come GR manager, voglio che le email di notifica (approvazione, Modulo 3, reminder) vengano effettivamente recapitate ai destinatari in produzione, non catturate da MailPit.

**Criteri di accettazione:**
- [ ] `.env.production.example` aggiornato: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`, placeholder per `MAIL_USERNAME`/`MAIL_PASSWORD`
- [ ] `docker-compose.production.yml`: servizio `mailpit` rimosso o reso opzionale, dato che non serve più in produzione
- [ ] `DEPLOY.md` aggiornato con le istruzioni per configurare le credenziali SMTP Gmail
- [ ] Verifica manuale: invio di una mail reale di test con credenziali Gmail configurate, ricezione confermata su una casella reale

### US-004: Allineare la configurazione mail al rename staging → develop
**Descrizione:** Come developer, voglio che la configurazione MailPit di questo PRD sia applicata al file risultante dal rename `docker-compose.staging.yml` → `docker-compose.develop.yml` (deciso nel PRD CI/CD — vedi `prd-cicd-branching-strategy.md`, US-002), così da non lavorare su un file che sta per essere rinominato.

**Criteri di accettazione:**
- [ ] Le modifiche a MailPit (sotto-URL, basic auth) vengono applicate su `docker-compose.develop.yml`/`.env.develop.example` (non più su `docker-compose.staging.yml`)
- [ ] `MAIL_FROM_ADDRESS` e URL di MailPit aggiornati al dominio definitivo `develop.prenotar.montagnaservizi.it` (es. `https://develop.prenotar.montagnaservizi.it/mailpit`)

## 4. Functional Requirements

- FR-1: In locale, la configurazione mail resta invariata (Sail + MailPit, UI porta 8026).
- FR-2: Nell'ambiente develop, MailPit UI deve essere raggiungibile via HTTP(S) pubblico su un sotto-URL dell'app, protetto da basic auth.
- FR-3: Nell'ambiente produzione, il mailer deve essere SMTP reale (Gmail), non più MailPit.
- FR-4: Le credenziali SMTP di produzione devono essere gestite solo via variabili d'ambiente (`.env`), mai committate.
- FR-5: `DEPLOY.md` deve riflettere la configurazione aggiornata per tutti e tre gli ambienti.

## 5. Non-Goals (Out of Scope)

- Contenuto/testo dei template email (Blade/Markdown) — non trattato qui.
- Provider mail diverso da Gmail SMTP per la produzione.
- Monitoring/alerting sulla mancata consegna delle email.

## 6. Design Considerations

- Riutilizzare lo schema di reverse-proxy Nginx già documentato in `DEPLOY.md` per staging/produzione.
- Basic auth via file `.htpasswd` generato manualmente sul server, nessuna UI di gestione dedicata.

## 7. Technical Considerations

- Verificare i limiti di invio di Gmail SMTP standard (500 email/giorno per account) rispetto al volume atteso (152 sezioni + 77 sottosezioni + GR Lombardia).
- Se il volume rischia di superare i limiti, valutare Google Workspace SMTP relay (nessun limite giornaliero, richiede IP autorizzato) — vedi Open Questions.
- `docker-compose.production.yml` include oggi un servizio `mailpit` di cattura: va rimosso o reso condizionale per evitare ambiguità.

## 8. Success Metrics

- 100% delle email applicative in produzione recapitate al provider SMTP reale (nessuna finisce più su MailPit).
- MailPit develop raggiungibile e autenticato, verificato da almeno un test manuale end-to-end.

## 9. Open Questions

- **Ancora aperta:** le credenziali SMTP Gmail (app password o Workspace relay) non sono ancora disponibili e non è stato assegnato un responsabile/una data — US-003 non può essere completata fino a quando non vengono fornite. Da monitorare come blocco esterno al di fuori del controllo del team.

**Risolte:**
- L'ambiente "develop" è lo stack "staging" esistente, rinominato (vedi `prd-cicd-branching-strategy.md`, US-002), dominio `develop.prenotar.montagnaservizi.it`.
- Rotazione basic auth MailPit: responsabilità dell'admin tecnico (Alessio), nessuna rotazione periodica automatica.
