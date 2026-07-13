# PRD: Semplificazione del modulo trasporto e vincolo data ritiro

## Introduzione/Overview

Nota di progetto del 13/07/2026 ("Semplificazione della logica di prenotazione e trasporto per le torri") richiede di semplificare drasticamente lo step "Logistica trasporto" del wizard di prenotazione (`/sezione`, `CreatePrenotazione`), oggi troppo ricco di campi opzionali che generano confusione e rischio (luoghi di ritiro/riconsegna, distinzione mezzo aziendale/privato con preset "Montagna Servizi", categoria patente condizionata al tipo di mezzo).

La nuova logica riduce il modulo trasporto a **due soli campi obbligatori** — nome del conducente e targa del veicolo — più un'**unica dichiarazione formale e sempre obbligatoria** di possesso patente di categoria **B+E** (idonea al traino del rimorchio, indipendentemente da chi trasporta), e introduce un vincolo temporale oggi assente: la data di ritiro della torre non può essere successiva alla data di inizio della prenotazione.

## Goals

- Ridurre lo step "Logistica trasporto" ai soli campi essenziali: nome conducente, targa autoveicolo, date di ritiro/riconsegna (già esistenti, invariate salvo il nuovo vincolo), dichiarazione patente B+E.
- Eliminare completamente: luogo ritiro, luogo riconsegna, distinzione mezzo aziendale/privato, preset azienda di trasporto "Montagna Servizi", selezione di categoria patente condizionata.
- Introdurre il vincolo `data_ritiro <= data_inizio_prenotazione`, con messaggio di errore esplicativo e blocco del salvataggio in caso di violazione.
- Rendere la dichiarazione patente B+E sempre obbligatoria (oggi lo è solo se si sceglie "mezzo privato"), con tracciabilità (timestamp di conferma).

## Non-Goals (Out of Scope)

- Nessuna modifica al vincolo di overlap sul calendario torri già esistente (`app/Rules/NoOverlapTorre.php`, basato su `data_inizio_prenotazione`/`data_fine_prenotazione`) — resta invariato, questo PRD aggiunge solo il nuovo vincolo su `data_ritiro`.
- Nessuna modifica al vincolo `data_riconsegna >= data_ritiro` già esistente lato GR (`ViewPrenotazione`, modale riassegnazione date).
- Non si introduce alcun canale/tool operativo per coordinare la logistica di ritiro/riconsegna in assenza dei campi "luogo" (la nota di progetto lo assegna a una SOP esterna del team Operations, fuori dallo scope tecnico di questo PRD).
- Non si tocca in alcun modo "Responsabile in loco" (step 5 del wizard): resta un contatto distinto e indipendente dal conducente, nessun collegamento o campo condiviso tra i due.
- Non si introduce alcuna verifica automatica/esterna della patente dichiarata (resta un'autodichiarazione, coerente con la decisione già presa in `prd-wizard-prenotazione-miglioramenti.md` di non verificare il "codice CAI").

## User Stories

### US-001: Migrazione — rimozione campi obsoleti, nuovi campi conducente e dichiarazione patente

**Descrizione:** Come sviluppatore, voglio una migrazione che rimuova le colonne non più necessarie e aggiunga i due nuovi campi (nome conducente, timestamp dichiarazione patente B+E), così da allineare lo schema alla nuova logica prima di modificare il form.

**Acceptance Criteria:**
- [ ] Nuova migrazione che droppa da `prenotazioni`: `luogo_ritiro`, `luogo_riconsegna`, `tipo_mezzo`, `azienda_trasporto`, `categoria_patente_privato` (drop diretto, non deprecazione — produzione non ha ancora prenotazioni reali).
- [ ] Nuova migrazione (o la stessa) che aggiunge: `nome_conducente` (string, nullable a livello DB, obbligatorio a livello di validazione form) e `patente_be_dichiarata_at` (timestamp, nullable).
- [ ] `app/Models/Prenotazione.php` aggiornato: `fillable` e `casts` coerenti con i campi rimossi/aggiunti; rimossi i cast verso `TipoMezzo`/`CategoriaPatente`.
- [ ] Se gli enum `App\Enums\TipoMezzo` e `App\Enums\CategoriaPatente` non sono più referenziati da nessun'altra parte del codice dopo questo PRD, rimuoverli; se restano referenziati altrove, lasciarli e segnalarlo nelle note.
- [ ] `sail composer qa` passa.

### US-002: Step "Logistica trasporto" semplificato nel wizard

**Descrizione:** Come presidente di sezione/sottosezione, nello step logistica del wizard voglio inserire solo il nome del conducente, la targa e le date di ritiro/riconsegna, confermando un'unica dichiarazione di possesso patente B+E, senza dover più scegliere tipo di mezzo o azienda di trasporto.

**Acceptance Criteria:**
- [ ] Rimossi dallo step: `luogo_ritiro`, `luogo_riconsegna`, `tipo_mezzo` (ToggleButtons), `azienda_trasporto`, `categoria_patente_privato` (Radio) e ogni testo/placeholder condizionato al tipo di mezzo.
- [ ] Campi rimasti/aggiunti nello step: `data_ritiro` (DatePicker, invariato), `data_riconsegna` (DatePicker, invariato), `targa_autoveicolo` (TextInput, invariato), **nuovo** `nome_conducente` (TextInput, obbligatorio), **nuova** checkbox unica di dichiarazione patente (obbligatoria, `accepted`).
- [ ] Testo della dichiarazione patente (checkbox): "Dichiaro, sotto la mia responsabilità, di essere in possesso di patente di guida di categoria B+E (o superiore), idonea al traino del rimorchio della torre di arrampicata, e che quanto dichiarato corrisponde al vero." — rivedibile/modificabile in futuro, ma presente e non ambiguo fin da subito.
- [ ] Alla conferma della checkbox, `patente_be_dichiarata_at` viene valorizzato con l'istante corrente; se la checkbox viene deselezionata, torna `null`.
- [ ] Nuova validazione: `data_ritiro` deve essere minore o uguale a `data_inizio_prenotazione`, con messaggio d'errore esplicativo (es. "La data di ritiro non può essere successiva alla data di inizio della prenotazione") che blocca l'avanzamento/salvataggio in caso di violazione.
- [ ] Nessuna regressione al vincolo di overlap già esistente su `data_inizio_prenotazione`/`data_fine_prenotazione` (`NoOverlapTorre`).
- [ ] Verify in browser using dev-browser skill.

### US-003: Aggiornamento PDF e viste di dettaglio (GR/Sezione) per i nuovi campi

**Descrizione:** Come Presidente GR o presidente di sezione, nei PDF generati (Richiesta parete, Modulo 3) e nelle viste di dettaglio prenotazione voglio vedere il nome del conducente e la dichiarazione patente al posto dei campi ormai rimossi, così da avere una documentazione coerente con il nuovo modulo trasporto.

**Acceptance Criteria:**
- [ ] `resources/views/pdf/richiesta_parete.blade.php` e `resources/views/pdf/modulo3.blade.php` non referenziano più `tipo_mezzo`, `categoria_patente_privato`, `azienda_trasporto`, `luogo_ritiro`, `luogo_riconsegna`; mostrano invece `nome_conducente`, targa (invariata), date ritiro/riconsegna (invariate) e un'indicazione che la patente B+E è stata dichiarata (con la data della dichiarazione).
- [ ] `app/Filament/Gr/Resources/PrenotazioneResource/Pages/ViewPrenotazione.php` e `app/Filament/Sezione/Resources/PrenotazioneResource/Pages/ViewPrenotazione.php` aggiornate allo stesso modo (rimossi i TextEntry per i campi eliminati, aggiunti quelli per i nuovi campi).
- [ ] Il modale di riassegnazione date lato GR (`data_ritiro`/`data_riconsegna`) resta funzionante e non referenzia campi rimossi.
- [ ] `sail composer qa` passa.

### US-004: Aggiornamento test esistenti sul modulo trasporto

**Descrizione:** Come sviluppatore, voglio riscrivere i test che coprivano la vecchia logica (tipo mezzo, categoria patente condizionata) con test sulla nuova logica semplificata, incluso il nuovo vincolo sulla data di ritiro.

**Acceptance Criteria:**
- [ ] `tests/Feature/Sezione/PrenotazioneTipoMezzoValidationTest.php` sostituito con test sui nuovi campi: `nome_conducente` obbligatorio, checkbox patente obbligatoria (con `patente_be_dichiarata_at` valorizzato solo se confermata), submit senza dichiarazione patente bloccato con messaggio esplicativo.
- [ ] Nuovo test: `data_ritiro` successiva a `data_inizio_prenotazione` blocca il salvataggio con messaggio esplicativo; `data_ritiro` uguale o precedente passa.
- [ ] Aggiornati (rimossi i riferimenti ai campi obsoleti) `tests/Feature/Sezione/PrenotazioneWizardCreateTest.php`, `tests/Feature/Gr/PrenotazioneChangeDatesTest.php`, `tests/Feature/Gr/PrenotazioneWorkflowFase4Test.php`, `tests/Feature/Jobs/SendReminderT2ggTest.php` (verificarne la compatibilità con lo schema aggiornato).
- [ ] `sail composer qa` passa sull'intera suite.

## Functional Requirements

- FR-1: Il sistema deve rimuovere dallo step "Logistica trasporto" i campi `luogo_ritiro`, `luogo_riconsegna`, `tipo_mezzo`, `azienda_trasporto`, `categoria_patente_privato`, e le relative colonne dal database.
- FR-2: Il sistema deve richiedere obbligatoriamente `nome_conducente` e `targa_autoveicolo` per procedere oltre lo step logistica.
- FR-3: Il sistema deve richiedere obbligatoriamente, indipendentemente da qualunque altra scelta, la conferma di un'unica dichiarazione di possesso patente categoria B+E, con timestamp di conferma persistito.
- FR-4: Il sistema deve validare che `data_ritiro <= data_inizio_prenotazione`, bloccando il salvataggio con un messaggio esplicativo in caso di violazione.
- FR-5: Tutti i punti dell'applicazione che leggono i campi rimossi (PDF, viste di dettaglio GR/Sezione) devono essere aggiornati per non referenziarli più, sostituendoli con `nome_conducente` e l'indicazione della dichiarazione patente.

## Design Considerations

- Riusare lo stile visivo già introdotto nel restyle per checkbox/dichiarazioni obbligatorie (coerente con il pattern del nuovo step "Manuale d'istruzioni").
- Il messaggio di errore sul vincolo `data_ritiro <= data_inizio_prenotazione` deve essere in linguaggio semplice e non tecnico, coerente con l'utenza del progetto (vedi `CLAUDE.md`, target utenti poco avvezzi al digitale).

## Technical Considerations

- File principale: `app/Filament/Sezione/Resources/PrenotazioneResource.php` (`wizardSteps()`, step "Logistica trasporto").
- Modello: `app/Models/Prenotazione.php` — nuovi campi `nome_conducente`, `patente_be_dichiarata_at`; rimossi `luogo_ritiro`, `luogo_riconsegna`, `tipo_mezzo`, `azienda_trasporto`, `categoria_patente_privato`.
- Migrazione: nuovo file in `database/migrations/`, non modificare le migrazioni esistenti già eseguite (`2026_05_10_200005_create_prenotazioni_table.php`, `2026_07_08_164918_add_tipo_mezzo_to_prenotazioni.php`).
- Enum da rimuovere se non più referenziati altrove: `App\Enums\TipoMezzo`, `App\Enums\CategoriaPatente`.
- File da aggiornare per i campi rimossi/aggiunti: `resources/views/pdf/richiesta_parete.blade.php`, `resources/views/pdf/modulo3.blade.php`, `app/Filament/Gr/Resources/PrenotazioneResource/Pages/ViewPrenotazione.php`, `app/Filament/Sezione/Resources/PrenotazioneResource/Pages/ViewPrenotazione.php`.
- Test da riscrivere/aggiornare: `tests/Feature/Sezione/PrenotazioneTipoMezzoValidationTest.php` (sostituzione completa), `tests/Feature/Sezione/PrenotazioneWizardCreateTest.php`, `tests/Feature/Gr/PrenotazioneChangeDatesTest.php`, `tests/Feature/Gr/PrenotazioneWorkflowFase4Test.php`, `tests/Feature/Jobs/SendReminderT2ggTest.php`.
- Nessun nuovo campo "conducente" collegato a `responsabile_*` (step 5): sono entità indipendenti, anche se nella pratica potrebbero essere la stessa persona.

## Success Metrics

- Nessuna prenotazione può essere salvata senza nome conducente, targa e dichiarazione patente B+E confermata.
- Nessuna prenotazione può essere salvata con data di ritiro successiva alla data di inizio prenotazione.
- Zero riferimenti residui nel codice ai campi rimossi (`grep` pulito su `luogo_ritiro`, `luogo_riconsegna`, `tipo_mezzo`, `azienda_trasporto`, `categoria_patente_privato` al di fuori delle migrazioni storiche).
- `sail composer qa` verde su tutta la suite dopo la modifica.

## Open Questions

Nessuna al momento — le decisioni principali (dichiarazione patente B+E unica e fissa, rimozione completa di azienda_trasporto, drop diretto delle colonne, testo legale proposto in questo PRD) sono state prese prima della stesura.
