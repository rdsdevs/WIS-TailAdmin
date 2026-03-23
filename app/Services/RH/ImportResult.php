<?php

declare(strict_types=1);

namespace App\Services\RH;

final class ImportResult
{
    public function __construct(
        public int $imported = 0,
        public int $updated  = 0,
        public int $skipped  = 0,
        public array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function totalProcessed(): int
    {
        return $this->imported + $this->updated + $this->skipped + count($this->errors);
    }
}
