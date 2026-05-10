<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrenotazioneStatus;
use Database\Factories\PrenotazioneHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PrenotazioneStatus|null $status_from
 * @property PrenotazioneStatus $status_to
 */
class PrenotazioneHistory extends Model
{
    /** @use HasFactory<PrenotazioneHistoryFactory> */
    use HasFactory;

    protected $table = 'prenotazione_history';

    public $timestamps = false;

    /** @var string[] */
    protected $dates = ['created_at'];

    protected $fillable = [
        'prenotazione_id',
        'user_id',
        'status_from',
        'status_to',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status_from' => PrenotazioneStatus::class,
            'status_to' => PrenotazioneStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Prenotazione, $this> */
    public function prenotazione(): BelongsTo
    {
        return $this->belongsTo(Prenotazione::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
