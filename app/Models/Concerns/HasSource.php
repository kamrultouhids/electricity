<?php

namespace App\Models\Concerns;

/**
 * Tracks how a record was created — typed in by a user or brought in by a
 * CSV import. Shared by models that support both entry paths.
 */
trait HasSource
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_CSV = 'csv';

    public const SOURCES = [
        self::SOURCE_MANUAL => 'Manual',
        self::SOURCE_CSV    => 'CSV Import',
    ];

    /**
     * Whether the record came from a CSV import.
     */
    public function isImported(): bool
    {
        return $this->source === self::SOURCE_CSV;
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? 'Manual';
    }
}
