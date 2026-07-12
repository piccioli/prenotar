# Prompt per Claude (design) — Mockup UI Prenotar per ruolo

> Questo documento è pensato per essere incollato come prompt in una conversazione separata con Claude (con la skill di frontend-design attiva), per generare mockup/wireframe UI dei 3 pannelli Filament di Prenotar, uno alla volta per ruolo. Contiene tutto il contesto di dominio necessario: non serve accedere al codice.

## 1. Cos'è Prenotar

Prenotar è il sistema con cui il **CAI Gruppo Regionale (GR) Lombardia** gestisce la prenotazione delle sue **2 torri di arrampicata mobili** (CityWall) da parte delle 152 sezioni e 77 sottosezioni CAI della regione. Non è un prodotto consumer: è uno strumento verticale, a basso traffico (~230 account totali), usato da volontari e dirigenti associativi — spesso non giovanissimi e non particolarmente esperti di software gestionali.

Il sistema ha **3 pannelli separati e mutuamente esclusivi** (nessun ruolo eredita le funzioni di un altro), ciascuno con URL, colore e scopo diversi:

| Pannello | Ruolo | Colore attuale | Scopo |
|---|---|---|---|
| `/admin` | `admin` | Ambra | Gestione tecnica: utenti, import Excel, audit log, impersonate |
| `/gr` | `gr_manager` | Verde | Approvare/rifiutare prenotazioni, generare PDF, gestire assicurazione |
| `/sezione` | `sezione` + `sottosezione` | Blu | Creare e gestire le proprie prenotazioni tramite un wizard |

## 2. Vincoli tecnici (importanti per il design)

- Il frontend è **Filament 3** (Livewire + Alpine + Tailwind). I mockup devono essere **realizzabili con i pattern di Filament** (form Wizard, Resource/Table, Infolist a tab, widget dashboard, FullCalendar) — non proporre pattern che richiederebbero un frontend custom da zero (es. drag&drop complessi, animazioni elaborate, layout non a griglia standard).
- Va bene reimmaginare **spaziatura, gerarchia visiva, colori, iconografia, micro-copy, stato vuoto, densità delle tabelle** — non l'architettura dei componenti.
- L'utenza include persone poco avvezze al digitale: preferire chiarezza e leggibilità a densità informativa o effetti estetici.
- Branding: logo CAI GR Lombardia (aquila + stella alpina, stile araldico), nessuna palette ufficiale rigida oltre a quella già scelta per i 3 pannelli (tabella sopra) — proponi pure una palette coerente e più curata di quella attuale (ambra/verde/blu sono placeholder di sviluppo, non scelte definitive).

## 3. Cosa NON replicare (piattaforma legacy)

La piattaforma precedente (screenshot allegati separatamente, non in questo file) aveva questi problemi UX/UI noti, che Prenotar deve risolvere — tienili presenti come "anti-pattern" nei mockup:

| Bug | Problema | Fix atteso nel design |
|---|---|---|
| BUG-01 | Prenotazioni ordinate dalla più lontana nel tempo | Ordinamento default dalla più vicina (data inizio DESC) |
| BUG-02 | Prenotazioni passate/annullate mescolate a quelle attive | Tab "Archivio" separata, vista principale solo attive |
| BUG-03 | Un secondo campo "info parete" sovrascriveva il primo (stato condiviso) | UI che rende esplicito quale dato appartiene a quale sezione/step |
| BUG-04 | Data di riconsegna diversa tra form e calendario | Un solo punto di verità mostrato in entrambi i contesti |
| BUG-05 | Sottosezioni etichettate come fossero sezioni | Etichetta sempre distinta "S.SEZ. ..." con sezione di riferimento visibile |
| BUG-06 | Nessun modo per il supporto tecnico di vedere cosa vede l'utente | (funzionale, non di design — impersonate già previsto) |
| BUG-07 | **Calendario duplicato**, una tab per torre, invece di un calendario unico | Calendario unico con le 2 torri distinte **a colori**, non a tab |
| BUG-08 | Indirizzo di deposito della torre poco visibile | Deve comparire in modo prominente ovunque sia rilevante (dettaglio torre, riepilogo prenotazione) |
| BUG-09 | "Soccorso Alpino" non selezionabile come tipo responsabile | (funzionale, già risolto — nessun impatto grafico) |

Nota tecnica aggiuntiva emersa dall'implementazione attuale, utile per il design: nel calendario i colori delle torri (blu/arancio/verde/viola, assegnati per ordine id) **non coincidono** con i colori badge usati nelle tabelle per le stesse torri (badge semantici Filament tipo info/warning). Il design dovrebbe **unificare la codifica colore delle torri** in tutta l'app (stesso colore per la stessa torre, ovunque compaia: calendario, badge tabella, dettaglio prenotazione).

## 4. Output atteso

Per ciascun ruolo (sezioni 5-8 sotto), genera un **mockup HTML** (o un set di schermate) che copra le schermate elencate, con:
- Layout di navigazione (sidebar/topbar) coerente col colore del pannello
- Almeno una schermata di **lista/tabella** con dati di esempio realistici (usa i nomi di campo indicati)
- Almeno una schermata di **dettaglio o form** con i campi indicati
- Stati vuoti e badge di stato coerenti con gli stati della prenotazione (vedi tabella stati sotto)
- Dark/light mode non necessario (l'app gira solo su desktop in orario ufficio, non è un requisito) — ma va bene se lo includi

### Stati della prenotazione (validi in tutti i pannelli, badge coerenti)

| Stato | Colore badge suggerito | Significato |
|---|---|---|
| Bozza | grigio | Creata dalla sezione, non ancora inviata |
| Inviata | giallo/warning | In attesa di approvazione GR |
| Approvata | verde | Approvata dal GR, torre assegnata |
| Annullata | rosso | Rifiutata dal GR o annullata |
| PDF firmato inviato | azzurro/info | Sezione ha caricato il PDF firmato della delibera |
| Inviata all'assicurazione | blu | GR ha inviato il Modulo 3 all'assicurazione |
| Concluso | grigio scuro | Evento passato, archiviato automaticamente |

---

## 5. Ruolo: Admin (`/admin`, colore ambra)

**Chi è**: il responsabile tecnico della piattaforma (non un dirigente CAI). Profilo più tecnico degli altri 3 ruoli, ma non deve creare prenotazioni: solo amministrare il sistema.

**Schermate da disegnare**:
1. **Dashboard/Home** — nessun widget dedicato oggi: proponi una home con stato di sistema (utenti attivi, ultimo import Excel, eventuali errori recenti)
2. **Gestione utenti** (tabella) — colonne: nome, email, ruolo/i, sezione/sottosezione di appartenenza, stato attivo/disattivato; azioni riga: **impersona** (badge/icona ben distinta, azione "pericolosa" da rendere visivamente riconoscibile), reset password, attiva/disattiva (con motivazione)
3. **Gestione torri** — CRUD completo: nome, descrizione, **indirizzo di deposito** (deve essere prominente, BUG-08), foto, PDF specifiche tecniche, PDF manuale d'istruzioni, toggle attiva/non attiva
4. **Prenotazioni (sola lettura + azioni straordinarie)** — tabella di tutte le prenotazioni di tutte le sezioni; azioni eccezionali "force stato" (richiede motivazione, va disegnata come azione distinta/da usare con cautela) ed "elimina definitivamente" (conferma forte, snapshot in audit log)
5. **Log import Excel** — tabella storica: data, righe importate/aggiornate/in errore, con possibilità di vedere il dettaglio errori
6. **Audit log** — tabella eventi (chi ha fatto cosa, quando), filtrabile per tipo evento e intervallo date
7. **Pagina test email** e link a **Horizon** (coda job) — anche solo un rimando/voce di menu, non serve mockup dettagliato

**Toni**: densità informativa più alta accettabile (utente tecnico), ma le azioni distruttive/eccezionali (hard delete, force stato, impersonate) devono essere visivamente distinguibili dalle azioni ordinarie.

---

## 6. Ruolo: GR Manager (`/gr`, colore verde)

**Chi è**: il Presidente del Gruppo Regionale CAI Lombardia (o un suo delegato). Dirigente associativo, non tecnico. Usa la piattaforma per **approvare/rifiutare** richieste, non per crearle.

**Schermate da disegnare**:
1. **Dashboard** — widget "Prenotazioni da approvare": lista delle ultime richieste in stato "Inviata" (le più urgenti in cima), contatore totale da approvare, contatore prenotazioni già approvate nei prossimi 30 giorni. Questa è la schermata più importante per questo ruolo: deve rendere immediatamente chiaro "cosa devo fare oggi".
2. **Lista prenotazioni** (tabella, sola lettura + azione "vedi dettaglio") — colonne: sezione/sottosezione richiedente (con etichetta "S.SEZ." se sottosezione + sezione di riferimento, BUG-05), evento, periodo, torre assegnata (colore coerente col calendario), stato; filtri per stato/torre/sezione
3. **Dettaglio prenotazione** (vista a tab: Dettagli / Allegati / Storico) con le azioni disponibili in base allo stato:
   - Su "Inviata": **Approva** (con eventuale riassegnazione torre), **Rifiuta** (motivo obbligatorio)
   - Su "Approvata"/"PDF firmato inviato": **Riassegna torre**, **Modifica date trasporto**
   - Scarica PDF "Richiesta parete" e PDF "Modulo 3"
   - **Invia all'assicurazione** (azione con conferma e riepilogo destinatari email)
   - **Segna come conclusa**
   - Tab "Storico": timeline delle transizioni di stato (autore + data + nota)
4. **Calendario unico** — le 2 torri **a colori distinti** (non a tab separate, BUG-07), vista mensile, click su evento → dettaglio prenotazione
5. **Impostazioni** (pagina a tab): email notifiche GR, email assicurazione (liste), dati anagrafici e firma del Presidente GR (upload documento/firma), parametri operativi (giorni minimi per caricare documenti, ore minime per richiesta assicurazione)

**Toni**: linguaggio istituzionale ma semplice; le azioni di approvazione/rifiuto devono essere il fulcro visivo (call to action primarie, non nascoste in un menu).

---

## 7. Ruolo: Sezione (`/sezione`, colore blu)

**Chi è**: il Presidente di una sezione CAI (una delle 152). Crea e gestisce le prenotazioni della propria sezione. È l'utente meno tecnico e più numeroso: il design deve massimizzare la chiarezza guidata (wizard) più che la densità.

**Schermate da disegnare**:
1. **Dashboard** — se esiste una prenotazione attiva, mostrarla in evidenza (card con stato, torre, date); altrimenti invito chiaro a crearne una nuova; link a "le mie prenotazioni"
2. **Wizard di prenotazione — nuova richiesta**, 5 step con progress indicator sempre visibile:
   - **Step 1 "Quando e dove"**: date inizio/fine, torre (opzionale — "il GR assegnerà"), **anteprima calendario disponibilità** embedded nello step; se si sceglie una torre, appare il link al **manuale d'istruzioni PDF** + una **checkbox obbligatoria "ho letto il manuale"** che blocca l'avanzamento finché non spuntata (cambiare torre resetta la spunta) — rendi questo passaggio molto esplicito visivamente, è un requisito di sicurezza
   - **Step 2 "Evento"**: nome evento, tipo (fiera / manifestazione CAI / evento promozionale / corso / altro), descrizione, indirizzo, date evento
   - **Step 3 "Logistica trasporto"**: date/luoghi di ritiro e riconsegna, azienda trasporto (default "Montagna Servizi"), targa; **toggle "mezzo aziendale / mezzo privato"** — se privato, appare selezione obbligatoria della **categoria patente** (B o B+E)
   - **Step 4 "Responsabile in loco"**: nome, tipo (istruttore / accompagnatore / soccorso alpino / altro), titolo CAI, telefono, email
   - **Step 5 "Riepilogo"**: riepilogo leggibile di tutto quanto inserito, avviso esplicito che dopo il salvataggio come bozza serve caricare la delibera del consiglio per poter inviare la richiesta
3. **Modifica prenotazione / Allegati** — form non-wizard con le stesse sezioni + upload: delibera del consiglio (obbligatoria per invio), autorizzazione suolo pubblico, autorizzazione ZTL, patente del responsabile, altri allegati liberi; azione "Invia richiesta al GR" abilitata solo con delibera caricata; azione "elimina bozza" solo su stato Bozza
4. **Lista "Le mie prenotazioni"** — tabella scoped alla propria sezione, ordinata dalla più vicina (BUG-01), con tab separata "Archivio" per concluse/annullate (BUG-02)
5. **Calendario** — stessa vista a colori delle 2 torri del pannello GR, in sola consultazione

**Toni**: guidare passo passo, ridurre l'ansia da modulo lungo (mostrare sempre "dove sono" nel wizard), rendere impossibile sbagliare i passaggi bloccanti (manuale non letto, patente mancante, delibera mancante).

---

## 8. Ruolo: Sottosezione (stesso pannello `/sezione`, colore blu)

**Chi è**: il Presidente di una sottosezione CAI (una delle 77), dipendente amministrativamente da una sezione "madre". **Non ha un pannello o risorse separate**: usa esattamente le stesse schermate del ruolo Sezione (punto 7), con un'unica differenza da rendere visibile nel design:

- Ovunque compaia il nome del proprietario di una prenotazione (liste, dettaglio, intestazioni), l'etichetta deve essere sempre nella forma **"S.SEZ. {nome sottosezione} (sez. rif. {nome sezione madre})"**, mai il solo nome della sottosezione confuso con una sezione (BUG-05). Nei mockup di questo ruolo, mostra questa etichetta in almeno una lista e in un'intestazione di dettaglio, per verificare che resti leggibile anche con nomi lunghi.

Non serve produrre schermate aggiuntive per questo ruolo: basta un mockup di 1-2 schermate del set del punto 7 con questa etichetta correttamente applicata, per validare che il pattern grafico regga.
