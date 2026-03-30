<?php

declare(strict_types=1);

namespace App\Services\RH;

final class ImportContractResult
{
    public function __construct(
        public readonly int $imported,
        public readonly int $updated,
        public readonly int $skipped,
        public readonly array $errors,
        public readonly array $categoryCounts,
        public readonly int $committedValuesImported,
        public readonly int $committedValuesSkipped,
    ) {}
}
