# Documento di collaudo — Prenotar v0.11.0

| Campo | Valore |
|-------|--------|
| **Versione** | v0.11.0 |
| **Ambiente di collaudo** | https://prenotar.develop.montagnaservizi.com:8443 |
| **Data rilascio su develop** | 15/07/2026 |
| **Tester Sezione** | |
| **Tester GR Lombardia** | |
| **Tester Admin** | |
| **Data collaudo** | |

---

## 1. Come funziona l'ambiente di collaudo

Questa sezione spiega l'ambiente prima di iniziare i test veri e propri: leggila per intero se è la prima volta che fai un collaudo su Prenotar.

### 1.1 Indirizzo di accesso

L'ambiente di collaudo (**develop**) è raggiungibile solo a questo indirizzo, **porta 8443 inclusa**:

```
https://prenotar.develop.montagnaservizi.com:8443/login
```

> ⚠️ Se apri l'indirizzo **senza `:8443`**, il browser mostrerà un errore di certificato ("La connessione non è privata" / `NET::ERR_CERT_COMMON_NAME_INVALID`). Questo perché sulla porta 443 di default risponde il certificato del sito di **produzione**, non quello di develop — i due ambienti condividono lo stesso server ma solo la produzione pubblica le porte standard 80/443. Se vedi quell'errore, controlla di aver scritto la porta.

Un'unica pagina di login (`/login`) serve tutti e tre i ruoli: dopo l'accesso, ciascun utente viene reindirizzato automaticamente al proprio pannello (`/sezione`, `/gr` o `/admin`).

### 1.2 Utenti di test

La password è **`password`** per tutti gli account:

| Ruolo | Email | Note |
|-------|-------|------|
| Sezione | `abbiategrasso@cai.it` | Sez. Abbiategrasso — una qualsiasi delle 152 sezioni reali importate va bene |
| Sottosezione | `infocaiberbenno@gmail.com` | S.Sez. Berbenno — stesso ruolo `sezione`, ma con etichetta "S.SEZ." e ambito ristretto alla propria sottosezione |
| GR Lombardia | `gr@local.test` | Presidente/delegato GR — vede e decide le richieste di tutte le sezioni |
| Admin | `admin@local.test` | Amministratore tecnico — gestione utenti, torri, import Excel, audit log |

Per trovare l'email di una sezione specifica diversa da quelle sopra, chiedi all'amministratore tecnico (Alessio) — l'elenco completo non è pubblicato in questo documento.

### 1.3 Come funzionano i dati di test (il "seeder")

I dati presenti su develop **non sono reali prenotazioni**: sono generati da uno script (`LocalDevSeeder`) che:

- importa l'anagrafica **reale** delle 152 sezioni + 77 sottosezioni CAI Lombardia (nomi ed email sono quelli veri, per poter testare con casi realistici);
- crea gli account admin/GR di test sopra elencati;
- genera automaticamente alcune **prenotazioni finte** di esempio (prefisso `[DEV]`), in vari stati del flusso (Inviata, Approvata, PDF firmato, Assicurazione inviata), utili per vedere subito calendario e liste popolati senza doverle creare a mano.

**Cosa succede quando facciamo un test**: quando crei una prenotazione vera durante il collaudo (senza prefisso `[DEV]`), questa **resta nel database** anche dopo un nuovo rilascio di Prenotar su develop — un normale aggiornamento del codice non cancella i dati. Il reset completo (cancellazione di tutto e rigenerazione dei dati demo) è un'operazione **manuale**, eseguita dall'amministratore tecnico solo quando serve ripartire da una base pulita (tipicamente prima di un nuovo giro di collaudo). In pratica:

- **non dare per scontato che una prenotazione creata oggi sia ancora lì fra una settimana** — tratta i tuoi dati di test come "usa e getta";
- se all'inizio di una sessione di collaudo l'ambiente ti sembra "sporco" (troppe prenotazioni vecchie, stati inconsistenti), chiedi all'amministratore tecnico un reset prima di iniziare.

### 1.4 Come verificare le email (MailPit)

Prenotar invia diverse email (nuova richiesta al GR, approvazione, rifiuto, promemoria, ecc. — vedi elenco al §2). Su develop **nessuna email esce davvero**: viene tutta intercettata da **MailPit**, uno strumento che mostra le email come se fosse una casella di posta, senza spedirle a nessuno.

> ⚠️ **Limite noto**: a differenza dell'ambiente locale di sviluppo, su develop l'interfaccia di MailPit **non è ancora raggiungibile direttamente da browser** con un indirizzo pubblico (è prevista come miglioramento futuro, non ancora implementato). Per consultarla oggi serve un collegamento tecnico (tunnel) che solo l'amministratore tecnico può aprire dal proprio computer. **Prima di iniziare una sessione di collaudo che coinvolge la verifica delle email, coordinati con Alessio**: aprirà la casella MailPit e la condividerà con te (schermo condiviso o accesso temporaneo) per tutta la durata del test.

Quando in questo documento trovi 📧 **Verifica email**, significa: chiedi conferma a chi ha MailPit aperto che il messaggio corrispondente sia arrivato, con l'oggetto e i destinatari indicati.

### 1.5 Come compilare questo documento

Ogni voce di collaudo è **numerata** (colonna **#**) e ha **tre caselle**, una sola delle quali va spuntata:

| Esito | Significato |
|-------|-------------|
| **Passato** | Il comportamento osservato corrisponde esattamente a quanto descritto nella voce. |
| **Fallito** | Il comportamento osservato **non** corrisponde a quanto descritto — è un errore/bug. |
| **Passato con riserva** | Il comportamento è complessivamente accettabile, ma con un dettaglio dubbio, poco chiaro o migliorabile che vale la pena segnalare, pur senza essere un errore bloccante. |

> ⚠️ **Obbligatorio**: per **ogni voce non Passato** (quindi Fallito **o** Passato con riserva), è necessario compilare una riga nella tabella "Note e osservazioni del collaudo" (§7), riportando il **numero della voce** in colonna "Riferimento" e spiegando **il motivo** del fallimento o della riserva. Non lasciare una casella su Fallito/Passato con riserva senza la nota corrispondente: senza motivazione scritta, chi legge il collaudo non può capire cosa sia andato storto né dare seguito alla correzione.

---

## 2. Flusso end-to-end di una prenotazione (Sezione ↔ GR)

Questa è la sequenza principale da testare: segue una prenotazione dalla creazione fino alla conclusione, alternando le azioni tra il tester **Sezione** e il tester **GR Lombardia**. Serve la partecipazione di entrambi i ruoli (anche in momenti diversi, basta rispettare l'ordine).

### 2.1 Creazione della prenotazione (Sezione)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 1 | Accedi come Sezione (o Sottosezione) senza prenotazioni attive in corso. | ☐ | ☐ | ☐ |
| 2 | Da "Prenotazioni" → "Nuova prenotazione". | ☐ | ☐ | ☐ |
| 3 | **Step "Manuale d'istruzioni"**: verifica che il pulsante "Continua" sia disabilitato finché non spunti "Ho letto e compreso il manuale d'istruzioni". Spunta e verifica che si abiliti. | ☐ | ☐ | ☐ |
| 4 | **Step "Quando & dove"**: inserisci data inizio/fine utilizzo (la data inizio deve essere almeno 10 giorni da oggi — provare con una data più vicina deve mostrare un errore di validazione); scegli una torre o lascia "Nessuna preferenza"; verifica che il calendario sotto mostri le occupazioni esistenti. | ☐ | ☐ | ☐ |
| 5 | **Step "Evento"**: compila nome evento, tipo evento (fiera / manifestazione CAI / evento promozionale / corso / altro), indirizzo, date evento (la data inizio evento non può essere precedente alla data inizio prenotazione). | ☐ | ☐ | ☐ |
| 6 | **Step "Logistica trasporto"**: compila nome conducente (obbligatorio); se indichi una data di ritiro, verifica che **non possa essere successiva** alla data di inizio prenotazione (deve bloccare con un messaggio esplicativo); spunta la dichiarazione di possesso patente B+E (obbligatoria per proseguire). | ☐ | ☐ | ☐ |
| 7 | **Step "Responsabile in loco"**: compila nome, qualifica CAI (istruttore / accompagnatore / soccorso alpino / altro), telefono ed email (tutti obbligatori tranne il titolo CAI). | ☐ | ☐ | ☐ |
| 8 | **Step "Riepilogo"**: verifica che tutti i dati inseriti siano corretti nel riepilogo, poi clicca "Salva come bozza". | ☐ | ☐ | ☐ |
| 9 | Verifica che la prenotazione risulti in stato **Bozza**. | ☐ | ☐ | ☐ |

### 2.2 Caricamento delibera e invio al GR (Sezione)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 10 | Nella pagina di modifica della bozza, carica un file nella sezione "Delibera del Consiglio Direttivo" (obbligatoria). | ☐ | ☐ | ☐ |
| 11 | Verifica che il pulsante "Invia richiesta al GR" sia **disabilitato finché la delibera non è caricata**, e che si abiliti subito dopo. | ☐ | ☐ | ☐ |
| 12 | Clicca "Invia richiesta al GR" e conferma. | ☐ | ☐ | ☐ |
| 13 | Verifica che lo stato passi a **Inviata**. | ☐ | ☐ | ☐ |
| 14 | 📧 **Verifica email**: il GR (destinatari configurati in Impostazioni GR, di default `gr@local.test`) riceve un'email con oggetto **"[Prenotar] Nuova richiesta: `<nome evento>`"**. | ☐ | ☐ | ☐ |

### 2.3 Blocco seconda prenotazione (Sezione)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 15 | Con la stessa sezione (che ora ha una prenotazione attiva), prova ad aprire di nuovo "Nuova prenotazione". | ☐ | ☐ | ☐ |
| 16 | Verifica che compaia subito un messaggio di blocco con nome evento/date/link alla prenotazione esistente, **senza mostrare il form**. | ☐ | ☐ | ☐ |

### 2.4 Approvazione o rifiuto (GR Lombardia)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 17 | Accedi come GR Lombardia, vai su "Prenotazioni" → tab "Da approvare": verifica che la richiesta appena inviata sia presente. | ☐ | ☐ | ☐ |
| 18 | Apri il dettaglio della richiesta e verifica dati/allegati/storico. | ☐ | ☐ | ☐ |
| 19 | **Caso rifiuto**: clicca "Rifiuta", inserisci un motivo (obbligatorio — provare a confermare senza motivo deve bloccare) e conferma. | ☐ | ☐ | ☐ |
| 20 | Verifica che lo stato passi a **Annullata**. | ☐ | ☐ | ☐ |
| 21 | 📧 **Verifica email**: la sezione riceve un'email con oggetto **"[Prenotar] Richiesta rifiutata: `<nome evento>`"**, contenente il motivo indicato. | ☐ | ☐ | ☐ |
| 22 | **Caso approvazione** (ripetere la creazione al §2.1–2.2 per una nuova richiesta, se il caso sopra l'ha già rifiutata): clicca "Approva" (puoi opzionalmente cambiare la torre assegnata in questo passaggio) e conferma. | ☐ | ☐ | ☐ |
| 23 | Verifica che lo stato passi ad **Approvata**. | ☐ | ☐ | ☐ |
| 24 | 📧 **Verifica email**: la sezione riceve un'email con oggetto **"[Prenotar] Richiesta approvata: `<nome evento>`"**. | ☐ | ☐ | ☐ |

### 2.5 Riassegnazione torre e modifica date (GR Lombardia)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 25 | Su una prenotazione **Approvata**, usa l'azione "Riassegna torre" per cambiarla. | ☐ | ☐ | ☐ |
| 26 | Verifica che la torre risulti aggiornata nel dettaglio. | ☐ | ☐ | ☐ |
| 27 | 📧 **Verifica email**: la sezione riceve un'email con oggetto **"[Prenotar] Torre riassegnata: `<nome evento>`"** (torre precedente vs nuova). | ☐ | ☐ | ☐ |
| 28 | Usa l'azione "Modifica date trasporto" per cambiare ritiro/riconsegna, inserendo un motivo (obbligatorio). | ☐ | ☐ | ☐ |
| 29 | Verifica che le nuove date siano salvate. | ☐ | ☐ | ☐ |
| 30 | 📧 **Verifica email**: la sezione riceve un'email con oggetto **"[Prenotar] Modifica date trasporto: `<nome evento>`"**, con date vecchie/nuove e motivo. | ☐ | ☐ | ☐ |

### 2.6 Caricamento PDF firmato (Sezione)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 31 | Come sezione proprietaria, sulla prenotazione **Approvata**, scarica/genera e poi carica il PDF "Richiesta parete" firmato tramite l'azione "Carica PDF firmato". | ☐ | ☐ | ☐ |
| 32 | Verifica che lo stato passi a **Inviato PDF firmato**. | ☐ | ☐ | ☐ |
| 33 | 📧 **Verifica email**: il GR riceve un'email con oggetto **"[Prenotar] PDF firmato caricato: `<nome evento>`"**. | ☐ | ☐ | ☐ |

### 2.7 Invio assicurazione — Modulo 3 (GR Lombardia)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 34 | Sulla prenotazione **Inviato PDF firmato**, usa l'azione "Invia all'assicurazione": verifica che nella conferma siano mostrati i destinatari configurati (email assicurazione). | ☐ | ☐ | ☐ |
| 35 | Conferma l'invio. | ☐ | ☐ | ☐ |
| 36 | Verifica che lo stato passi a **Inviato assicurazione**. | ☐ | ☐ | ☐ |
| 37 | 📧 **Verifica email**: email con oggetto **"Modulo 3 — attivazione polizze trasporto `<torre>`"**, inviata alle email assicurazione, **in copia alla sezione proprietaria**, con un PDF "Modulo 3" allegato (e, se caricato nelle Impostazioni GR, il documento d'identità del presidente). | ☐ | ☐ | ☐ |

### 2.8 Conclusione (GR Lombardia)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 38 | Sulla prenotazione **Inviato assicurazione**, usa l'azione "Segna come conclusa" (normalmente questo passaggio avviene da solo automaticamente dopo la data fine evento; qui lo forziamo manualmente per il test). | ☐ | ☐ | ☐ |
| 39 | Verifica che lo stato passi a **Conclusa** e che la prenotazione compaia ora nella tab "Archivio" per entrambi i ruoli, non più tra le attive. | ☐ | ☐ | ☐ |
| 40 | Verifica che **non** arrivi nessuna email per questa transizione (non è previsto un avviso di conclusione). | ☐ | ☐ | ☐ |

### 2.9 Blocco sovrapposizione torre (Sezione)

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 41 | Prova a creare una nuova prenotazione (con un'altra sezione, dato che quella usata sopra ha già una prenotazione attiva) scegliendo la **stessa torre** e un periodo che si sovrappone a una prenotazione già Inviata/Approvata su quella torre. | ☐ | ☐ | ☐ |
| 42 | Verifica che il sistema blocchi con un messaggio che invita a scegliere un altro periodo o un'altra torre. | ☐ | ☐ | ☐ |

---

## 3. Altri casi di collaudo — Pannello Sezione

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 43 | **"Le mie prenotazioni"**: verifica le tab "Attive" e "Archivio" (Concluse e Annullate vanno in Archivio), ordinamento dalla più vicina. | ☐ | ☐ | ☐ |
| 44 | **Calendario torri**: verifica che gli eventi delle proprie prenotazioni mostrino il titolo completo, mentre quelli di altre sezioni mostrino solo il nome della torre (senza altri dettagli, per riservatezza). | ☐ | ☐ | ☐ |
| 45 | **Dashboard**: verifica la card della prenotazione attiva (se presente) con torre, deposito, stato, ed eventuale avviso di scadenza per il caricamento del PDF firmato. | ☐ | ☐ | ☐ |
| 46 | **Catalogo torri** (sola lettura): verifica che si possano consultare nome, indirizzo deposito, descrizione, foto/schede tecniche/manuale, ma **nessuna azione di creazione/modifica** sia disponibile. | ☐ | ☐ | ☐ |
| 47 | **Eliminazione bozza**: crea una bozza e usa "Elimina bozza" (disponibile solo in stato Bozza) — verifica che venga rimossa e non sia più consultabile. | ☐ | ☐ | ☐ |

---

## 4. Altri casi di collaudo — Pannello GR Lombardia

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 48 | **Calendario torri (vista GR)**: verifica che qui, a differenza della vista Sezione, i titoli completi siano visibili per **tutte** le prenotazioni di **tutte** le sezioni, non solo le proprie. | ☐ | ☐ | ☐ |
| 49 | **Dashboard**: verifica i contatori (richieste da approvare, approvate nei prossimi 30 giorni) e che la lista delle richieste in attesa mostri per prima quella più urgente (evento più vicino). | ☐ | ☐ | ☐ |
| 50 | **Catalogo torri** (sola lettura): come per la Sezione, nessuna azione di creazione/modifica. | ☐ | ☐ | ☐ |
| 51 | **Impostazioni GR Lombardia**: modifica le email di notifica GR e le email assicurazione (campi a tag, più indirizzi) e verifica che vengano salvate. | ☐ | ☐ | ☐ |
| 52 | Modifica l'anagrafica del presidente (nome, luogo/data di nascita) e carica firma/documento d'identità di prova. | ☐ | ☐ | ☐ |
| 53 | Modifica i parametri operativi (giorni minimi caricamento documenti, ore minime richiesta assicurazione) e verifica che il salvataggio mostri una notifica di successo. | ☐ | ☐ | ☐ |
| 54 | Crea una nuova prenotazione da Sezione **dopo** aver cambiato i giorni minimi e verifica che il vincolo sulla data minima di inizio prenotazione rifletta il nuovo valore. | ☐ | ☐ | ☐ |

---

## 5. Casi di collaudo — Pannello Admin

| # | Verifica | Passato | Fallito | Passato con riserva |
|---|----------|:---:|:---:|:---:|
| 55 | **Import Excel**: da "Import Excel" → "Carica nuovo Excel", carica un file di prova; verifica che compaia nel log import con righe importate/aggiornate/in errore. Prova anche l'opzione "forza re-import" su un file già importato. | ☐ | ☐ | ☐ |
| 56 | **Gestione utenti — creazione/modifica**: crea un nuovo utente e modificane uno esistente. | ☐ | ☐ | ☐ |
| 57 | **Gestione utenti — reset password**: usa l'azione "Reset password" su un utente. | ☐ | ☐ | ☐ |
| 58 | 📧 **Verifica email**: l'utente riceve un'email con oggetto **"Imposta la tua password — Prenotar CAI Lombardia"**. | ☐ | ☐ | ☐ |
| 59 | Verifica che l'azione compaia nell'Audit log (evento `user.reset_password`). | ☐ | ☐ | ☐ |
| 60 | **Gestione utenti — attiva/disattiva**: disattiva un utente di prova inserendo un motivo (obbligatorio) e verifica che non possa più accedere; riattivalo. | ☐ | ☐ | ☐ |
| 61 | **Impersona**: usa "Impersona" su un utente Sezione o GR e verifica di navigare come se fossi quell'utente; termina l'impersonificazione e verifica il ritorno all'account admin. Verifica che l'inizio/fine impersonificazione sia tracciata in Audit log. | ☐ | ☐ | ☐ |
| 62 | **Torri — CRUD completo**: crea una torre di prova, modificane una esistente (nome, indirizzo deposito, colore, specifiche, foto/manuale/schede PDF), disattivala e riattivala. | ☐ | ☐ | ☐ |
| 63 | **Audit log**: verifica di poter filtrare per tipo evento e data, e che le azioni sopra (reset password, toggle attivo/disattivo, impersonate, import Excel) siano tutte tracciate con autore e timestamp. | ☐ | ☐ | ☐ |
| 64 | **Prenotazioni — azioni straordinarie**: "Force stato…" su una prenotazione di prova: verifica che richieda un motivo di almeno 10 caratteri, che aggiorni lo stato bypassando il flusso normale, e che compaia una riga `[FORCE STATE]` nello storico. | ☐ | ☐ | ☐ |
| 65 | "Elimina definitivamente…" su una prenotazione di prova (**azione irreversibile** — usare solo su dati di test, mai su prenotazioni reali): verifica che venga rimossa e che l'Audit log conservi comunque uno snapshot completo dei dati eliminati. | ☐ | ☐ | ☐ |
| 66 | **Test email**: dalla pagina "Test email", invia un messaggio di prova a un indirizzo a piacere. | ☐ | ☐ | ☐ |
| 67 | 📧 **Verifica email**: il messaggio arriva su MailPit con l'oggetto/corpo indicati. | ☐ | ☐ | ☐ |
| 68 | Verifica che l'invio sia tracciato in Audit log (evento `email.test`). | ☐ | ☐ | ☐ |
| 69 | **Stato sistema (dashboard)**: verifica i contatori (utenti attivi/totali/disattivati, ultimo import Excel, errori recenti, coda job Horizon) e gli ultimi eventi di sistema. | ☐ | ☐ | ☐ |
| 70 | **Horizon**: verifica che il link in dashboard porti alla UI di Horizon (monitoraggio code) e che sia raggiungibile solo per l'admin. | ☐ | ☐ | ☐ |

---

## 6. Verifiche interne già effettuate

Prima della consegna per il collaudo, ogni funzionalità sopra descritta è coperta da:
- suite di test automatici (290 test, tutti superati);
- analisi statica del codice (Larastan livello 6, 0 errori);
- controllo formattazione codice (Pint, superato);
- verifica visiva end-to-end in browser del flusso di prenotazione e del nuovo logo su tutti e tre i pannelli.

Queste verifiche coprono la correttezza tecnica; il collaudo funzionale sopra descritto — in particolare l'esperienza d'uso reale e la ricezione effettiva delle email — resta a carico del cliente.

---

## 7. Note e osservazioni del collaudo

Compila una riga per **ogni voce numerata** segnata come **Fallito** o **Passato con riserva** (vedi §1.5). Il numero in "Riferimento" deve corrispondere alla colonna **#** della voce.

| # | Riferimento (voce) | Esito | Descrizione del motivo | Gravità |
|---|---------------------|-------|-------------------------|---------|
| | | | | |

---

## 8. Approvazione

Confermo che i casi sopra relativi al mio ruolo sono stati verificati, che ogni voce non "Passato" è motivata in §7, e che il comportamento osservato è complessivamente conforme a quanto descritto.

| Ruolo | Nome e cognome | Data | Firma |
|-------|----------------|------|-------|
| Sezione | | | |
| GR Lombardia | | | |
| Admin | | | |
