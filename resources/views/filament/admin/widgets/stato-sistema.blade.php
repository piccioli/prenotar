<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-extrabold tracking-tight" style="color:var(--stone-900)">Stato del sistema</h2>
        <p class="text-[15px]" style="color:var(--stone-600)">Panoramica tecnica della piattaforma.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Utenti attivi --}}
        <div class="flex flex-col gap-2 rounded-2xl border bg-white p-5 dark:bg-gray-900" style="border-color:var(--stone-200)">
            <div class="flex items-center gap-2 text-[12.5px] font-extrabold uppercase tracking-wide" style="color:var(--stone-500)">
                <x-filament::icon icon="heroicon-o-users" class="h-4 w-4" />
                Utenti attivi
            </div>
            <div class="text-[28px] font-extrabold leading-none" style="color:var(--stone-900)">
                {{ $this->getUtentiAttivi() }} <span class="text-sm font-semibold" style="color:var(--stone-500)">/ {{ $this->getUtentiTotali() }}</span>
            </div>
            <p class="text-[12.5px]" style="color:var(--stone-500)">{{ $this->getUtentiDisattivati() }} disattivati</p>
        </div>

        {{-- Ultimo import Excel --}}
        @php $ultimoImport = $this->getUltimoImport(); @endphp
        <div class="flex flex-col gap-2 rounded-2xl border bg-white p-5 dark:bg-gray-900" style="border-color:var(--stone-200)">
            <div class="flex items-center gap-2 text-[12.5px] font-extrabold uppercase tracking-wide" style="color:var(--stone-500)">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-4 w-4" />
                Ultimo import Excel
            </div>
            @if($ultimoImport)
                <div class="text-[19px] font-extrabold leading-tight" style="color:var(--stone-900)">
                    {{ $ultimoImport->created_at->translatedFormat('d M Y, H:i') }}
                </div>
                <p class="flex items-center gap-1.5 text-[12.5px] font-bold" style="color:{{ $ultimoImport->righe_in_errore > 0 ? 'var(--larch-700)' : 'var(--green-700)' }}">
                    <x-filament::icon :icon="$ultimoImport->righe_in_errore > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle'" class="h-3.5 w-3.5" />
                    {{ $ultimoImport->righe_importate }} righe, {{ $ultimoImport->righe_in_errore }} errori
                </p>
            @else
                <div class="text-[19px] font-extrabold leading-tight" style="color:var(--stone-900)">—</div>
                <p class="text-[12.5px]" style="color:var(--stone-500)">Nessun import ancora eseguito</p>
            @endif
        </div>

        {{-- Errori recenti --}}
        @php $erroriRecenti = $this->getErroriRecenti(); @endphp
        <div class="flex flex-col gap-2 rounded-2xl border-[1.5px] bg-white p-5 dark:bg-gray-900" style="border-color:{{ $erroriRecenti > 0 ? 'var(--larch-200)' : 'var(--stone-200)' }}">
            <div class="flex items-center gap-2 text-[12.5px] font-extrabold uppercase tracking-wide" style="color:{{ $erroriRecenti > 0 ? 'var(--larch-700)' : 'var(--stone-500)' }}">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4" />
                Errori recenti (7 gg)
            </div>
            <div class="text-[28px] font-extrabold leading-none" style="color:var(--stone-900)">{{ $erroriRecenti }}</div>
            <a href="{{ $this->getUrlAuditLog() }}" class="text-[12.5px] font-bold underline" style="color:var(--larch-700)">Vedi nel log →</a>
        </div>

        {{-- Coda job Horizon --}}
        <div class="flex flex-col gap-2 rounded-2xl border bg-white p-5 dark:bg-gray-900" style="border-color:var(--stone-200)">
            <div class="flex items-center gap-2 text-[12.5px] font-extrabold uppercase tracking-wide" style="color:var(--stone-500)">
                <x-filament::icon icon="heroicon-o-cpu-chip" class="h-4 w-4" />
                Coda job (Horizon)
            </div>
            <div class="text-[28px] font-extrabold leading-none" style="color:var(--stone-900)">
                {{ $this->getJobInAttesa() }} <span class="text-sm font-semibold" style="color:var(--stone-500)">in attesa</span>
            </div>
            <a href="{{ $this->getUrlHorizon() }}" target="_blank" class="text-[12.5px] font-bold underline" style="color:var(--larch-700)">Apri Horizon ↗</a>
        </div>
    </div>

    {{-- Ultimi eventi di sistema --}}
    <div class="overflow-hidden rounded-2xl border bg-white dark:bg-gray-900" style="border-color:var(--stone-200)">
        <div class="flex items-center justify-between border-b px-6 py-4" style="border-color:var(--stone-200)">
            <h3 class="text-[16px] font-extrabold" style="color:var(--stone-900)">Ultimi eventi di sistema</h3>
            <a href="{{ $this->getUrlAuditLog() }}" class="text-[13.5px] font-bold underline" style="color:var(--larch-700)">Audit log completo</a>
        </div>

        @forelse($this->getUltimiEventi() as $evento)
            <div class="grid grid-cols-1 gap-2 border-b px-6 py-3.5 last:border-b-0 md:grid-cols-[190px_1fr_220px] md:items-center md:gap-4" style="border-color:var(--stone-100)">
                <span class="text-[13px]" style="color:var(--stone-500)">{{ $evento->created_at->diffForHumans() }}</span>
                <span class="text-sm" style="color:var(--stone-800)">
                    @if($evento->causer)
                        <strong>{{ $evento->causer->name }}</strong>
                    @endif
                    {{ $evento->description }}
                </span>
                <span class="w-fit rounded-full px-2.5 py-1 text-[12.5px] font-extrabold uppercase tracking-wide" style="background-color:var(--stone-100); color:var(--stone-600)">
                    {{ $evento->event ?? $evento->log_name }}
                </span>
            </div>
        @empty
            <p class="px-6 py-8 text-center text-sm" style="color:var(--stone-500)">Nessun evento registrato.</p>
        @endforelse
    </div>
</div>
