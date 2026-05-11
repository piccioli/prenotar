@component('mail::message')
# Benvenuto in Prenotar — CAI GR Lombardia

Ciao **{{ $user->name }}**,

il tuo account per la piattaforma di prenotazione delle torri di arrampicata del **CAI GR Lombardia** è stato creato.

Per accedere, imposta la tua password cliccando il bottone qui sotto. Il link è valido per **7 giorni**.

@component('mail::button', ['url' => $url])
Imposta la tua password
@endcomponent

Se non hai richiesto questo account, ignora questa email.

---
*Piattaforma Prenotar — Club Alpino Italiano GR Lombardia*
@endcomponent
