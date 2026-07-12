# PRD: Restyle UX/UI dei 3 pannelli Filament + esperienza mobile

## Introduzione/Overview

Nel progetto claude.ai/design **"Design UI ruoli"** sono stati prodotti 4 mockup HTML (vedi `tasks/design-reference/prenotar-ui-ruoli/`) che ridisegnano le schermate chiave dei 3 pannelli Filament di Prenotar (`/admin`, `/gr`, `/sezione`) e la loro versione mobile (390px), basandosi su un design system dedicato ("Montagna Servizi Design System": verde pino come brand primario, accento larice, neutri "stone", font Manrope) e sul documento di brief `tasks/design-reference/prenotar-ui-ruoli/../PROMPT_DESIGN_UI_ruoli.md`-equivalente (contesto dominio + bug noti da non replicare, già presenti in `CLAUDE.md`).

Oggi i 3 pannelli usano solo i colori Filament stock inline (`Color::Amber`, `Color::Green`, `Color::Blue` — vedi `AdminPanelProvider`, `GrPanelProvider`, `SezionePanelProvider`), nessun tema custom compilato, e **nessuna personalizzazione mobile**: l'app eredita il comportamento responsive di default di Filament (sidebar che collassa in un menu, nessuna bottom nav, nessun FAB, tabelle non "stackate" a card). I mockup introducono pattern non nativi di Filament (bottom tab bar mobile, FAB, liste a card, wizard full-screen con CTA fissa) che richiedono override custom dei layout Blade di Filament, non solo un tema di colori.

Questo PRD copre il restyle **desktop e mobile di tutti e 3 i pannelli**, riutilizzando le Resource/Page/Widget Filament esistenti (mappate in dettaglio nella sezione Technical Considerations) dove la struttura corrisponde, ed estendendole dove il mockup introduce contenuti/azioni nuove. È un cambiamento di **livello visivo/UX**, non funzionale: la state machine, le policy, i job e le regole di dominio restano invariati salvo dove il mockup stesso implica un fix di UX già tracciato come bug noto (vedi tabella BUG-XX in `CLAUDE.md`).

## Goals

- Introdurre i design token Montagna Servizi (colori, tipografia, spaziatura, effetti) come tema Filament compilato, condiviso dai 3 pannelli, in sostituzione dei colori inline `Color::Amber/Green/Blue`.
- Ridisegnare tutte le schermate desktop coperte dai mockup nei 3 pannelli (dashboard, liste, dettaglio/form, wizard, calendario, impostazioni) mantenendo invariata la logica sottostante.
- Introdurre una shell mobile condivisa (<768px) per i 3 pannelli: topbar con hamburger menu al posto della sidebar, bottom tab bar a 4 voci, FAB per l'azione primaria, liste tabellari convertite in card, wizard adattato a step full-screen con CTA fissa in basso.
- Unificare la codifica colore delle torri in tutta l'app (calendario, badge tabella, dettaglio prenotazione) — fix strutturale di BUG-07/BUG-08, prerequisito visivo per i mockup di calendario e liste.
- Rendere le azioni distruttive/eccezionali (impersona, hard delete, force-stato) visivamente distinguibili dalle azioni ordinarie in tutti i pannelli dove compaiono, come richiesto dal brief di design.

## Non-Goals (Out of Scope)

- Nessuna modifica alla state machine delle prenotazioni, alle policy di autorizzazione, alla generazione PDF, all'invio email o alla logica di import Excel.
- Nessuna modifica allo schema del database oltre a quanto strettamente necessario per l'unificazione colore torri (se serve una colonna `colore_hex`/enum su `Torre` — vedi Open Questions) e per l'eventuale campo di firma/anagrafica presidente GR già previsto nella pagina Impostazioni esistente.
- Nessuna dark mode (esplicitamente non richiesta dal brief di design: l'app è uso ufficio, non un requisito).
- Nessun nuovo ruolo o permesso.
- Non si tocca il comportamento responsive "stock" di Filament oltre al nuovo breakpoint mobile custom: il restyle desktop tra ~768px e i breakpoint standard Filament non introduce layout intermedi dedicati.
- Le schermate mobile "campione" nel mockup (7 su ~26 totali) non coprono ogni pagina: le pagine mobile non illustrate esplicitamente (es. Sezione step 2/4, Modifica/Allegati, Calendario; GR lista/calendario/impostazioni; Admin torri/prenotazioni/import/audit) vanno adattate applicando lo stesso linguaggio di shell mobile (topbar, bottom nav, FAB, card) definito da US-002, non ridisegnate da zero — questo PRD lo richiede esplicitamente (vedi FR-9) ma non fornisce un mockup pixel-perfect per quelle pagine.

## User Stories

### EPIC 0 — Fondamenta condivise (prerequisito per tutte le altre epiche)

#### US-001: Tema Filament condiviso con i design token Montagna Servizi

**Descrizione:** Come sviluppatore, voglio un tema CSS compilato (Vite) che espone i design token del progetto (colori, tipografia, spaziatura, effetti) come variabili utilizzabili nei 3 pannelli Filament, così da poter implementare gli altri user stories senza duplicare colori hardcoded.

**Acceptance Criteria:**
- [ ] Nuovo entry point Vite `resources/css/filament/{admin,gr,sezione}/theme.css` per ciascun pannello (o un unico entry condiviso + override di colore primario per pannello, a scelta implementativa purché i 3 colori restino distinti come nei mockup: Sezione = blu/ardesia scuro, GR = verde pino, Admin = ambra/larice scuro).
- [ ] I token in `tasks/design-reference/prenotar-ui-ruoli/tokens/*.css` sono la fonte di verità per i valori (non re-inventare palette).
- [ ] Ogni `*PanelProvider.php` registra il proprio tema via `->viteTheme(...)` (o equivalente Filament 3) al posto di (o in aggiunta a) `->colors(['primary' => Color::X])`.
- [ ] Font Manrope self-hosted (file font inclusi nel build Vite, nessuna chiamata a domini esterni tipo `fonts.googleapis.com` dal browser dell'utente).
- [ ] Nessuna regressione visiva grossolana sulle pagine non ancora ridisegnate (restano leggibili, solo il colore primario cambia).
- [ ] `composer qa` (Pint + Larastan + Pest) passa invariato.

#### US-002: Shell mobile condivisa (topbar, hamburger, bottom tab bar, FAB)

**Descrizione:** Come utente su smartphone (qualunque ruolo), voglio una navigazione ottimizzata per mobile — topbar compatta con menu, bottom tab bar con le 3-4 sezioni principali del mio pannello, pulsante di azione primaria (FAB) quando pertinente — così da poter usare Prenotar comodamente da telefono.

**Acceptance Criteria:**
- [ ] Layout Blade di Filament (render hook o vendor-publish mirato, non l'intero pacchetto) esteso con: topbar mobile (logo/nome pannello + hamburger + icona notifiche), bottom tab bar fissa (4 voci come nei mockup, icone Lucide, voce attiva evidenziata col colore del pannello), FAB in basso a destra dove il mockup lo prevede (creazione nuova prenotazione).
- [ ] Attivo solo sotto i 768px (o il breakpoint Tailwind `md`), il layout desktop esistente non cambia struttura.
- [ ] Tap target minimo 44×44px su tutti gli elementi interattivi della shell mobile (bottom nav, FAB, hamburger), come richiesto dal brief.
- [ ] La bottom tab bar naviga tra le pagine Filament reali del pannello corrente (Dashboard, Nuova/Prenotazioni, Calendario, + 4a voce specifica per ruolo) senza ricaricare l'intero layout desktop.
- [ ] Verify in browser using dev-browser skill (viewport 390×844, un pannello per ruolo).

#### US-003: Unificazione colore torri in tutta l'app

**Descrizione:** Come utente di qualsiasi pannello, voglio che la stessa torre abbia sempre lo stesso colore — nel calendario, nei badge di tabella, nel dettaglio prenotazione — così da riconoscerla a colpo d'occhio ovunque compaia (fix BUG-07).

**Acceptance Criteria:**
- [ ] Il colore di ciascuna torre è definito in un unico punto: nuovo campo `colore_hex` sul modello `Torre`, editabile da Admin (non calcolato per id in punti diversi come oggi).
- [ ] Il calendario (`CalendarioPage`/`CalendarioPrenotazioniWidget`, pannelli GR e Sezione), i badge torre nelle tabelle Prenotazione (Admin/GR/Sezione) e la vista di dettaglio usano tutti la stessa fonte per il colore.
- [ ] Migrazione per aggiungere il campo `colore_hex` a `Torre` (validato come colore esadecimale), con seeding retroattivo delle 2 torri esistenti sui colori già scelti nei mockup (verde per Torre 1, larice/arancio per Torre 2 — coerente con `LocalDevSeeder`).
- [ ] Il campo è editabile in `TorreResource` (Admin) tramite un color picker (`ColorPicker::make('colore_hex')` o equivalente Filament) — collegato a US-029.
- [ ] Test Feature che verifica: stesso colore restituito per la stessa torre da calendario e da badge tabella.

### EPIC A — Pannello Sezione (`/sezione`)

#### US-004: Dashboard Sezione — desktop

**Descrizione:** Come presidente di sezione/sottosezione, voglio una dashboard che mostri subito la mia prossima prenotazione attiva (o un invito chiaro a crearne una se non ne ho), così da capire in un colpo d'occhio il mio stato.

**Acceptance Criteria:**
- [ ] Stato "prenotazione attiva": card con evento, date, stato (badge), torre assegnata (colore da US-003), indirizzo deposito in evidenza (BUG-08), eventuale avviso scadenza documento (es. "carica il PDF firmato entro il ..."), CTA "Vedi dettaglio".
- [ ] Stato vuoto: messaggio di invito chiaro a creare una nuova prenotazione, coerente con il mockup `pannello-sezione.dc.html` schermata "Dashboard stato vuoto".
- [ ] Riutilizza `PrenotazioniDashboardWidget` esistente estendendone markup/vista, non lo sostituisce con un componente parallelo.
- [ ] Verify in browser using dev-browser skill.

#### US-005: Dashboard Sezione — mobile

**Descrizione:** Come presidente di sezione, voglio la stessa dashboard su smartphone con card e bottom nav, così da controllare lo stato della mia prenotazione anche fuori ufficio.

**Acceptance Criteria:**
- [ ] Corrisponde alla schermata mockup "Mobile Sezione Dashboard": card prenotazione attiva, 2 quick action (Nuova prenotazione, Calendario torri), bottom nav Home/Nuova/Prenotazioni/Calendario.
- [ ] Usa la shell mobile di US-002, non un layout parallelo.
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-006: Wizard prenotazione — Step 1 "Quando e dove" (desktop)

**Descrizione:** Come presidente di sezione, nello step 1 del wizard voglio scegliere le date e (opzionalmente) la torre, con il vincolo bloccante di dover confermare la lettura del manuale d'istruzioni se scelgo una torre, reso visivamente inequivocabile.

**Acceptance Criteria:**
- [ ] Restyle dello step esistente in `PrenotazioneResource::wizardSteps()` (non nuova logica: la checkbox bloccante "ho letto il manuale" e il reset al cambio torre sono già implementati per `prd-wizard-prenotazione-miglioramenti.md` US-004 — qui si restyla solo la presentazione).
- [ ] Le card torre mostrano indirizzo deposito (BUG-08) e pallino colore coerente con US-003.
- [ ] Il pulsante "Continua" resta disabilitato finché la checkbox non è confermata, con messaggio esplicativo sotto il pulsante come nel mockup.
- [ ] Nessuna regressione ai test esistenti (`PrenotazioneWizardCreateTest`, `PrenotazioneBloccoWizardAttivaTest`).
- [ ] Verify in browser using dev-browser skill.

#### US-007: Wizard prenotazione — Step 1 mobile

**Descrizione:** Come presidente di sezione su smartphone, voglio compilare lo step 1 del wizard a schermo intero con indicatore di progresso e pulsante "Continua" sempre visibile in basso.

**Acceptance Criteria:**
- [ ] Corrisponde al mockup "Mobile Sezione Wizard step 1": topbar con freccia indietro + "Passo 1 di 5", barra di progresso sottile, contenuto scrollabile, CTA fissa in basso con relativo messaggio di blocco.
- [ ] Stesso comportamento di validazione desktop (nessuna duplicazione di logica, solo markup).
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-008: Wizard prenotazione — Step 3 "Logistica trasporto" (desktop)

**Descrizione:** Come presidente di sezione, nello step logistica voglio vedere chiaramente la scelta mezzo aziendale/privato con targa e categoria patente, in coerenza visiva con lo step 1 ridisegnato.

**Acceptance Criteria:**
- [ ] Restyle dello step esistente (campi `tipo_mezzo`, `categoria_patente_privato`, date/luoghi ritiro-riconsegna, azienda trasporto, targa) secondo il mockup `pannello-sezione.dc.html` "Wizard step 3 Logistica".
- [ ] Nessuna modifica ai campi o alla validazione, solo a componenti/gerarchia visiva.
- [ ] Verify in browser using dev-browser skill.

#### US-009: Wizard prenotazione — Step 5 "Riepilogo" (desktop)

**Descrizione:** Come presidente di sezione, nello step finale voglio un riepilogo leggibile di tutti i dati inseriti prima di salvare come bozza, con avviso esplicito sul prossimo passo (caricare la delibera).

**Acceptance Criteria:**
- [ ] Restyle dello step riepilogo secondo mockup "Wizard step 5 Riepilogo": sezioni raggruppate per step precedente, badge di stato coerenti, avviso "dopo il salvataggio serve caricare la delibera".
- [ ] Verify in browser using dev-browser skill.

#### US-010: Wizard prenotazione — Step 2 "Evento" e Step 4 "Responsabile" (desktop, coerenza visiva)

**Descrizione:** Come sviluppatore, voglio allineare gli step 2 e 4 del wizard (non illustrati esplicitamente nei mockup) alla stessa gerarchia visiva/spaziatura/componenti degli step 1/3/5 ridisegnati, per evitare un'esperienza incoerente all'interno dello stesso wizard.

**Acceptance Criteria:**
- [ ] Stessi componenti di input, spaziatura, tipografia degli altri step (nessun mockup dedicato da seguire pixel-per-pixel, ma coerenza con lo stile system-wide di US-001).
- [ ] Nessuna modifica ai campi esistenti.
- [ ] Verify in browser using dev-browser skill.

#### US-011: Modifica prenotazione / Allegati (desktop)

**Descrizione:** Come presidente di sezione, nella pagina di modifica/allegati di una prenotazione esistente voglio un form chiaro con lo stato di ogni allegato richiesto (delibera, autorizzazioni, patente) e l'azione "Invia richiesta al GR" abilitata solo a delibera caricata.

**Acceptance Criteria:**
- [ ] Restyle secondo mockup "Modifica prenotazione Allegati": stato per-allegato (mancante/caricato), azione invio disabilitata finché la delibera non è presente, azione "elimina bozza" visibile solo in stato Bozza.
- [ ] Nessuna modifica alla logica di validazione/abilitazione già esistente.
- [ ] Verify in browser using dev-browser skill.

#### US-012: "Le mie prenotazioni" — lista desktop

**Descrizione:** Come presidente di sezione, voglio la lista delle mie prenotazioni ordinata dalla più vicina (BUG-01) con una tab separata per l'archivio (BUG-02).

**Acceptance Criteria:**
- [ ] Ordinamento default `data_inizio_prenotazione DESC` (più vicina in cima) già presente lato query — qui si verifica/rende esplicito anche in UI (colonna ordinamento visibile).
- [ ] Tab "Attive" / "Archivio (concluse/annullate)" nella `PrenotazioneResource` (Sezione), non tabella unica con tutto mescolato.
- [ ] Badge torre coerenti con US-003.
- [ ] Verify in browser using dev-browser skill.

#### US-013: "Le mie prenotazioni" — lista mobile a card

**Descrizione:** Come presidente di sezione su smartphone, voglio vedere le mie prenotazioni come card scorrevoli invece che come tabella, con le stesse tab Attive/Archivio e un FAB per crearne una nuova.

**Acceptance Criteria:**
- [ ] Corrisponde al mockup "Mobile Sezione Lista": tab pill Attive/Archivio con contatori, card per prenotazione (titolo, date, badge stato, badge torre o "da assegnare"), FAB in basso a destra.
- [ ] Nessuna tabella HTML renderizzata sotto il breakpoint mobile per questa pagina — solo card.
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-014: Calendario torri — desktop

**Descrizione:** Come presidente di sezione, voglio un calendario unico (non a tab separate) con le 2 torri distinte a colori coerenti col resto dell'app (fix BUG-07).

**Acceptance Criteria:**
- [ ] `CalendarioPrenotazioniWidget`/`CalendarioPage` (Sezione) usa i colori da US-003 per gli eventi FullCalendar.
- [ ] Nessuna tab per torre: un solo calendario, click su evento apre il dettaglio.
- [ ] Verify in browser using dev-browser skill.

#### US-015: Calendario — vista mobile

**Descrizione:** Come presidente di sezione su smartphone, voglio consultare il calendario delle torri anche da mobile, in una vista adattata (agenda/lista invece di griglia mensile se la griglia non è leggibile a 390px).

**Acceptance Criteria:**
- [ ] FullCalendar in modalità `listWeek`/`listMonth` (o equivalente) sotto il breakpoint mobile, non la griglia mensile desktop compressa illeggibile.
- [ ] Stessa shell mobile (topbar, bottom nav) di US-002.
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-016: Etichetta "S.SEZ." per sottosezioni — applicazione in tutto il pannello Sezione

**Descrizione:** Come presidente di una sottosezione, ovunque compaia il nome della mia sottosezione (dashboard, liste, dettaglio) voglio vedere sempre l'etichetta "S.SEZ. {nome} (sez. rif. {sezione madre})", mai confusa con una sezione (fix BUG-05).

**Acceptance Criteria:**
- [ ] Componente/helper condiviso per il rendering dell'etichetta sezione/sottosezione, usato in dashboard (US-004/005), lista (US-012/013) e dettaglio.
- [ ] Verificato con un nome sottosezione lungo che l'etichetta resta leggibile su mobile (non tronca in modo illeggibile).
- [ ] Test Feature che verifica il formato dell'etichetta per un utente sottosezione vs sezione.

### EPIC B — Pannello GR (`/gr`)

#### US-017: Dashboard GR — desktop

**Descrizione:** Come Presidente GR (o delegato), voglio una dashboard che mi mostri immediatamente "cosa devo fare oggi": richieste in attesa di approvazione, ordinate per urgenza, con contatori.

**Acceptance Criteria:**
- [ ] Restyle di `PrenotazioniDaApprovareWidget` secondo mockup "GR Dashboard" (`pannello-gr.dc.html`): contatori "da approvare" e "approvate nei prossimi 30gg", lista richieste in attesa con badge "tra N giorni", CTA "Esamina" per ciascuna.
- [ ] Etichetta S.SEZ. (US-016) applicata anche qui per richieste da sottosezioni.
- [ ] Verify in browser using dev-browser skill.

#### US-018: Dashboard GR — mobile

**Descrizione:** Come Presidente GR su smartphone, voglio la stessa vista "da approvare" in formato card con bottom nav.

**Acceptance Criteria:**
- [ ] Corrisponde al mockup "Mobile GR Dashboard": contatori a griglia 2 colonne, card richiesta con CTA "Esamina", bottom nav Home/Prenotazioni/Calendario/Impostazioni.
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-019: Lista prenotazioni GR — desktop

**Descrizione:** Come Presidente GR, voglio una tabella di tutte le prenotazioni con sezione/sottosezione richiedente, evento, periodo, torre assegnata, stato, filtrabile per stato/torre/sezione.

**Acceptance Criteria:**
- [ ] Restyle di `PrenotazioneResource` (GR) secondo mockup "GR Lista prenotazioni": colonne e filtri come da brief, badge torre coerenti con US-003, etichetta S.SEZ. (US-016).
- [ ] Verify in browser using dev-browser skill.

#### US-020: Dettaglio prenotazione GR — desktop (tab Dettagli/Allegati + azioni)

**Descrizione:** Come Presidente GR, nel dettaglio di una richiesta voglio vedere i dati in tab (Dettagli/Allegati/Storico) e avere a disposizione le azioni pertinenti allo stato corrente (Approva, Rifiuta, Riassegna torre, Invia assicurazione, Concludi).

**Acceptance Criteria:**
- [ ] Restyle secondo mockup "GR Dettaglio prenotazione": header con titolo evento, etichetta richiedente, badge stato+torre; tab Dettagli/Allegati(N)/Storico; azioni condizionate allo stato corrente della prenotazione (non tutte visibili sempre).
- [ ] Download PDF "Richiesta parete" e "Modulo 3" presenti e funzionanti come oggi (nessuna modifica alla generazione PDF).
- [ ] Nessuna modifica alla state machine: le azioni chiamano gli stessi metodi/eventi già esistenti, solo la UI cambia.
- [ ] Verify in browser using dev-browser skill.

#### US-021: Dettaglio prenotazione GR — mobile (azioni fisse in basso)

**Descrizione:** Come Presidente GR su smartphone, nel dettaglio di una richiesta voglio le azioni principali (Approva/Rifiuta) sempre visibili in fondo allo schermo, senza dover scrollare per trovarle.

**Acceptance Criteria:**
- [ ] Corrisponde al mockup "Mobile GR Dettaglio": header con freccia indietro, tab Dettagli/Allegati/Storico, sezione documenti scaricabili, barra azioni fissa in basso (Approva primario, Rifiuta secondario con outline rosso).
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-022: Tab "Storico" — timeline transizioni di stato

**Descrizione:** Come Presidente GR, nella tab Storico del dettaglio voglio vedere la timeline delle transizioni di stato (autore, data, nota) della prenotazione.

**Acceptance Criteria:**
- [ ] Restyle secondo mockup "GR Dettaglio Storico": timeline verticale con icona/colore per tipo di transizione, autore e timestamp leggibili.
- [ ] Dati letti da `prenotazione_history` esistente, nessuna nuova migrazione.
- [ ] Verify in browser using dev-browser skill.

#### US-023: Calendario GR — desktop

**Descrizione:** Come Presidente GR, voglio lo stesso calendario unico a colori coerenti visto dalla Sezione, con vista mensile e click su evento per aprire il dettaglio.

**Acceptance Criteria:**
- [ ] `CalendarioPage` (GR) allineata a US-003/US-014 (stessa sorgente colore torri).
- [ ] Verify in browser using dev-browser skill.

#### US-024: Impostazioni GR — pagina a tab

**Descrizione:** Come Presidente GR, nella pagina Impostazioni voglio gestire in tab separate: email notifiche GR, liste email assicurazione, dati anagrafici/firma del Presidente, parametri operativi (giorni minimi documenti, ore minime richiesta assicurazione).

**Acceptance Criteria:**
- [ ] Restyle di `ImpostazioniPage` (GR) secondo mockup "GR Impostazioni": tab separate come da brief. I campi anagrafica/firma presidente esistono già (`GrSettings::presidente_nome/nato_a/data_nascita`, `firma_presidente_path`, `documento_presidente_path` via `FileUpload`) — solo restyle della presentazione, nessun nuovo campo.
- [ ] Nessuna modifica alla persistenza esistente (spatie/laravel-settings, classe `GrSettings`).
- [ ] Verify in browser using dev-browser skill.

### EPIC C — Pannello Admin (`/admin`)

#### US-025: Dashboard Admin — desktop (stato di sistema)

**Descrizione:** Come responsabile tecnico, voglio una dashboard con lo stato di sistema (utenti attivi, ultimo import Excel, errori recenti, coda job) e gli ultimi eventi di audit, per capire subito se c'è qualcosa che richiede attenzione.

**Acceptance Criteria:**
- [ ] Nuova composizione di widget stat (utenti attivi/totali, errori ultimi 7gg, ultimo import con esito, coda job/link Horizon) secondo mockup "Admin Dashboard".
- [ ] Sezione "ultimi eventi" che legge dall'audit log esistente (Spatie activitylog), non una nuova sorgente dati.
- [ ] Verify in browser using dev-browser skill.

#### US-026: Dashboard Admin — mobile

**Descrizione:** Come responsabile tecnico su smartphone, voglio la stessa vista di stato sistema in formato card con bottom nav.

**Acceptance Criteria:**
- [ ] Corrisponde al mockup "Mobile Admin Dashboard": indicatore stato sistema in topbar (pallino "OK"), stat a griglia 2 colonne, lista eventi recenti, bottom nav Home/Utenti/Prenotazioni/Audit log.
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-027: Gestione utenti — desktop

**Descrizione:** Come responsabile tecnico, nella tabella utenti voglio vedere nome, email, ruoli, sezione/sottosezione di appartenenza, stato attivo/disattivato, con le azioni impersona/reset password/attiva-disattiva chiaramente distinte (impersona resa visivamente "pericolosa").

**Acceptance Criteria:**
- [ ] Restyle di `UserResource` (Admin) secondo mockup "Admin Utenti": azione "Impersona" con stile/colore distinto dalle altre azioni riga (non un'icona uguale alle altre).
- [ ] Azione "attiva/disattiva" (già esistente: `Action::make('toggle_active')` con `Textarea::make('motivo')` obbligatoria, loggata via `AuditLogger::logAdminAction`) resta funzionalmente invariata — solo restyle della presentazione/posizione dell'azione e del suo form di conferma.
- [ ] Verify in browser using dev-browser skill.

#### US-028: Gestione utenti — mobile

**Descrizione:** Come responsabile tecnico su smartphone, voglio gestire gli utenti anche da mobile con la stessa shell a card.

**Acceptance Criteria:**
- [ ] Tabella `UserResource` convertita a card sotto il breakpoint mobile (nessun mockup pixel-perfect dedicato: applica il pattern di US-013/US-002).
- [ ] Azione "Impersona" resta visivamente distinta anche in versione card/mobile.
- [ ] Verify in browser using dev-browser skill (390×844).

#### US-029: Gestione torri — desktop (indirizzo deposito prominente)

**Descrizione:** Come responsabile tecnico, nella gestione torri voglio un CRUD completo (nome, descrizione, indirizzo deposito, foto, PDF specifiche/manuale, toggle attiva) con l'indirizzo di deposito sempre ben visibile (fix BUG-08).

**Acceptance Criteria:**
- [ ] Restyle di `TorreResource` (Admin) secondo mockup "Admin Torri": indirizzo deposito in evidenza (non un campo minore in coda al form/tabella).
- [ ] Campo colore torre (US-003) gestibile qui se implementato come campo esplicito.
- [ ] Verify in browser using dev-browser skill.

#### US-030: Prenotazioni Admin — sola lettura + azioni straordinarie

**Descrizione:** Come responsabile tecnico, voglio vedere tutte le prenotazioni di tutte le sezioni e avere accesso alle azioni eccezionali (force-stato con motivazione, hard delete con conferma forte) rese visivamente distinte dalle azioni ordinarie.

**Acceptance Criteria:**
- [ ] Restyle di `PrenotazioneResource` (Admin) secondo mockup "Admin Prenotazioni": azioni "force stato" ed "elimina definitivamente" isolate/distinte (es. sezione separata del menu azioni, colore di allerta), non mescolate alle azioni normali.
- [ ] Nessuna modifica alla logica di hard-delete esistente (conferma + audit log invariati) — solo presentazione.
- [ ] Verify in browser using dev-browser skill.

#### US-031: Log import Excel — desktop

**Descrizione:** Come responsabile tecnico, voglio una tabella storica degli import Excel (data, righe importate/aggiornate/in errore) con possibilità di vedere il dettaglio errori.

**Acceptance Criteria:**
- [ ] Restyle di `ExcelImportResource` secondo mockup "Admin Import Excel".
- [ ] Verify in browser using dev-browser skill.

#### US-032: Audit log — desktop

**Descrizione:** Come responsabile tecnico, voglio una tabella eventi (chi ha fatto cosa, quando) filtrabile per tipo evento e intervallo date.

**Acceptance Criteria:**
- [ ] Restyle di `AuditLogResource` secondo mockup "Admin Audit log": filtri per tipo evento e data.
- [ ] Verify in browser using dev-browser skill.

## Functional Requirements

- FR-1: Il sistema deve esporre i design token Montagna Servizi (colori, tipografia, spaziatura, effetti — vedi `tasks/design-reference/prenotar-ui-ruoli/tokens/`) come tema Filament compilato per ciascuno dei 3 pannelli, in sostituzione dei colori inline oggi in uso.
- FR-2: Il sistema deve applicare, sotto il breakpoint mobile (768px), una shell di navigazione condivisa (topbar+hamburger, bottom tab bar, FAB) ai 3 pannelli, coerente nei componenti ma con colore primario distinto per pannello.
- FR-3: Il sistema deve derivare il colore di ciascuna torre da un'unica fonte dati (`Torre`), usata coerentemente in calendario, badge tabella e dettaglio prenotazione in tutti e 3 i pannelli.
- FR-4: Tutte le tabelle Filament elencate nei mockup (Prenotazione, Utenti, Torri, Import Excel, Audit log) devono presentarsi come liste di card sotto il breakpoint mobile, non come tabelle HTML compresse.
- FR-5: Le azioni distruttive o eccezionali (impersona, hard delete, force-stato) devono essere visivamente distinte (colore/posizione/iconografia) dalle azioni ordinarie in ogni punto dell'interfaccia in cui compaiono, in tutti i pannelli e in entrambe le viste desktop/mobile.
- FR-6: L'etichetta di una sottosezione deve sempre comparire nella forma "S.SEZ. {nome} (sez. rif. {sezione madre})" ovunque nel sistema (dashboard, liste, dettaglio), nei pannelli Sezione e GR.
- FR-7: Il calendario delle torri deve restare unico (nessuna tab per torre) in tutti i punti in cui compare (Sezione, GR), con vista adattata (lista/agenda) sotto il breakpoint mobile.
- FR-8: L'ordinamento di default delle liste prenotazioni deve restare `data_inizio_prenotazione DESC` (più vicina in cima) e le prenotazioni concluse/annullate devono comparire solo in una tab "Archivio" separata, mai nella vista principale.
- FR-9: Le pagine dei 3 pannelli non esplicitamente coperte da uno screenshot mobile nel mockup devono comunque ricevere la shell mobile di FR-2/US-002 (bottom nav coerente col pannello, card al posto di tabelle) per garanzia di usabilità completa, anche in assenza di un riferimento pixel-perfect dedicato.
- FR-10: Nessuna delle modifiche di questo PRD deve alterare il comportamento delle policy di autorizzazione, della state machine delle prenotazioni, della generazione PDF o dell'invio email.

## Design Considerations

- Riferimento visivo primario: i 4 file in `tasks/design-reference/prenotar-ui-ruoli/` (`pannello-sezione.dc.html`, `pannello-gr.dc.html`, `pannello-admin.dc.html`, `mobile-3-ruoli.dc.html`) e i token in `tasks/design-reference/prenotar-ui-ruoli/tokens/`. Sono file statici non eseguibili standalone (referenziano asset del progetto claude.ai/design originale): vanno letti come riferimento di struttura/colore/spaziatura, non aperti nel browser aspettandosi un rendering fedele al 100% senza gli asset `_ds/...`.
- Icone: il mockup usa Lucide (`data-lucide="..."`) — **da mappare 1:1 su Heroicons** (già incluso in Filament, nessuna nuova dipendenza), scegliendo l'icona Heroicons semanticamente più vicina a ciascuna icona Lucide del mockup (es. `calendar-range`→`heroicon-o-calendar-days`, `map-pin`→`heroicon-o-map-pin`, `file-warning`→`heroicon-o-document-exclamation`) invece della resa grafica esatta.
- Font Manrope: **self-hosted**, incluso nel build Vite — nessuna chiamata a Google Fonts o altri CDN esterni a runtime.
- Bottone/FAB, bottom nav, card: sono pattern **non nativi** di Filament — vanno implementati come override mirati dei layout Blade (render hooks `Filament::renderHook(...)` o viste vendor-published puntuali), non come fork completo del pacchetto Filament.

## Technical Considerations

- Panel providers da modificare: `app/Providers/Filament/AdminPanelProvider.php`, `GrPanelProvider.php`, `SezionePanelProvider.php` (oggi: `->colors(['primary' => Color::Amber/Green/Blue])`, nessun guard dedicato, middleware condiviso incl. `EnsureContactEmail`).
- Resource coinvolte: `app/Filament/Admin/Resources/{AuditLogResource,ExcelImportResource,PrenotazioneResource,TorreResource,UserResource}.php`; `app/Filament/Gr/Resources/{PrenotazioneResource,TorreResource}.php`; `app/Filament/Sezione/Resources/{PrenotazioneResource,TorreResource}.php`.
- Pagine custom coinvolte: `app/Filament/Pages/FirstAccessPage.php` (condivisa); `app/Filament/Admin/Pages/TestEmailPage.php`; `app/Filament/Gr/Pages/{CalendarioPage,ImpostazioniPage}.php`; `app/Filament/Sezione/Pages/CalendarioPage.php`; wizard in `app/Filament/Sezione/Resources/PrenotazioneResource/Pages/CreatePrenotazione.php` (steps definiti in `PrenotazioneResource::wizardSteps()`).
- Widget coinvolti: `app/Filament/Gr/Widgets/PrenotazioniDaApprovareWidget.php`; `app/Filament/Sezione/Widgets/{PrenotazioniDashboardWidget,PlaceholderWidget,CalendarioPrenotazioniWidget}.php` (`CalendarioPrenotazioniWidget extends FullCalendarWidget`); nessun widget custom oggi in Admin (solo stock `AccountWidget`/`FilamentInfoWidget`) — US-025 ne introduce di nuovi.
- Nessun tema Filament oggi compilato: `resources/css/app.css` è Tailwind vanilla, `vite.config.js` ha un solo entry — US-001 introduce entry Vite dedicati per pannello.
- Nessuna view Filament vendor-published oggi (`resources/views/vendor/filament-panels/` assente): gli override di layout per la shell mobile (US-002) sono territorio nuovo, non modifica di file esistenti.
- Plugin già installati e da riusare: `saade/filament-fullcalendar` (calendario), `stechstudio/filament-impersonate` (azione impersona, US-027), `spatie/laravel-medialibrary` + `filament/spatie-laravel-media-library-plugin` (foto/PDF torri, US-029).
- Filament `^3.2` su Laravel `^11.31`, PHP `^8.2` — nessun upgrade di versione previsto da questo PRD.
- Test da proteggere durante il refactor (non devono regredire): `tests/Feature/Auth/*`, `tests/Feature/Admin/*` (8 file), `tests/Feature/Gr/*` (12 file), `tests/Feature/Sezione/*` (11 file) — in particolare `PrenotazioneWizardCreateTest`, `PrenotazioneBloccoWizardAttivaTest`, `TorreReadOnlyGrResourceTest`, `TorreReadOnlyResourceTest`, `PrenotazioneCalendarioGrPageTest`, `PrenotazioneCalendarioPageTest`, `ImpostazioniGrPageTest`.
- Dato il volume (32 user stories su 3 pannelli), l'implementazione via Ralph va probabilmente sequenziata: EPIC 0 (US-001/002/003) prima di tutto il resto, poi le 3 epiche per pannello in un ordine qualsiasi (non hanno dipendenze incrociate tra loro).

## Success Metrics

- Tutte e 32 le user stories chiudono con `composer qa` verde (Pint + Larastan + Pest) e verifica browser desktop+mobile dove richiesta.
- Nessuna regressione nei test Feature esistenti elencati in Technical Considerations.
- Zero occorrenze residue di colori Filament stock (`Color::Amber`, `Color::Green`, `Color::Blue`) nei 3 PanelProvider dopo US-001.
- Il colore di ciascuna torre è identico (stesso valore, stessa fonte dati) in calendario, badge tabella e dettaglio, verificabile con un singolo test automatico (US-003).

## Open Questions

Nessuna al momento — tutte le questioni aperte in una revisione precedente di questo PRD sono state risolte:

- Colore torri (US-003/US-029): campo `colore_hex` editabile in `TorreResource`, non fissato via seed non modificabile.
- Font Manrope (US-001): self-hosted.
- Icone (Design Considerations): mappate su Heroicons esistenti, nessuna nuova dipendenza Lucide.
- Attiva/disattiva utente (US-027): già implementato lato backend (`Action::make('toggle_active')`, `Textarea::make('motivo')` obbligatoria, log via `AuditLogger`) — solo restyle.
- Anagrafica/firma Presidente GR (US-024): già implementato lato backend (`GrSettings` + `FileUpload` per firma/documento) — solo restyle.
- Granularità `prd.json` Ralph: un unico file, eseguito in ordine EPIC 0 → A → B → C.
