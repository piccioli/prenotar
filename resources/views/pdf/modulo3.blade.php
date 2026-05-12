@extends('pdf.layout')

@section('content')
<h1>Modulo 3 — Richiesta attivazione polizze trasporto</h1>

<p style="font-size:9pt; color:#555; margin-bottom:14px;">
    Richiesta di attivazione polizze assicurative per il trasporto della torre di arrampicata mobile.
</p>

<h2>Richiedente</h2>
<table class="data">
    <tr>
        <td class="label">Presidente GR Lombardia</td>
        <td class="value">{{ $settings->presidente_nome ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Nato a</td>
        <td class="value">{{ $settings->presidente_nato_a ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Data di nascita</td>
        <td class="value">
            @if($settings->presidente_data_nascita)
                {{ \Carbon\Carbon::parse($settings->presidente_data_nascita)->format('d/m/Y') }}
            @else
                —
            @endif
        </td>
    </tr>
</table>

<h2>Bene da assicurare</h2>
<table class="data">
    <tr>
        <td class="label">Descrizione</td>
        <td class="value">Torre di arrampicata mobile CityWall (CST) — valore €&nbsp;38.131,10</td>
    </tr>
    <tr>
        <td class="label">Azienda proprietaria</td>
        <td class="value">Montagna Servizi S.r.l.</td>
    </tr>
    <tr>
        <td class="label">Torre</td>
        <td class="value">{{ $p->torre?->nome ?? '—' }}</td>
    </tr>
</table>

<h2>Viaggio e periodo</h2>
<table class="data">
    <tr>
        <td class="label">Partenza (ritiro)</td>
        <td class="value">
            {{ $p->luogo_ritiro ?? '—' }}
            @if($p->data_ritiro) — {{ $p->data_ritiro->format('d/m/Y') }} @endif
        </td>
    </tr>
    <tr>
        <td class="label">Destinazione (evento)</td>
        <td class="value">{{ $p->indirizzo_evento ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Rientro (riconsegna)</td>
        <td class="value">
            {{ $p->luogo_riconsegna ?? '—' }}
            @if($p->data_riconsegna) — {{ $p->data_riconsegna->format('d/m/Y') }} @endif
        </td>
    </tr>
    <tr>
        <td class="label">Periodo (dal / al)</td>
        <td class="value">
            {{ $p->data_inizio_prenotazione->format('d/m/Y') }}
            — {{ $p->data_fine_prenotazione->format('d/m/Y') }}
        </td>
    </tr>
</table>

<h2>Veicolo</h2>
<table class="data">
    <tr>
        <td class="label">Azienda trasporto</td>
        <td class="value">{{ $p->azienda_trasporto ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Targa autoveicolo</td>
        <td class="value">{{ $p->targa_autoveicolo ?? '—' }}</td>
    </tr>
</table>

<h2>Sezione richiedente</h2>
<table class="data">
    <tr>
        <td class="label">Sezione / Sottosezione</td>
        <td class="value">{{ $p->proprietario_label }}</td>
    </tr>
    <tr>
        <td class="label">Evento</td>
        <td class="value">{{ $p->nome_evento }}</td>
    </tr>
</table>

<div class="firma-block">
    <p style="font-size:9pt; color:#555; margin-bottom:6px;">
        Il Presidente del GR Lombardia
    </p>
    @if($settings->firma_presidente_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($settings->firma_presidente_path))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('local')->path($settings->firma_presidente_path) }}"
             style="max-height:60px; max-width:200px; margin-top:8px;" alt="Firma Presidente GR">
    @else
        <div class="firma-line"></div>
    @endif
    <div class="firma-label" style="margin-top:6px;">
        {{ $settings->presidente_nome ?? '___________________________' }}
    </div>
</div>
@endsection
