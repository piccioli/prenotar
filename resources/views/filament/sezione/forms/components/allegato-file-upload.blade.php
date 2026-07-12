@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Facades\FilamentView;

    $id = $getId();
    $imageCropAspectRatio = $getImageCropAspectRatio();
    $imageResizeTargetHeight = $getImageResizeTargetHeight();
    $imageResizeTargetWidth = $getImageResizeTargetWidth();
    $isAvatar = $isAvatar();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $hasImageEditor = $hasImageEditor();
    $hasCircleCropper = $hasCircleCropper();

    $alignment = $getAlignment() ?? Alignment::Start;

    if (! $alignment instanceof Alignment) {
        $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
    }

    $mediaItems = $getRecord()?->getMedia($getCollection()) ?? collect();
    $presente = $mediaItems->isNotEmpty();

    $bordo = match (true) {
        $presente => 'border:1px solid var(--stone-200);background:#FFFFFF',
        $obbligatorio => 'border:1.5px solid var(--larch-200);background:var(--larch-100)',
        default => 'border:1px dashed var(--stone-300);background:var(--stone-50)',
    };

    $coloreIcona = match (true) {
        $presente => 'var(--green-600)',
        $obbligatorio => 'var(--larch-700)',
        default => 'var(--stone-500)',
    };

    $coloreSottotitolo = match (true) {
        $presente => 'var(--stone-600)',
        $obbligatorio => 'var(--stone-700)',
        default => 'var(--stone-500)',
    };
@endphp

{{-- Nessun editor immagine per questi allegati (solo PDF/JPEG/PNG senza ->image()): la modale
     di ritaglio del file-upload.blade.php vendor non serve mai qui, quindi non è duplicata. --}}
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-3.5 rounded-xl p-4" style="{{ $bordo }}">
            <x-filament::icon
                :icon="$presente ? 'heroicon-o-document-check' : $iconaMancante"
                class="h-[22px] w-[22px] flex-shrink-0"
                style="color:{{ $coloreIcona }}"
            />

            <div class="flex flex-1 flex-col gap-0.5">
                <div class="text-sm {{ $obbligatorio ? 'font-extrabold' : 'font-bold' }}" style="color:var(--stone-900)">
                    {{ $titolo }}
                    @if ($obbligatorio)
                        <span style="color:var(--danger-600)">*</span>
                    @endif
                </div>

                <div class="text-xs" style="color:{{ $coloreSottotitolo }}">
                    @if ($presente)
                        @if ($mediaItems->count() === 1)
                            {{ $mediaItems->first()->file_name }} · caricato il {{ $mediaItems->first()->created_at->format('d/m/Y') }}
                        @else
                            {{ $mediaItems->count() }} documenti caricati
                        @endif
                    @else
                        {{ $sottotitoloMancante }}
                    @endif
                </div>
            </div>
        </div>

        <div
            @if (FilamentView::hasSpaMode())
                {{-- format-ignore-start --}}x-load="visible || event (ax-modal-opened)"{{-- format-ignore-end --}}
            @else
                x-load
            @endif
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('file-upload', 'filament/forms') }}"
            x-data="fileUploadFormComponent({
                        acceptedFileTypes: @js($getAcceptedFileTypes()),
                        imageEditorEmptyFillColor: @js($getImageEditorEmptyFillColor()),
                        imageEditorMode: @js($getImageEditorMode()),
                        imageEditorViewportHeight: @js($getImageEditorViewportHeight()),
                        imageEditorViewportWidth: @js($getImageEditorViewportWidth()),
                        deleteUploadedFileUsing: async (fileKey) => {
                            return await $wire.deleteUploadedFile(@js($statePath), fileKey)
                        },
                        getUploadedFilesUsing: async () => {
                            return await $wire.getFormUploadedFiles(@js($statePath))
                        },
                        hasImageEditor: @js($hasImageEditor),
                        hasCircleCropper: @js($hasCircleCropper),
                        canEditSvgs: @js($canEditSvgs()),
                        isSvgEditingConfirmed: @js($isSvgEditingConfirmed()),
                        confirmSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.confirmation')),
                        disabledSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.disabled')),
                        imageCropAspectRatio: @js($imageCropAspectRatio),
                        imagePreviewHeight: @js($getImagePreviewHeight()),
                        imageResizeMode: @js($getImageResizeMode()),
                        imageResizeTargetHeight: @js($imageResizeTargetHeight),
                        imageResizeTargetWidth: @js($imageResizeTargetWidth),
                        imageResizeUpscale: @js($getImageResizeUpscale()),
                        isAvatar: @js($isAvatar),
                        isDeletable: @js($isDeletable()),
                        isDisabled: @js($isDisabled),
                        isDownloadable: @js($isDownloadable()),
                        isMultiple: @js($isMultiple()),
                        isOpenable: @js($isOpenable()),
                        isPasteable: @js($isPasteable()),
                        isPreviewable: @js($isPreviewable()),
                        isReorderable: @js($isReorderable()),
                        itemPanelAspectRatio: @js($getItemPanelAspectRatio()),
                        loadingIndicatorPosition: @js($getLoadingIndicatorPosition()),
                        locale: @js(app()->getLocale()),
                        panelAspectRatio: @js($getPanelAspectRatio()),
                        panelLayout: @js($getPanelLayout()),
                        placeholder: @js($getPlaceholder()),
                        maxFiles: @js($getMaxFiles()),
                        maxSize: @js(($size = $getMaxSize()) ? "{$size}KB" : null),
                        minSize: @js(($size = $getMinSize()) ? "{$size}KB" : null),
                        mimeTypeMap: @js($getMimeTypeMap()),
                        maxParallelUploads: @js($getMaxParallelUploads()),
                        removeUploadedFileUsing: async (fileKey) => {
                            return await $wire.removeFormUploadedFile(@js($statePath), fileKey)
                        },
                        removeUploadedFileButtonPosition: @js($getRemoveUploadedFileButtonPosition()),
                        reorderUploadedFilesUsing: async (files) => {
                            return await $wire.reorderFormUploadedFiles(@js($statePath), files)
                        },
                        shouldAppendFiles: @js($shouldAppendFiles()),
                        shouldOrientImageFromExif: @js($shouldOrientImagesFromExif()),
                        shouldTransformImage: @js($imageCropAspectRatio || $imageResizeTargetHeight || $imageResizeTargetWidth),
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                        uploadButtonPosition: @js($getUploadButtonPosition()),
                        uploadingMessage: @js($getUploadingMessage()),
                        uploadProgressIndicatorPosition: @js($getUploadProgressIndicatorPosition()),
                        uploadUsing: (fileKey, file, success, error, progress) => {
                            $wire.upload(
                                `{{ $statePath }}.${fileKey}`,
                                file,
                                () => {
                                    success(fileKey)
                                },
                                error,
                                (progressEvent) => {
                                    progress(true, progressEvent.detail.progress, 100)
                                },
                            )
                        },
                    })"
            wire:ignore
            wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.{{
                substr(md5(serialize([
                    $isDisabled,
                ])), 0, 64)
            }}"
            {{
                $attributes
                    ->merge([
                        'aria-labelledby' => "{$id}-label",
                        'id' => $id,
                        'role' => 'group',
                    ], escape: false)
                    ->merge($getExtraAttributes(), escape: false)
                    ->merge($getExtraAlpineAttributes(), escape: false)
                    ->class([
                        'fi-fo-file-upload flex flex-col gap-y-2 [&_.filepond--root]:font-sans',
                        match ($alignment) {
                            Alignment::Start, Alignment::Left => 'items-start',
                            Alignment::Center => 'items-center',
                            Alignment::End, Alignment::Right => 'items-end',
                            default => $alignment,
                        },
                    ])
            }}
        >
            <div
                @class([
                    'h-full',
                    'w-32' => $isAvatar,
                    'w-full' => ! $isAvatar,
                ])
            >
                <input
                    x-ref="input"
                    {{
                        $getExtraInputAttributeBag()
                            ->merge([
                                'aria-labelledby' => "{$id}-label",
                                'disabled' => $isDisabled,
                                'multiple' => $isMultiple(),
                                'type' => 'file',
                            ], escape: false)
                    }}
                />
            </div>

            <div
                x-show="error"
                x-text="error"
                x-cloak
                class="text-sm text-danger-600 dark:text-danger-400"
            ></div>
        </div>
    </div>
</x-dynamic-component>
