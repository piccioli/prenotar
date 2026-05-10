<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('gr.emails_notifiche_gr', ['gr_cai_lombardia@cai.it']);
        $this->migrator->add('gr.emails_assicurazione', [
            'info@lazzaroassicurazioni.it',
            'assicurazioni@cai.it',
        ]);
        $this->migrator->add('gr.firma_presidente_path', null);
        $this->migrator->add('gr.documento_presidente_path', null);
        $this->migrator->add('gr.presidente_nome', null);
        $this->migrator->add('gr.presidente_nato_a', null);
        $this->migrator->add('gr.presidente_data_nascita', null);
        $this->migrator->add('gr.giorni_minimi_caricamento_documenti', 10);
        $this->migrator->add('gr.ore_minime_richiesta_assicurazione', 48);
    }
};
