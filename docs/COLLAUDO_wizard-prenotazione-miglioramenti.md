# Documento di collaudo — Miglioramenti al wizard di prenotazione

## Contesto

Durante la sessione di test guidato del **11/05/2026** con CAI GR Lombardia (Valentino Poli, Enrico Sala/Renato) sono emersi quattro problemi puntuali nel wizard "Nuova prenotazione" del pannello Sezioni. Questo documento riporta le correzioni implementate e le modalità per verificarle (collaudo) prima del rilascio in produzione.

| Campo | Valore |
|-------|--------|
| **Ambiente di collaudo** | https://prenotar.develop.montagnaservizi.com:8443 |
| **Data implementazione** | 08/07/2026 |
| **Tester** | |
| **Data collaudo** | |

---

## 1. Blocco anticipato se la sezione ha già una prenotazione attiva

**Problema segnalato:** una sezione che aveva già una prenotazione attiva scopriva il blocco solo alla fine della compilazione del form, dopo aver inserito tutti i dati.

**Soluzione implementata:** il controllo viene ora eseguito subito, prima ancora che il form venga mostrato. Se la sezione (o sottosezione) ha già una prenotazione attiva, compare direttamente un messaggio con il nome dell'evento, le date e un link alla prenotazione esistente, senza alcun campo da compilare.

**Come verificare:**
- [ ] Accedere con una sezione che ha già una prenotazione in stato diverso da *Annullata* o *Conclusa*.
- [ ] Cliccare "Nuova prenotazione".
- [ ] Verificare che compaia subito il messaggio di blocco (nome evento, date, link alla prenotazione esistente) e **nessun campo del form**.
- [ ] Accedere con una sezione senza prenotazioni attive e verificare che il wizard si apra normalmente, senza regressioni.

---

## 2. Dichiarazione di mezzo privato con categoria patente

**Problema segnalato:** il form del responsabile logistico assumeva sempre un mezzo aziendale (Montagna Servizi); non era possibile dichiarare un mezzo privato con la relativa patente.

**Soluzione implementata:** nello step "Logistica/Trasporto" del wizard è ora selezionabile il "Tipo mezzo": **Aziendale** (comportamento invariato) oppure **Privato**. Selezionando "Privato" diventa obbligatorio indicare la categoria di patente (**B** o **B+E**). Il dato è visibile anche nel riepilogo finale del wizard e nel dettaglio della prenotazione lato GR.

**Come verificare:**
- [ ] Nello step trasporto, selezionare "Aziendale" → verificare che il comportamento sia quello di sempre (nessun campo patente richiesto).
- [ ] Selezionare "Privato" → verificare che compaia il campo "Categoria patente" e che sia obbligatorio (provare a proseguire senza sceglierla: il sistema deve bloccare con un messaggio).
- [ ] Completare con categoria patente selezionata e verificare che il salvataggio riesca.
- [ ] Verificare che "Tipo mezzo" e "Categoria patente" siano visibili nel riepilogo finale del wizard e nel dettaglio prenotazione lato GR.

---

## 3. Rimozione del campo "Codice CAI" del responsabile

**Problema segnalato:** il campo "codice CAI" del responsabile non era verificabile da nessuno e generava solo falsa sicurezza sui dati ("può mettere qualsiasi numero, non c'è modo di controllarlo").

**Soluzione implementata:** il campo è stato rimosso completamente dal form, dal riepilogo, dal dettaglio prenotazione (Sezione e GR) e dal PDF "Richiesta parete". Il campo "Titolo CAI" del responsabile (istruttore, accompagnatore, ecc.), diverso e tuttora utile, resta invariato.

**Come verificare:**
- [ ] Nello step "Responsabile in loco" del wizard, verificare che il campo "Codice CAI" non sia più presente.
- [ ] Verificare che non compaia più nel dettaglio prenotazione (Sezione e GR).
- [ ] Generare il PDF "Richiesta parete" di una prenotazione e verificare che non citi più il codice CAI.
- [ ] Verificare che il campo "Titolo CAI" (istruttore/accompagnatore) sia ancora presente e funzionante.

---

## 4. Conferma obbligatoria di lettura del manuale d'istruzioni della torre

**Problema segnalato:** non esisteva un controllo che garantisse che chi prenota avesse letto il manuale d'istruzioni/uso della torre prima di procedere, causando telefonate di supporto per problemi già coperti dal manuale.

**Soluzione implementata:** nello step di selezione torre del wizard compare ora un link per visualizzare/scaricare il manuale della torre scelta, accanto a una checkbox "Ho letto e compreso il manuale d'istruzioni". Il pulsante "Successivo" resta disabilitato finché la checkbox non viene spuntata. Se l'utente cambia torre, la conferma va ripetuta per la nuova torre selezionata.

**Come verificare:**
- [ ] Nello step di selezione torre, selezionare una torre con manuale caricato → verificare che compaia il link al manuale.
- [ ] Verificare che il pulsante "Successivo" sia disabilitato finché la checkbox non è spuntata.
- [ ] Spuntare la checkbox e verificare che sia possibile proseguire.
- [ ] Cambiare la torre selezionata dopo aver spuntato la checkbox → verificare che la conferma si resetti e vada ripetuta.
- [ ] (se applicabile) Selezionare una torre senza manuale caricato → verificare che compaia un messaggio "manuale non ancora disponibile" senza bloccare in modo anomalo il wizard.

---

## Verifiche interne già effettuate

Prima della consegna per il collaudo, ogni correzione è stata verificata con:
- suite di test automatici (235 test, tutti superati);
- analisi statica del codice (0 errori);
- controllo formattazione codice (superato).

Queste verifiche coprono la correttezza tecnica; il collaudo funzionale sopra descritto resta a carico del cliente sui casi d'uso reali.

---

## Note e osservazioni del collaudo

| # | Descrizione | Gravità | Riferimento |
|---|-------------|---------|-------------|
| | | | |

---

## Approvazione

Confermo che le voci sopra sono state verificate e che le correzioni sono conformi a quanto segnalato nella sessione di test dell'11/05/2026.

| Campo | Valore |
|-------|--------|
| **Nome e cognome** | |
| **Ruolo** | |
| **Data** | |
| **Firma** | |
