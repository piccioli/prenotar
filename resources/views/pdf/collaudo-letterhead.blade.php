<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 15mm 20mm 20mm 20mm; }

        header { text-align: center; margin-bottom: 14px; }
        header img { height: 55px; }

        footer {
            margin-top: 24px; padding-top: 10px; border-top: 1px solid #eee;
            text-align: center; font-size: 8pt; font-style: italic; color: #1D574B; line-height: 1.5;
        }

        main h1 { font-size: 17pt; color: #1D574B; margin-bottom: 4px; }
        main h2 { font-size: 12.5pt; color: #1D574B; margin-top: 20px; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        main h3 { font-size: 11pt; color: #1a1a1a; margin-top: 14px; margin-bottom: 5px; }
        main p { margin-bottom: 7px; }
        main hr { border: none; border-top: 1px solid #ddd; margin: 16px 0; }

        main table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9pt; }
        main table th, main table td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        main table th { background: #f1f7f5; color: #1D574B; }

        main ul, main ol { margin-left: 18px; margin-bottom: 8px; }
        main li { margin-bottom: 3px; }
        main li.task-list-item { list-style: none; margin-left: -18px; }
        main input[type="checkbox"] { margin-right: 6px; }

        main code { background: #f5f5f5; padding: 1px 4px; border-radius: 2px; font-size: 9pt; }
        main strong { color: #1a1a1a; }
        main blockquote { border-left: 3px solid #C77E2A; padding: 4px 10px; margin: 8px 0; background: #F8ECD9; font-size: 9.5pt; }
    </style>
</head>
<body>
<div class="page">
    <header>
        <img src="{{ $logoPath }}" alt="Montagna Servizi">
    </header>
    <main>
        {!! $content !!}
    </main>
    <footer>
        MONTAGNA SERVIZI S.C.P.A.<br>
        Via Errico Petrella 19 - 20124 - Milano (MI)<br>
        https://montagnaservizi.com<br>
        PEC: montagnaserviziscpa@legalmail.it - email: info@montagnaservizi.com<br>
        C.F./P.IVA: 11790660960
    </footer>
</div>
</body>
</html>
