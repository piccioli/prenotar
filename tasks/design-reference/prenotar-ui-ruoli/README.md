# Design reference — Restyle UI 3 ruoli + mobile

Materiale importato dal progetto claude.ai/design **"Design UI ruoli"**
(`7f12a5f8-2fc0-4d50-b996-b907078b91e3`), riferimento per `prd-restyle-ux-pannelli-filament.md`.

- `pannello-sezione.dc.html` — mockup desktop pannello `/sezione` (8 schermate: dashboard con/senza prenotazione attiva, wizard step 1/3/5, modifica/allegati, lista, calendario).
- `pannello-gr.dc.html` — mockup desktop pannello `/gr` (6 schermate: dashboard, lista, dettaglio, storico, calendario, impostazioni).
- `pannello-admin.dc.html` — mockup desktop pannello `/admin` (6 schermate: dashboard, utenti, torri, prenotazioni, import Excel, audit log).
- `mobile-3-ruoli.dc.html` — mockup mobile a 390px dei 3 pannelli (7 schermate campione: dashboard/wizard/lista Sezione, dashboard/dettaglio GR, dashboard/utenti Admin) — pattern di riferimento per topbar+hamburger, bottom tab bar, FAB, liste a card, step wizard full-screen.
- `tokens/*.css` — design token Montagna Servizi (colori, tipografia, spaziatura, effetti/radii/shadow) usati in tutti i mockup.

Questi file sono materiale di progettazione (HTML statico generato da claude.ai/design, non renderizzabile standalone — referenzia asset `_ds/...` del progetto originale). Vanno letti come riferimento visivo/strutturale durante l'implementazione, non eseguiti o serviti dall'app.
