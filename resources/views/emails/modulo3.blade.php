In allegato il Modulo 3 per l'attivazione delle polizze assicurative relative al trasporto
della torre di arrampicata mobile CityWall.

Dettagli prenotazione:
- Sezione: {{ $prenotazione->proprietario_label }}
- Evento: {{ $prenotazione->nome_evento }}
- Torre: {{ $prenotazione->torre?->nome ?? '—' }}
- Periodo: {{ $prenotazione->data_inizio_prenotazione->format('d/m/Y') }} — {{ $prenotazione->data_fine_prenotazione->format('d/m/Y') }}
- Ritiro: {{ $prenotazione->luogo_ritiro ?? '—' }} {{ $prenotazione->data_ritiro?->format('d/m/Y') ?? '' }}
- Riconsegna: {{ $prenotazione->luogo_riconsegna ?? '—' }} {{ $prenotazione->data_riconsegna?->format('d/m/Y') ?? '' }}

Allegati:
- Modulo3_{{ $prenotazione->id }}.pdf (richiesta attivazione polizze)
@if($settings->documento_presidente_path)
- Documento d'identità del Presidente GR Lombardia
@endif

CAI GR Lombardia
