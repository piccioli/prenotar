# Tema Filament — struttura

- `tokens/` — design token Montagna Servizi copiati 1:1 da `tasks/design-reference/prenotar-ui-ruoli/tokens/*.css` (fonte di verità). Non re-inventare valori qui: se la palette/tipografia di riferimento cambia, sincronizzare da lì.
- `{admin,gr,sezione}/theme.css` — un entry point Vite per pannello. Ciascuno importa il tema base di Filament + `tokens/index.css`, poi sovrascrive solo gli alias semantici brand (`--text-brand`, `--surface-brand`, `--surface-brand-strong`, `--surface-inverse`, `--border-strong`, `--focus-ring`, `--surface-subtle`) con la propria scala colore. Le scale grezze (`--green-*`, `--larch-*`, `--stone-*`) restano condivise in `tokens/colors.css`.
- La scala "ardesia" (pannello Sezione) non è nei token condivisi: vive solo in `sezione/theme.css`, derivata a mano dagli hex nel mockup `pannello-sezione.dc.html`.
- Ordine obbligatorio dentro ogni `theme.css`: `@import` (tutti) prima, poi `@config`, poi eventuali override `:root {}` — postcss-import richiede che `@import` preceda ogni altra istruzione.
- `->viteTheme('resources/css/filament/{panel}/theme.css')` nei `*PanelProvider.php` **non** passare un secondo argomento (`buildDirectory`) a meno di configurare `laravel-vite-plugin` per un manifest separato: i 3 pannelli condividono un solo `vite.config.js`/manifest.
- Font Manrope self-hosted via `@fontsource/manrope` (subset `latin.css`), non aggiungere chiamate a Google Fonts o altri CDN esterni.
