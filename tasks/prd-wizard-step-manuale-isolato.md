# PRD: Step manuale d'istruzioni isolato nel wizard di prenotazione

## Introduzione/Overview

Nel wizard "Nuova prenotazione" (`/sezione`, `CreatePrenotazione`), la conferma di lettura del manuale d'istruzioni della torre è oggi implementata dentro il primo step "Quando & dove" (`app/Filament/Sezione/Resources/PrenotazioneResource.php::wizardSteps()`), **accoppiata alla scelta della torre**: se l'utente seleziona "Nessuna preferenza" invece di una torre specifica, la conferma di lettura non viene richiesta affatto (vedi `tests/Feature/Sezione/PrenotazioneManualeLettoTest.php`, caso "no torre selezionata → non richiesta"). Questo introdotto originariamente in `prd-wizard-prenotazione-miglioramenti.md` (US-004), che lo aveva progettato come vincolo per-torre.

Poiché entrambe le torri esistenti condividono lo stesso identico manuale d'istruzioni (stesso PDF caricato per entrambe in `TorriSeeder`), questo PRD **isola la conferma di lettura in un primo step dedicato**, sempre obbligatorio, indipendente da quale torre verrà scelta più avanti (o se non ne verrà scelta nessuna). Solo dopo aver confermato la lettura si procede allo step "Quando e dove" (che diventa il secondo step).

## Goals

- Un primo step del wizard dedicato esclusivamente alla conferma di lettura del manuale, sempre presente e sempre obbligatorio.
- Nessuna possibilità di saltare la conferma scegliendo "nessuna preferenza" per la torre.
- Rimozione della logica di reset "cambio torre → azzera conferma", non più necessaria perché la conferma non dipende più dalla torre scelta.
- Nessuna interruzione del flusso di prenotazione se il dato "manuale caricato" manca lato Admin (avviso, non blocco).

## Non-Goals (Out of Scope)

- Nessuna modifica alla gestione dei manuali PDF per torre lato Admin (`TorreResource`, campo `manuale_pdf_path`) — si continua a leggere il campo già esistente.
- Nessuna nuova migrazione: le colonne `manuale_letto_confermato_at` e `manuale_letto_torre_id` su `prenotazioni` esistono già.
- Nessuna modifica alla logica di scelta/assegnazione torre nello step "Quando e dove" (radio `torre_id`, "nessuna preferenza", validazione overlap) oltre alla rimozione dell'effetto collaterale sul flag manuale.
- Non si introduce un campo "manuale generico" separato dalle torri: si riusa il manuale della prima torre attiva esistente, dato che il contenuto è identico per le due torri di oggi.

## User Stories

### US-001: Nuovo step isolato "Manuale d'istruzioni" come primo step del wizard

**Descrizione:** Come presidente di sezione/sottosezione, all'apertura del wizard di nuova prenotazione voglio un primo step dedicato che mi chieda di confermare la lettura del manuale d'istruzioni, indipendentemente da quale torre sceglierò poi, così da non poter saltare questo controllo di sicurezza scegliendo "nessuna preferenza".

**Acceptance Criteria:**
- [ ] Nuovo primo step del wizard (gli step successivi si rinumerano: 1. Manuale d'istruzioni, 2. Quando e dove, 3. Evento, 4. Logistica trasporto, 5. Responsabile in loco, 6. Riepilogo).
- [ ] Lo step mostra il link al manuale d'istruzioni (PDF) e una checkbox "Ho letto e compreso il manuale d'istruzioni", obbligatoria per procedere.
- [ ] Il manuale mostrato è quello della prima torre attiva (per `id`) tra quelle esistenti con `manuale_pdf_path` valorizzato — indifferente quale, dato che il contenuto è identico per le torri attuali.
- [ ] Se nessuna torre attiva ha un manuale caricato, lo step mostra un avviso "manuale non ancora disponibile" ma **non blocca** l'avanzamento (la checkbox resta comunque obbligatoria per procedere, semplicemente senza link scaricabile).
- [ ] Il pulsante "Continua" dello step resta disabilitato finché la checkbox non è spuntata, indipendentemente da quale torre verrà scelta nello step successivo.
- [ ] Verify in browser using dev-browser skill.

### US-002: Rimozione dell'accoppiamento manuale↔torre nello step "Quando e dove"

**Descrizione:** Come sviluppatore, voglio rimuovere dallo step "Quando e dove" la checkbox e la logica di reset legate alla torre selezionata, perché la conferma di lettura ora avviene una sola volta all'inizio del wizard, indipendentemente dalla torre che verrà scelta.

**Acceptance Criteria:**
- [ ] Rimossi dallo step "Quando e dove": `Checkbox::make('manuale_letto_confirm')`, i campi nascosti duplicati e l'`afterStateUpdated` sul `Radio::make('torre_id')` che azzerava i campi manuale al cambio torre.
- [ ] La selezione torre (`torre_id`, incluso "nessuna preferenza") resta identica nello step "Quando e dove" in tutto il resto (validazione overlap, card torre con indirizzo deposito) — cambia solo l'assenza di effetti sui campi manuale.
- [ ] Nessuna regressione alla validazione `NoOverlapTorre` già esistente sul campo `torre_id`.
- [ ] `sail composer qa` passa.

### US-003: Persistenza della conferma lettura manuale non più torre-scoped

**Descrizione:** Come sviluppatore, voglio adattare la persistenza dei campi `manuale_letto_*` per riflettere che è ora una conferma unica valida per l'intera prenotazione, non più legata alla torre scelta più avanti nel wizard.

**Acceptance Criteria:**
- [ ] `manuale_letto_confermato_at` viene valorizzato al momento della conferma nel nuovo primo step (non più nello step "Quando e dove").
- [ ] `manuale_letto_torre_id` viene valorizzato con l'id della torre di riferimento usata per mostrare il manuale in quel momento (la prima torre attiva con manuale caricato), non con la torre eventualmente scelta più avanti nel wizard — resta `null` se nessuna torre attiva ha un manuale.
- [ ] `mutateFormDataBeforeCreate` (o equivalente) aggiornato di conseguenza, senza introdurre nuove colonne.
- [ ] `sail composer qa` passa.

### US-004: Aggiornamento test esistenti sul flusso manuale/wizard

**Descrizione:** Come sviluppatore, voglio riscrivere `tests/Feature/Sezione/PrenotazioneManualeLettoTest.php` per coprire il nuovo comportamento (conferma sempre obbligatoria fin dal primo step, indipendente dalla torre scelta), rimuovendo gli scenari non più validi.

**Acceptance Criteria:**
- [ ] Test: submit senza aver confermato la checkbox nel primo step → bloccato, indipendentemente dalla torre scelta poi (incluso "nessuna preferenza") — sostituisce lo scenario "nessuna torre → conferma non richiesta", ora invalido.
- [ ] Test: conferma nel primo step + qualunque torre (o nessuna) scelta nello step successivo → salvataggio riuscito con `manuale_letto_confermato_at` valorizzato.
- [ ] Test: nessuna torre attiva ha un manuale caricato → lo step mostra l'avviso ma permette comunque di procedere confermando la checkbox.
- [ ] Rimosso/sostituito il test "cambio torre resetta la conferma" (non più applicabile: la conferma non dipende più dalla torre).
- [ ] Verificata la compatibilità di `tests/Feature/Sezione/PrenotazioneWizardCreateTest.php` (conta gli step del wizard: "ha il wizard con i 5 step" → ora diventano 6) e aggiornato se necessario.
- [ ] `sail composer qa` passa sull'intera suite.

## Functional Requirements

- FR-1: Il wizard di creazione prenotazione deve avere un primo step dedicato alla conferma di lettura del manuale d'istruzioni, sempre presente e sempre obbligatorio per procedere.
- FR-2: Il manuale mostrato in quello step deve provenire dalla prima torre attiva (ordinata per `id`) che abbia `manuale_pdf_path` valorizzato.
- FR-3: Se nessuna torre attiva ha un manuale caricato, lo step deve mostrare un avviso esplicito ma non deve bloccare l'avanzamento del wizard.
- FR-4: La scelta della torre nello step "Quando e dove" (incluso "nessuna preferenza") non deve più avere alcun effetto sui campi `manuale_letto_confermato_at`/`manuale_letto_torre_id`.
- FR-5: `manuale_letto_torre_id`, quando valorizzato, deve riferirsi alla torre di riferimento mostrata nel primo step, non alla torre eventualmente scelta più avanti.

## Design Considerations

- Riusare lo stesso pattern visivo già introdotto nel restyle per il primo step del wizard (icona, badge, card di avviso stile "larch" per il messaggio informativo), applicandolo al nuovo step dedicato invece che al blocco manuale dentro "Quando e dove".
- Il messaggio "manuale non ancora disponibile" (caso torre senza manuale caricato) deve restare visivamente coerente con gli altri avvisi/warning già presenti nel wizard.

## Technical Considerations

- File principale: `app/Filament/Sezione/Resources/PrenotazioneResource.php` (`wizardSteps()`), da riorganizzare aggiungendo il nuovo step in testa all'array e ripulendo lo step "Quando e dove" esistente.
- `app/Filament/Sezione/Resources/PrenotazioneResource/Pages/CreatePrenotazione.php`: verificare/aggiornare `$torreSenzaManualeConfermato` (o logica equivalente di gating del pulsante "Continua"), spostandola dal riferimento a `torre_id` a un gate sul nuovo step basato solo sulla checkbox.
- Vista condivisa desktop/mobile: `resources/views/filament/sezione/forms/components/prenotazione-wizard.blade.php` — nessuna vista mobile separata da mantenere sincronizzata (stesso blade per entrambi).
- Vista checkbox esistente da riusare/adattare: `resources/views/filament/sezione/forms/components/manuale-checkbox.blade.php`.
- Modello coinvolto: `app/Models/Prenotazione.php` (campi già `fillable`, nessuna nuova migrazione).
- Test da riscrivere: `tests/Feature/Sezione/PrenotazioneManualeLettoTest.php`; da verificare (conteggio step): `tests/Feature/Sezione/PrenotazioneWizardCreateTest.php`.
- Questo PRD **supersede** la parte "conferma per-torre" di `tasks/prd-wizard-prenotazione-miglioramenti.md` (US-004) — quel documento resta come riferimento storico della decisione originale, non va modificato retroattivamente.

## Success Metrics

- Nessuna prenotazione può essere creata senza aver confermato la lettura del manuale, indipendentemente dalla torre scelta (incluso "nessuna preferenza").
- Zero blocchi totali del wizard per assenza di manuale caricato: in quel caso si procede comunque con avviso.
- `sail composer qa` verde su tutta la suite dopo la modifica.

## Open Questions

Nessuna al momento — l'unica decisione aperta (comportamento in assenza di manuale su tutte le torri) è stata presa: procedere comunque con avviso, non bloccare.
