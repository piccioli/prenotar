# UAT Checklist — Prenotar Fase 8

Usare una copia di questo file per ogni sezione pilota (es. `UAT_sezione-lecco.md`).

---

## Sessione UAT

| Campo | Valore |
|-------|--------|
| **Sezione pilota** | |
| **Data** | |
| **Tester** | |
| **Ambiente** | https://staging.prenotar.montagnaservizi.it |
| **Versione** | Prenotar v0.8.0 |

---

## Golden path — Flusso completo prenotazione

### Accesso e primo login

- [ ] Login con email sezione + password ricevuta dal GR → accesso al pannello `/sezione`
- [ ] (se primo accesso) Link "Imposta password" arriva via email → click → scelta password → login riuscito

### Creazione prenotazione

- [ ] Clicca "Nuova prenotazione" → wizard si apre correttamente
- [ ] **Passo 1**: seleziona torre, inserisce date coerenti (ritiro ≤ inizio evento, riconsegna ≥ fine evento) → vai avanti
- [ ] **Passo 2**: inserisce nome evento, date evento, luogo → vai avanti
- [ ] **Passo 3**: inserisce responsabili ritiro e riconsegna → vai avanti
- [ ] **Passo 4**: riepilogo corretto → salva bozza → prenotazione in stato **BOZZA** visibile nella lista

### Caricamento delibera e invio

- [ ] Apre dettaglio prenotazione → sezione "Allegati" → carica PDF delibera del consiglio
- [ ] Pulsante "Invia richiesta" diventa attivo dopo il caricamento
- [ ] Clicca "Invia richiesta" → status passa a **INVIATA**
- [ ] GR riceve email di notifica nuova richiesta (verificare Mailpit `http://127.0.0.1:8027`)

### Approvazione GR

- [ ] Login GR (`gr@local.test` / `password`) → pannello `/gr` → prenotazione visibile
- [ ] GR clicca "Approva" → inserisce note → conferma
- [ ] Status passa a **APPROVATA**
- [ ] Sezione riceve email di approvazione (verificare Mailpit)

### PDF firmato (T-10)

- [ ] Sezione carica PDF Richiesta parete firmato → status passa a **INVIATO_PDF_FIRMATO**
- [ ] GR riceve email di notifica documento caricato

### Invio assicurazione (GR, T-48h)

- [ ] GR clicca "Genera Modulo 3" → PDF scaricato correttamente
- [ ] GR clicca "Invia all'assicurazione" → status passa a **INVIATO_ASSICURAZIONE**
- [ ] Prenotazione visibile nel calendario con colore corretto

### Conclusione automatica

- [ ] (smoke test) Eseguire `php artisan prenotazioni:nightly` su prenotazione con `data_fine_evento` passata → status passa a **CONCLUSO**, prenotazione in tab "Archivio"

---

## Edge case

- [ ] **Sovrapposizione torre**: crea prenotazione su stesse date di una esistente per la stessa torre → sistema blocca con messaggio di errore
- [ ] **Sottosezione**: login con account sottosezione → label "S.SEZ. X" corretta nella UI
- [ ] **Email fallback**: login con account che ha `email_is_fallback=true` → icona avviso visibile in `/admin`
- [ ] **Reminder T-10**: prenotazione in stato APPROVATA con evento a T-10 giorni → email reminder arriva, flag `reminder_t10_inviato_at` valorizzato, re-run nightly non duplica email
- [ ] **Reminder T-2gg**: prenotazione in stato INVIATO_PDF_FIRMATO con ritiro a T-2gg → email reminder al GR, flag `reminder_t2gg_inviato_at` valorizzato, idempotente
- [ ] **Impersonate** (admin): login admin → "Impersona" utente sezione → accede al pannello `/sezione` come quella sezione

---

## Polish UI

- [ ] Tutte le date nelle tabelle mostrano formato `dd/mm/aaaa` (non ISO)
- [ ] Le tabelle vuote mostrano un messaggio "Nessuna prenotazione" (non tabella vuota senza indicazione)
- [ ] Menu di navigazione ordine: **Prenotazioni** → **Calendario** → **Torri** (su `/sezione` e `/gr`)
- [ ] Etichette coerenti: la sezione "Torri" si chiama "Torri" (non "Carte d'identità torri")
- [ ] Wizard: il campo "Passo successivo" indica chiaramente di caricare la delibera
- [ ] Responsive mobile (iPhone/Android): le principali schermate sono usabili su smartphone

---

## Verifiche Horizon (solo admin)

- [ ] Login admin → menu "Diagnostica" → "Horizon" si apre in nuovo tab → dashboard visibile
- [ ] I supervisori `supervisor-default` e `supervisor-archive` risultano attivi (status verde)
- [ ] Login GR/sezione → tentativo accesso a `/horizon` → errore 403

---

## Note e bug rilevati

| # | Descrizione | Gravità | Riferimento Issue |
|---|-------------|---------|-------------------|
| | | | |

---

## Sign-off

Confermo che il flusso UAT è stato completato e le voci spuntate sono state verificate.

| Campo | Valore |
|-------|--------|
| **Nome e cognome** | |
| **Ruolo** | |
| **Data** | |
| **Firma** | |
