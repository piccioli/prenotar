# Linee guida presidenti — Prenotar

Guida all'uso di **Prenotar**, il sistema di prenotazione delle 2 torri di arrampicata mobili CityWall del CAI GR Lombardia.

---

## 1. Accesso alla piattaforma

**URL produzione**: https://prenotar.montagnaservizi.it

Al primo accesso riceverai un'email con oggetto **"Imposta la tua password"** contenente un link personale (valido 24 ore). Clicca il link e scegli una password sicura.

> Se non hai ricevuto l'email o il link è scaduto, contatta il GR Lombardia (vedi §6).

**Account con email automatica**: alcune sezioni non hanno un indirizzo email nel registro CAI. In questo caso l'account viene creato con un'email generata automaticamente. Contatta il GR per ricevere la password e, se vuoi, per aggiornare il tuo indirizzo email reale.

---

## 2. Creare una prenotazione (wizard in 4 passi)

Dal pannello `/sezione`, clicca **"Nuova prenotazione"** in alto a destra.

### Passo 1 — Periodo e torre

- **Data inizio / fine prenotazione**: periodo in cui la torre sarà in uso dalla tua sezione.
- **Data ritiro / riconsegna**: date di presa e restituzione della torre al deposito.
  - Il ritiro deve essere almeno il giorno prima dell'inizio evento.
  - La riconsegna deve essere almeno il giorno dopo la fine evento.
- **Torre**: scegli Torre 1 o Torre 2 (disponibilità verificata automaticamente — il calendario mostra le prenotazioni esistenti).

### Passo 2 — Evento

- **Nome evento**: es. "Giornata arrampicata 2026", "Corso base giugno".
- **Data inizio / fine evento**: date dell'evento pubblico (possono coincidere con il periodo di prenotazione).
- **Luogo evento**: indirizzo o nome della location.
- **Tipo parete 1 e 2**: configura le due pareti della torre (tipo di parete, altezza, ecc.).

### Passo 3 — Responsabili

Indica chi ritira e chi riconsegna la torre:

- **Responsabile ritiro**: nome, cognome, telefono.
- **Responsabile riconsegna**: nome, cognome, telefono (può essere la stessa persona).

### Passo 4 — Riepilogo e salvataggio

Controlla i dati e clicca **"Salva bozza"**.

> Dopo il salvataggio, torna nella pagina della prenotazione e carica la **delibera del consiglio** (PDF) tramite il pulsante "Carica delibera". Solo dopo il caricamento potrai inviare la richiesta al GR.

---

## 3. Inviare la richiesta al GR

Una volta caricata la delibera, clicca **"Invia richiesta"** per passare la prenotazione allo stato **INVIATA**. Il GR Lombardia riceverà una notifica email e valuterà la richiesta.

Riceverai email di aggiornamento ad ogni cambio di stato.

---

## 4. Tempi e scadenze

| Scadenza | Cosa fare | Azione |
|----------|-----------|--------|
| **T-10 giorni** dall'evento | Caricare il PDF firmato dal presidente di sezione | Pannello `/sezione` → prenotazione → "Carica PDF firmato" |
| **T-48 ore** dall'evento | Il GR invia la richiesta all'assicurazione | Azione GR (non richiede intervento della sezione) |

> Se mancano i documenti entro T-10 giorni, riceverai un **promemoria automatico** via email.

---

## 5. Documenti richiesti

| Documento | Quando | Chi lo carica |
|-----------|--------|---------------|
| **Delibera del consiglio** | Al momento della richiesta | Presidente sezione |
| **PDF Richiesta parete firmato** | Entro T-10 giorni dall'evento | Presidente sezione |

I documenti si caricano dalla pagina di dettaglio della prenotazione (pulsanti nella sezione "Allegati").

---

## 6. Calendario e disponibilità

Dal menu, clicca **"Calendario torri"** per visualizzare tutte le prenotazioni in corso per Torre 1 e Torre 2 (colori distinti). Puoi filtrare per torre.

Se la tua data richiesta è già occupata, seleziona date alternative. Il sistema blocca automaticamente le sovrapposizioni.

---

## 7. Archivio prenotazioni

Le prenotazioni concluse o annullate vengono archiviate automaticamente e sono visibili nella tab **"Archivio"** del pannello `/sezione`.

---

## 8. Contatti

**GR Lombardia** — per approvazioni, problemi con i documenti, informazioni sul ritiro/riconsegna:
- Email: configurata nelle impostazioni GR (visibile nel footer delle email automatiche)

**Supporto tecnico** — per problemi di accesso o malfunzionamenti della piattaforma:
- Email: alessiopiccioli@webmapp.it

---

## FAQ

**Non ricevo l'email di impostazione password.**
→ Controlla la cartella spam. Se l'account ha un'email generata automaticamente, contatta il GR per ricevere le credenziali via altro canale.

**Ho sbagliato una data nella prenotazione.**
→ Se la prenotazione è ancora in stato **BOZZA**, puoi modificarla liberamente. Se è già stata inviata, contatta il GR: potrà rifiutarla così potrai crearne una nuova corretta.

**Non riesco a inviare la richiesta (pulsante grigio).**
→ Verifica di aver caricato la delibera del consiglio. Solo con la delibera allegata il pulsante "Invia richiesta" si attiva.

**La mia sezione è una sottosezione.**
→ L'accesso e il processo sono identici. Il tuo account è associato alla sottosezione di riferimento.

**Posso prenotare entrambe le torri contemporaneamente?**
→ Ogni sezione può avere una sola prenotazione attiva per volta. Per prenotare entrambe le torri nello stesso periodo contatta il GR.

---

*Documento generato per la Fase 8 UAT — Prenotar v0.8.0 — CAI GR Lombardia*

---

> **Generazione PDF** (per committente):
> `pandoc LINEE_GUIDA_PRESIDENTI.md -o LINEE_GUIDA_PRESIDENTI.pdf --pdf-engine=xelatex`
> Il PDF non è committato nel repository.
