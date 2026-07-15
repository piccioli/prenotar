@extends('pdf.layout')

@section('content')
<h1>Richiesta utilizzo parete mobile CityWall</h1>

<h2>Richiedente</h2>
<table class="data">
    <tr>
        <td class="label">Sezione / Sottosezione</td>
        <td class="value">{{ $p->proprietario_label }}</td>
    </tr>
    <tr>
        <td class="label">Torre richiesta</td>
        <td class="value">{{ $p->torre?->nome ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Periodo prenotazione</td>
        <td class="value">
            Dal {{ $p->data_inizio_prenotazione->format('d/m/Y') }}
            al {{ $p->data_fine_prenotazione->format('d/m/Y') }}
        </td>
    </tr>
</table>

<h2>Evento</h2>
<table class="data">
    <tr>
        <td class="label">Nome evento</td>
        <td class="value">{{ $p->nome_evento }}</td>
    </tr>
    <tr>
        <td class="label">Tipo evento</td>
        <td class="value">{{ $p->tipo_evento }}</td>
    </tr>
    <tr>
        <td class="label">Indirizzo evento</td>
        <td class="value">{{ $p->indirizzo_evento }}</td>
    </tr>
    <tr>
        <td class="label">Date evento</td>
        <td class="value">
            Dal {{ $p->data_inizio_evento->format('d/m/Y') }}
            al {{ $p->data_fine_evento->format('d/m/Y') }}
        </td>
    </tr>
    @if($p->descrizione_evento)
    <tr>
        <td class="label">Descrizione</td>
        <td class="value">{{ $p->descrizione_evento }}</td>
    </tr>
    @endif
</table>

<h2>Logistica trasporto</h2>
<table class="data">
    <tr>
        <td class="label">Conducente</td>
        <td class="value">{{ $p->nome_conducente ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Targa autoveicolo</td>
        <td class="value">{{ $p->targa_autoveicolo ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Data ritiro</td>
        <td class="value">{{ $p->data_ritiro?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Data riconsegna</td>
        <td class="value">{{ $p->data_riconsegna?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Patente B+E</td>
        <td class="value">
            @if($p->patente_be_dichiarata_at)
                Dichiarata il {{ $p->patente_be_dichiarata_at->format('d/m/Y H:i') }}
            @else
                Non dichiarata
            @endif
        </td>
    </tr>
</table>

<h2>Responsabile in loco</h2>
<table class="data">
    <tr>
        <td class="label">Nome e cognome</td>
        <td class="value">{{ $p->responsabile_nome }}</td>
    </tr>
    <tr>
        <td class="label">Titolo CAI</td>
        <td class="value">{{ $p->responsabile_titolo_cai ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Telefono</td>
        <td class="value">{{ $p->responsabile_telefono ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Email</td>
        <td class="value">{{ $p->responsabile_email ?? '—' }}</td>
    </tr>
</table>

<div class="firma-block">
    <p style="font-size:9pt; color:#555;">
        Il/La sottoscritto/a, Presidente della {{ $p->proprietario_label }},
        dichiara che le informazioni riportate sono veritiere e si impegna al rispetto
        del regolamento d'uso della torre CityWall del CAI GR Lombardia.
    </p>
    <div style="display:table; width:100%; margin-top:20px;">
        <div style="display:table-cell; width:50%;">
            <div class="firma-line"></div>
            <div class="firma-label">
                Il Presidente — {{ $p->sezione?->nominativo ?? $p->sottosezione?->nominativo ?? '' }}
            </div>
        </div>
        <div style="display:table-cell; width:50%; text-align:center;">
            <div class="firma-line" style="margin: 40px auto 0;"></div>
            <div class="firma-label">Luogo e data</div>
        </div>
    </div>
</div>

<div class="note-box" style="margin-top:20px;">
    <strong>Nota:</strong> Stampare, firmare e ricaricare come PDF firmato entro i termini previsti
    dal regolamento GR Lombardia.
</div>
@endsection
