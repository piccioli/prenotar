# PRD: Login unico con redirect automatico al pannello corretto

## Introduzione/Overview

Oggi Prenotar ha 3 pannelli Filament (`/admin`, `/gr`, `/sezione`), e ciascuno registra la propria pagina di login Filament stock (`/admin/login`, `/gr/login`, `/sezione/login`). I 3 pannelli condividono già lo stesso guard di autenticazione (`web`, nessun guard dedicato per pannello — confermato da `tests/Feature/Auth/PanelAccessTest.php`, che riusa la stessa sessione `actingAs($user)` su tutti e 3 gli URL senza un nuovo login). L'unica differenza tra pannelli è l'**autorizzazione**, non l'autenticazione: `App\Models\User::canAccessPanel()` decide, in base al ruolo (`isAdmin()`/`isGrManager()`/`isSezione()`) e allo stato `is_active`, se l'utente può vedere quel pannello.

Questo significa che un utente deve già sapere in anticipo quale delle 3 URL usare per accedere — non intuitivo per un'utenza di volontari/dirigenti associativi poco avvezzi al digitale (vedi `CLAUDE.md`, target utenti). Questo PRD introduce un **login unico** su `/login`, con branding neutro CAI GR Lombardia, che dopo l'autenticazione reindirizza automaticamente al pannello corretto in base al ruolo dell'utente, riusando la logica di ruolo già esistente su `User`.

## Goals

- Un solo punto di ingresso (`/login`) per tutti gli utenti, indipendentemente dal ruolo.
- Redirect automatico post-login al pannello corretto (`/admin`, `/gr`, `/sezione`) senza scelta manuale da parte dell'utente.
- Le vecchie URL di login per-pannello (`/admin/login`, `/gr/login`, `/sezione/login`) continuano a funzionare per chi le ha salvate nei bookmark, reindirizzando al login unico.
- Nessuna regressione alla sessione condivisa tra pannelli già esistente, né al flusso `FirstAccessPage` (completamento email di contatto al primo accesso).

## Non-Goals (Out of Scope)

- Nessuna modifica alla logica di autorizzazione (`canAccessPanel()`, ruoli, `is_active`) — resta la stessa, viene solo letta anche subito dopo il login invece che solo al momento dell'accesso al pannello.
- Nessun cambiamento al comportamento per utenti con account disattivato o senza ruolo valido: stesso messaggio di errore già esistente oggi.
- Nessuna modifica al flusso `FirstAccessPage` (contatto email al primo accesso) — deve continuare a funzionare invariato dopo il redirect al pannello corretto.
- Nessun single sign-on esterno (SAML/OAuth) — resta autenticazione email+password locale.
- Non si gestisce in questo PRD il caso (oggi inesistente nei dati) di un utente con più ruoli contemporaneamente tra Admin/GR/Sezione — se emergesse, va trattato come bug separato, non come requisito di questo PRD.

## User Stories

### US-001: Pagina di login unica con branding neutro

**Descrizione:** Come utente di uno qualsiasi dei 3 ruoli, voglio una pagina di login unica raggiungibile su `/login`, con branding istituzionale CAI GR Lombardia (non colorato come uno specifico pannello), così da non dover ricordare quale URL di pannello usare per accedere.

**Acceptance Criteria:**
- [ ] Nuova pagina di login raggiungibile su `/login`, non associata visivamente a nessuno dei 3 pannelli (niente colore ambra/verde/ardesia introdotto in US-001 del restyle).
- [ ] Branding: logo/nome CAI GR Lombardia, palette neutra (es. i toni "stone" del design system condiviso).
- [ ] Il form (email, password, "ricordami", messaggi di errore, throttling dei tentativi) mantiene tutte le funzionalità del componente Filament `Login` stock — nessuna funzionalità persa nel passaggio a pagina condivisa.
- [ ] Un utente già autenticato che visita `/login` viene reindirizzato al proprio pannello invece di rivedere il form (comportamento standard, da verificare non regredisca).
- [ ] Typecheck/Larastan passa.
- [ ] Verify in browser using dev-browser skill.

### US-002: Redirect post-login basato sul ruolo

**Descrizione:** Come utente autenticato su `/login`, voglio essere reindirizzato automaticamente al pannello corretto in base al mio ruolo (Admin, GR Manager, Sezione/Sottosezione), così da atterrare subito sulla vista giusta senza dover scegliere manualmente.

**Acceptance Criteria:**
- [ ] Dopo login riuscito: redirect a `/admin` se `isAdmin()`, a `/gr` se `isGrManager()`, a `/sezione` se `isSezione()`.
- [ ] La logica di mapping ruolo→pannello riusa gli stessi metodi già esistenti su `User` (nessuna nuova logica di ruolo duplicata rispetto a `canAccessPanel()`).
- [ ] Se l'utente non ha nessuno dei 3 ruoli, o l'account ha `is_active = false`, il comportamento resta quello già esistente oggi in `canAccessPanel()` (messaggio di errore chiaro, nessun redirect a un pannello inaccessibile o a una pagina bianca).
- [ ] Nessuna regressione alla sessione condivisa tra pannelli già verificata da `PanelAccessTest`: un utente autenticato può ancora navigare direttamente a un altro pannello per cui è autorizzato, senza un nuovo login.
- [ ] Il flusso `FirstAccessPage` (completamento email di contatto al primo accesso) continua a scattare correttamente dopo il redirect, se applicabile all'utente.
- [ ] Test Feature per ciascuno dei 3 ruoli che verifica il redirect al pannello corretto dopo login su `/login`.
- [ ] Typecheck/Larastan passa.

### US-003: Redirect delle vecchie URL di login per-pannello

**Descrizione:** Come utente con un bookmark o link salvato a `/admin/login`, `/gr/login` o `/sezione/login`, voglio essere reindirizzato automaticamente al nuovo login unico, così da non trovare un link rotto dopo l'introduzione del login unificato.

**Acceptance Criteria:**
- [ ] Le 3 vecchie URL rispondono con un redirect (302) verso `/login`, non con un 404 né con un form di login duplicato.
- [ ] Nessuna funzionalità di autenticazione resta raggiungibile/utilizzabile direttamente alle vecchie URL (niente doppio punto di ingresso).
- [ ] Test Feature che verifica il redirect 302 per tutte e 3 le vecchie URL verso `/login`.
- [ ] Typecheck/Larastan passa.

### US-004: Aggiornamento test esistenti sull'architettura di login

**Descrizione:** Come sviluppatore, voglio aggiornare i test che oggi assumono 3 pagine di login indipendenti, così da riflettere intenzionalmente la nuova architettura di login unico senza lasciare test falliti o silenziosamente obsoleti.

**Acceptance Criteria:**
- [ ] `tests/Feature/Auth/LoginBrandingTest.php` aggiornato: verifica il branding neutro di `/login` invece delle 3 pagine colorate separate per pannello.
- [ ] `tests/Feature/Auth/PanelAccessTest.php` aggiornato/esteso: continua a verificare che la sessione condivisa tra pannelli funzioni, e copre esplicitamente il nuovo redirect post-login per ciascun ruolo (o rimanda a US-002 se già coperto lì, evitando duplicazione).
- [ ] Nessun test esistente nella suite rimane rotto per riferimenti a comportamento di login ormai cambiato intenzionalmente.
- [ ] `sail composer qa` passa (Pint + Larastan + Pest) sull'intera suite.

## Functional Requirements

- FR-1: Il sistema deve esporre un'unica pagina di login su `/login`, con branding neutro CAI GR Lombardia, riusando il form/componenti standard di Filament (email, password, ricordami, throttling).
- FR-2: Dopo un'autenticazione riuscita su `/login`, il sistema deve reindirizzare l'utente al pannello corretto (`/admin`, `/gr`, `/sezione`) in base al primo ruolo valido restituito da `isAdmin()`/`isGrManager()`/`isSezione()` su `User`.
- FR-3: Le URL `/admin/login`, `/gr/login`, `/sezione/login` devono rispondere con un redirect 302 verso `/login`, non generare più un form di login autonomo.
- FR-4: Il comportamento per utenti senza ruolo valido o con `is_active = false` deve restare invariato rispetto a quello già gestito oggi da `canAccessPanel()`.
- FR-5: La sessione condivisa già esistente tra i 3 pannelli (stesso guard `web`) non deve subire regressioni: un utente autenticato resta autenticato su tutti i pannelli per cui è autorizzato.

## Design Considerations

- Branding della pagina `/login`: logo CAI GR Lombardia, palette neutra "stone" del design system condiviso (`tasks/design-reference/prenotar-ui-ruoli/tokens/colors.css`), non uno dei 3 colori pannello (ambra/verde-pino/ardesia) introdotti in US-001 del restyle Filament.
- Il form stesso (campi, validazione, messaggi di errore) deve restare visivamente e funzionalmente quello che Filament fornisce di default — l'obiettivo è unificare il punto di ingresso, non ridisegnare la UX del form di login.

## Technical Considerations

- File coinvolti oggi: `app/Providers/Filament/{Admin,Gr,Sezione}PanelProvider.php` (ciascuno chiama `->login()` senza argomenti, generando la pagina di login Filament stock sul proprio path).
- Mapping ruolo→pannello già esistente e da riusare: `App\Models\User::canAccessPanel(Panel $panel)` (metodi `isAdmin()`/`isGrManager()`/`isSezione()`, guardia `is_active`).
- Nessun guard dedicato per pannello oggi (`config/auth.php` ha un solo guard `web`, usato implicitamente da tutti e 3 i `PanelProvider`) — questo è ciò che rende possibile un login condiviso senza toccare l'autenticazione.
- `app/Filament/Pages/FirstAccessPage.php` è il flusso post-login di completamento email di contatto, oggi indipendente dal login: verificare che continui a scattare correttamente dopo il nuovo redirect basato sul ruolo.
- Test da aggiornare/estendere: `tests/Feature/Auth/LoginBrandingTest.php`, `tests/Feature/Auth/PanelAccessTest.php`, `tests/Feature/Auth/FirstAccessFlowTest.php` (verificarne la compatibilità, non necessariamente modificarlo).
- Punto di implementazione lasciato aperto all'implementatore: se realizzarlo come pagina di login Filament dedicata a un "panel" neutro, o come route/controller Laravel standalone che riusa i componenti Livewire di Filament — l'importante è il comportamento osservabile descritto nelle user story, non una specifica architettura di codice.
- Questo branch (`ralph/login-unificato`) è stato creato sopra `ralph/restyle-ux-pannelli-filament` (non ancora mergiato in `develop`), perché il branding neutro di `/login` riusa i design token e il tema Filament introdotti in quel lavoro (`tasks/design-reference/prenotar-ui-ruoli/tokens/colors.css`, `resources/css/filament/**`).

## Success Metrics

- Un utente può autenticarsi da `/login` indipendentemente dal proprio ruolo e atterra sempre sul pannello corretto senza azioni manuali aggiuntive.
- Zero link rotti per chi usa le vecchie URL di login per-pannello (redirect 302 funzionante su tutte e 3).
- `sail composer qa` verde su tutta la suite dopo il merge, incluso l'aggiornamento dei test di autenticazione esistenti.

## Open Questions

Nessuna al momento — le decisioni principali (URL del login unico, branding neutro, redirect 302 delle vecchie URL) sono state prese prima di scrivere questo PRD.
