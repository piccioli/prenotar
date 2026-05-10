<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExcelImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelImport extends Model
{
    /** @use HasFactory<ExcelImportFactory> */
    use HasFactory;

    protected $fillable = [
        'filename',
        'hash',
        'imported_by',
        'righe_importate',
        'righe_aggiornate',
        'righe_in_errore',
        'log',
    ];

    protected function casts(): array
    {
        return [
            'log' => 'array',
            'righe_importate' => 'integer',
            'righe_aggiornate' => 'integer',
            'righe_in_errore' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
