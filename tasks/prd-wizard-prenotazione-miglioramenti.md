# PRD: Miglioramenti al wizard di creazione prenotazione

## Introduzione/Overview

Durante la sessione di test guidato del 11/05/2026 con CAI GR Lombardia (Valentino Poli, Enrico Sala/Renato), sono emersi quattro problemi puntuali nel wizard "Nuova prenotazione" del pannello `/sezione`:

1. Una sezione che ha già una prenotazione attiva scopre il blocco solo alla fine della compilazione del form, dopo aver inserito tutti i dati — spreco di tempo e frustrazione.
2. Il form del responsabile logistico assume sempre un mezzo aziendale (`azienda_trasporto` di default "montagna servizi"); non è possibile dichiarare un mezzo privato con la relativa patente.
3. Il campo "codice CAI" del responsabile (riportato in trascrizione come "codice KAI") non è verificabile da nessuno e genera solo falsa sicurezza sui dati.
4. Non esiste un controllo che garantisca che chi prenota abbia letto il manuale d'istruzioni/uso della torre prima di procedere — causa telefonate di supporto per problemi già coperti dal manuale.

Questo PRD raggruppa le quattro correzioni perché toccano lo stesso componente (`CreatePrenotazione`, pannello `/sezione`) e la stessa sessione di lavoro utente.

## Goals

- Impedire la compilazione inutile del wizard quando la sezione ha già una prenotazione attiva.
- Permettere la dichiarazione di un mezzo privato (con categoria patente B o B+E) in alternativa al mezzo aziendale.
- Eliminare il campo "codice CAI" del responsabile, non verificabile e privo di valore informativo.
- Garantire che la sezione dichiari esplicitamente di aver letto il manuale d'istruzioni della torre scelta prima di proseguire nel wizard.

## User Stories

### US-001: Blocco anticipato per prenotazione già attiva

**Descrizione:** Come presidente di sezione/sottosezione, quando clicco "Nuova prenotazione" e ho già una prenotazione attiva (bozza o inviata, non ancora conclusa/annullata), voglio essere avvisato subito e non poter accedere al wizard, così da non perdere tempo a compilare un form che verrà comunque rifiutato.

**Acceptance Criteria:**
- [ ] Il controllo "esiste già una prenotazione attiva per questa sezione/sottosezione" viene eseguito PRIMA di renderizzare il primo step del wizard (non più solo al salvataggio finale).
- [ ] Se il controllo fallisce, viene mostrato un messaggio esplicativo (es. "Hai già una prenotazione attiva: [nome evento], dal [data] al [data]. Non puoi crearne un'altra finché non viene conclusa o annullata.") con link diretto a quella prenotazione.
- [ ] L'utente non vede alcun campo del form in questo caso — nessuno step è raggiungibile.
- [ ] Il controllo esistente lato salvataggio finale resta invariato come ulteriore rete di sicurezza (difesa in profondità, es. contro doppie tab aperte).
- [ ] Test Feature che verifica: utente con prenotazione attiva → accesso a `CreatePrenotazione` blocca/reindirizza con messaggio; utente senza prenotazioni attive → wizard accessibile normalmente.
- [ ] Verify in browser using dev-browser skill.

### US-002: Dichiarazione mezzo privato con categoria patente

**Descrizione:** Come responsabile logistico di una prenotazione, voglio poter dichiarare che il trasporto avverrà con un mezzo privato (anziché aziendale), indicando la categoria di patente richiesta (B o B+E), così da riflettere la situazione reale del trasporto.

**Acceptance Criteria:**
- [ ] Nello step logistica/trasporto del wizard, è selezionabile "Tipo mezzo": Aziendale (Montagna Servizi) | Privato.
- [ ] Se "Aziendale" è selezionato, il comportamento e i campi restano quelli attuali (`azienda_trasporto` precompilato, `targa_autoveicolo` opzionale).
- [ ] Se "Privato" è selezionato, diventa visibile e **obbligatorio** il campo "Categoria patente": B | B+E.
- [ ] Il salvataggio dello step è bloccato finché, in caso di mezzo privato, la categoria patente non è stata scelta (validazione form).
- [ ] Il dato è persistito e visibile nel riepilogo prenotazione e nella vista di dettaglio lato GR e Admin.
- [ ] Migrazione DB per i nuovi campi (`tipo_mezzo` enum, `categoria_patente_privato` enum nullable) senza rompere le prenotazioni esistenti (default `tipo_mezzo = aziendale` per i record storici).
- [ ] Test Feature su creazione prenotazione con mezzo privato senza categoria patente → validazione fallisce; con categoria patente → salvataggio riuscito.
- [ ] Verify in browser using dev-browser skill.

### US-003: Rimozione campo "codice CAI" del responsabile

**Descrizione:** Come sviluppatore, voglio rimuovere il campo `responsabile_codice_cai` (form e dati), perché durante il test è stato esplicitamente segnalato come non verificabile e senza valore ("può mettere qualsiasi numero, non c'è modo di controllarlo").

**Acceptance Criteria:**
- [ ] Il campo "Codice CAI" non è più presente nello step responsabile del wizard di creazione/modifica prenotazione.
- [ ] Migrazione che rimuove la colonna `responsabile_codice_cai` dalla tabella `prenotazioni`.
- [ ] Rimosso da `$fillable`, da eventuali viste di dettaglio/riepilogo, PDF generati (Richiesta parete, Modulo 3) e da qualunque export.
- [ ] Verificato che nessun PDF template (`resources/views/pdf/...`) referenzi ancora il campo — build/test non deve fallire per riferimento a colonna inesistente.
- [ ] Test Feature aggiornati che non referenzino più il campo rimosso.
- [ ] Typecheck/Larastan passa (nessun riferimento residuo a `responsabile_codice_cai`).

### US-004: Flag obbligatorio "ho letto il manuale d'istruzioni" della torre

**Descrizione:** Come presidente di sezione/sottosezione, prima di poter proseguire nel wizard di prenotazione, voglio (essere obbligato a) confermare di aver letto il manuale d'istruzioni della torre che sto scegliendo, così da ridurre le richieste di supporto per problemi già documentati.

**Acceptance Criteria:**
- [ ] Il flag è **per-torre**: riguarda la torre effettivamente selezionata nello step di scelta torre, non un manuale generico.
- [ ] Il manuale mostrato/linkato è quello già presente sul modello `Torre` (campo `manuale_pdf_path`); se una torre non ha un manuale caricato, il flag è comunque visibile ma con messaggio "manuale non ancora disponibile" (da definire con l'admin, non blocca lo sviluppo).
- [ ] Nello step della torre è presente un link/pulsante per visualizzare/scaricare il manuale della torre selezionata, accanto alla checkbox "Ho letto e compreso il manuale d'istruzioni".
- [ ] Il pulsante "Avanti" dello step è disabilitato finché la checkbox non è spuntata ("Se non flagghi non avanti", come confermato nel test).
- [ ] Se l'utente cambia torre dopo aver flaggato, il flag si resetta (deve essere ri-confermato per la nuova torre selezionata).
- [ ] Il flag confermato viene salvato collegato alla prenotazione (per audit: chi ha confermato, quando, per quale torre).
- [ ] Test Feature: step torre non avanzabile senza flag; avanzabile con flag; cambio torre resetta il flag.
- [ ] Verify in browser using dev-browser skill.

## Functional Requirements

- FR-1: Il sistema deve verificare l'esistenza di una prenotazione attiva (stato non in `Annullata` o `Conclusa`) per la sezione/sottosezione dell'utente PRIMA di mostrare il form di creazione, non solo al salvataggio.
- FR-2: Il sistema deve aggiungere un campo `tipo_mezzo` (enum: `aziendale` | `privato`, default `aziendale`) alla tabella `prenotazioni`.
- FR-3: Quando `tipo_mezzo = privato`, il sistema deve richiedere obbligatoriamente un campo `categoria_patente_privato` (enum: `B` | `BE`).
- FR-4: Il sistema deve rimuovere completamente il campo `responsabile_codice_cai` da form, modello, migrazioni, viste, PDF e test.
- FR-5: Il sistema deve mostrare, nello step di selezione torre del wizard, il manuale d'istruzioni (`manuale_pdf_path`) della torre selezionata con una checkbox di conferma lettura obbligatoria per procedere.
- FR-6: Il flag di conferma lettura manuale deve essere specifico per torre selezionata e deve resettarsi se l'utente cambia torre nello stesso wizard.
- FR-7: Tutte le nuove validazioni bloccanti (prenotazione attiva, categoria patente, flag manuale) devono produrre messaggi d'errore in italiano, coerenti con il resto dell'interfaccia.

## Non-Goals (Out of Scope)

- Non si introduce alcuna verifica automatica/esterna della validità della patente dichiarata (resta un'autodichiarazione, coerente con la decisione di rimuovere il codice CAI per lo stesso motivo).
- Non si gestisce in questo PRD l'upload della certificazione annuale di conformità né del manuale di montaggio della torre (gap distinto, pannello Admin/torri — richiede un PRD separato).
- Non si modifica la logica di stato della state machine prenotazioni (annullamento, approvazione, ecc.) — resta invariata.
- Non si implementa in questo PRD il questionario di feedback post-utilizzo né i reminder automatici sui documenti mancanti (gap distinti, altri PRD).
- Non si gestisce la generazione/versione di un nuovo manuale d'istruzioni: si assume che l'upload avvenga tramite funzionalità admin già esistente (`manuale_pdf_path`).

## Design Considerations

- Riutilizzare i componenti Filament/Livewire già usati negli altri step del wizard (radio/select per `tipo_mezzo` e `categoria_patente_privato`, coerenti con lo stile esistente).
- Il messaggio di blocco per prenotazione attiva (US-001) deve essere un'infolist/notice Filament, non un errore di validazione generico — deve risultare chiaro e non tecnico all'utente sezione.
- La checkbox del manuale (US-004) deve essere visivamente vicina al link/pulsante di download, per rendere evidente il collegamento tra lettura e conferma.

## Technical Considerations

- Componente coinvolto: `app/Filament/Sezione/Resources/PrenotazioneResource/Pages/CreatePrenotazione.php` (wizard Livewire/Filament).
- Modello `Prenotazione` (`app/Models/Prenotazione.php`): aggiungere `tipo_mezzo`, `categoria_patente_privato` a `$fillable` e ai cast enum; rimuovere `responsabile_codice_cai`.
- Migrazione dedicata (non modificare la migrazione originale `2026_05_10_200005_create_prenotazioni_table.php`, già eseguita in produzione): nuova migrazione per add/drop colonne.
- Il campo esistente `responsabile_titolo_cai` (diverso da `responsabile_codice_cai`) NON va toccato — resta perché rappresenta il titolo (istruttore, accompagnatore, ecc.), non il codice tesserato.
- Verificare riferimenti a `responsabile_codice_cai` in: PDF template (Richiesta parete, Modulo 3), eventuali export Excel, factory di test (`PrenotazioneFactory`).
- Il modello `Torre` ha già il campo `manuale_pdf_path` — nessuna nuova migrazione lato torre necessaria per US-004.
- Enum da creare: `TipoMezzo` e `CategoriaPatente` in `app/Enums/`, seguendo la convenzione già usata da `ResponsabileTipo`.

## Success Metrics

- Zero segnalazioni di supporto del tipo "ho compilato tutto il form e poi non me lo ha fatto salvare" relative al vincolo prenotazione-attiva.
- Riduzione delle telefonate di supporto per problemi/domande già coperte dal manuale d'istruzioni (metrica qualitativa, raccolta da GR nei mesi successivi al rilascio).
- 100% delle nuove prenotazioni con mezzo privato hanno una categoria patente valorizzata (nessun dato incompleto).

## Open Questions

- Cosa deve succedere se una torre non ha ancora un manuale caricato (`manuale_pdf_path` nullo)? Bloccare comunque il flag, o permettere di procedere senza checkbox in quel caso? (Da decidere con l'admin/GR prima dello sviluppo di US-004.)
- Il reset del flag di lettura manuale al cambio torre deve avvisare esplicitamente l'utente del motivo (es. "hai cambiato torre, devi confermare di aver letto il nuovo manuale") o è sufficiente il comportamento silenzioso della checkbox che si deseleziona?
