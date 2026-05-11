<?php

declare(strict_types=1);

namespace App\Services\Import\Exceptions;

use RuntimeException;

final class ImportAlreadyDoneException extends RuntimeException
{
    public function __construct(string $hash)
    {
        parent::__construct("File già importato (MD5: {$hash}). Usa --force per reimportare.");
    }
}
