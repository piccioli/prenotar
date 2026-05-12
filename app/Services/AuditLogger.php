<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /** @param array<string, mixed> $properties */
    public function logAdminAction(string $event, mixed $subject, string $motivo, array $properties = []): void
    {
        $builder = activity('admin')
            ->causedBy(auth()->user())
            ->withProperties(array_merge($properties, ['motivo' => $motivo]))
            ->event($event);

        if ($subject instanceof Model) {
            $builder = $builder->performedOn($subject);
        }

        $builder->log($event);
    }
}
