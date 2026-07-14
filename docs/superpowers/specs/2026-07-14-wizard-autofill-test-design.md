# Acceleratore di test — autofill del wizard prenotazione (solo ambiente locale)

## Contesto e obiettivo

I test manuali del wizard di creazione prenotazione (`CreatePrenotazione` /
`PrenotazioneResource::wizardSteps()`) richiedono l'inserimento ripetuto di
dati validi ad ogni step. Questo rallenta il ciclo di feedback durante lo
sviluppo. Obiettivo: un bottone per-step, disponibile **solo** in ambiente
locale, che compila i campi di quello step con dati di test validi generati
via Faker.

## Perimetro

- Riguarda esclusivamente il wizard di **creazione** (`CreatePrenotazione`,
  `PrenotazioneResource::wizardSteps()`). Il form flat di `EditPrenotazione`
  (allegati, invio richiesta) **non** è nel perimetro: non fa parte del
  "flusso a step" descritto nella richiesta.
- Il bottone compare in testa allo schema dei seguenti step, ciascuno con la
  propria azione indipendente:
  - Manuale d'istruzioni
  - Quando & dove
  - Evento
  - Logistica trasporto
  - Responsabile in loco
- **Escluso**: step "Riepilogo" — nessun campo compilabile, è un riepilogo
  read-only dei dati già inseriti.
- Ogni bottone compila **solo i campi del proprio step** (non l'intero
  wizard in un colpo).

## Vincolo di sicurezza: doppio gate

`->visible(fn () => app()->environment('local'))` nasconde il bottone in UI,
ma da solo non basta: un utente potrebbe forgiare la chiamata Livewire
dell'azione via devtools bypassando la sola visibilità. Ogni action deve
quindi anche fare, come primissima istruzione nel proprio closure:

```php
abort_unless(app()->environment('local'), 404);
```

Nessuna nuova variabile d'ambiente: `APP_ENV` resta l'unica fonte di verità
(già distinto tra `local`, `testing`, produzione/develop).

## Meccanismo tecnico

Nuova classe `App\Support\Testing\PrenotazioneWizardAutofill`, un metodo
statico pubblico per step, firma `(Forms\Set $set, Forms\Get $get): void`
(il metodo per "Manuale" non ha bisogno di `$get`):

- `manuale(Forms\Set $set): void`
- `quandoDove(Forms\Set $set): void`
- `evento(Forms\Set $set, Forms\Get $get): void`
- `logisticaTrasporto(Forms\Set $set, Forms\Get $get): void`
- `responsabileInLoco(Forms\Set $set): void`

Ogni metodo usa `fake('it_IT')` e scrive lo stato via `$set(...)` — nessuna
manipolazione DOM, compatibile con i campi a view custom (radio-card torre,
datepicker Alpine, checkbox con `afterStateUpdated`).

In `PrenotazioneResource::wizardSteps()`, ogni step riceve in testa (prima
delle `Section` esistenti) un blocco:

```php
Forms\Components\Actions::make([
    Forms\Components\Actions\Action::make('autofill_<step>')
        ->label('Compila con dati di test')
        ->icon('heroicon-o-beaker')
        ->color('gray')
        ->visible(fn (): bool => app()->environment('local'))
        ->action(function (Forms\Set $set, Forms\Get $get): void {
            abort_unless(app()->environment('local'), 404);
            PrenotazioneWizardAutofill::<metodo>($set, $get);
            Notification::make()->title('Dati di test inseriti')->success()->send();
        }),
])
```

stesso pattern già usato nel file per `azioneInvioSection()`.

## Dati generati per step (rispettano le regole di validazione esistenti)

- **Manuale d'istruzioni**: `manuale_step_confermato = true`.
- **Quando & dove**:
  - `data_inizio_prenotazione` = oggi + `GrSettings::giorni_minimi_caricamento_documenti`
    (10gg) + offset random 1–20gg;
  - `data_fine_prenotazione` = inizio + random 1–5gg;
  - `torre_id` resta vuoto ("Nessuna preferenza") — è facoltativo, evita di
    dover calcolare a runtime uno slot libero per superare `NoOverlapTorre`.
- **Evento**:
  - `nome_evento`, `descrizione_evento`, `indirizzo_evento` da Faker it_IT;
  - `tipo_evento` random tra le opzioni esistenti (`fiera`,
    `manifestazione_cai`, `evento_promozionale`, `corso`, `altro`);
  - `data_inizio_evento` = `$get('data_inizio_prenotazione')` (rispetta il
    `minDate` del campo reale);
  - `data_fine_evento` = inizio evento + random 1–3gg.
- **Logistica trasporto**:
  - `data_ritiro` = `$get('data_inizio_prenotazione')` (rispetta
    `DataRitiroEntroInizioPrenotazione`);
  - `data_riconsegna` = ritiro + random 1–3gg;
  - `targa_autoveicolo` nel formato standard italiano
    (`fake()->regexify('[A-Z]{2}[0-9]{3}[A-Z]{2}')`);
  - `nome_conducente` da Faker;
  - `patente_be_confermata = true` e `patente_be_dichiarata_at = now()`
    (stessa coppia già gestita da `afterStateUpdated` nel form reale, così
    il riepilogo la mostra come "Dichiarata").
- **Responsabile in loco**:
  - `responsabile_nome`, `responsabile_telefono`, `responsabile_email` da
    Faker;
  - `responsabile_tipo` random tra i case di `ResponsabileTipo`;
  - `responsabile_titolo_cai` da Faker (es. "Istruttore Sezionale").

Se `$get('data_inizio_prenotazione')` non è ancora impostata (step "Evento"
o "Logistica trasporto" compilati senza aver prima compilato "Quando &
dove"), il metodo usa un fallback: oggi + 10gg, per restare comunque valido.

## Test

- **Unit** (`tests/Unit/Support/PrenotazioneWizardAutofillTest.php`): per
  ogni metodo helper, verifica che i valori scritti tramite un `Set`/`Get`
  fittizio rispettino i vincoli di business (`data_ritiro <=
  data_inizio_prenotazione`, `data_fine_evento >= data_inizio_evento`,
  formato targa valido). Nessun DB coinvolto, pura logica.
- **Feature** (`tests/Feature/Sezione/PrenotazioneWizardAutofillTest.php`,
  `RefreshDatabase` su MariaDB reale):
  - con `app()->environment()` forzato a `local` a inizio test, l'azione è
    visibile e, se invocata, produce uno stato del form compilato e valido
    (submit senza errori di validazione);
  - con l'ambiente di test di default (`testing`), l'azione risulta non
    visibile **e** una chiamata Livewire forzata all'azione restituisce
    404 — verifica del doppio gate.

## Fuori perimetro (YAGNI)

- Nessun autofill dell'intero wizard in un colpo.
- Nessun autofill degli allegati (`EditPrenotazione`), fuori dal flusso a
  step.
- Nessun flag/env var dedicato oltre ad `APP_ENV`.
