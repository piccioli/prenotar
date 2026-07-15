import preset from '../../../vendor/filament/support/tailwind.config.preset';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/vendor/filament-panels/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
};
