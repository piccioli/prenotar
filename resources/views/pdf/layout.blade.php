<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .page { padding: 20mm 20mm 15mm 20mm; }
        .header {
            border-bottom: 2px solid #1a4f8c;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .header .org { font-size: 13pt; font-weight: bold; color: #1a4f8c; }
        .header .subtitle { font-size: 9pt; color: #555; margin-top: 2px; }
        h1 { font-size: 12pt; font-weight: bold; margin-bottom: 12px; color: #1a4f8c; text-transform: uppercase; }
        h2 { font-size: 10pt; font-weight: bold; margin-bottom: 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-top: 14px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data td { padding: 4px 6px; vertical-align: top; }
        table.data td.label { width: 38%; font-weight: bold; color: #333; }
        table.data td.value { width: 62%; border-bottom: 1px solid #ccc; }
        .footer { margin-top: 24px; font-size: 8pt; color: #888; border-top: 1px solid #eee; padding-top: 6px; text-align: center; }
        .firma-block { margin-top: 30px; }
        .firma-line { border-top: 1px solid #333; width: 200px; margin-top: 40px; }
        .firma-label { font-size: 9pt; color: #555; margin-top: 4px; }
        .note-box { background: #f5f5f5; border: 1px solid #ddd; padding: 8px; margin-top: 10px; font-size: 9pt; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">CAI — Club Alpino Italiano · GR Lombardia</div>
        <div class="subtitle">Gestione torri di arrampicata mobile CityWall</div>
    </div>
    @yield('content')
    <div class="footer">Documento generato automaticamente da Prenotar — CAI GR Lombardia</div>
</div>
</body>
</html>
