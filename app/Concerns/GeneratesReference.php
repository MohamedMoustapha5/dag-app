<?php

namespace App\Concerns;

/**
 * Generates a human-readable sequential reference such as DAG-CTR-2026-0231
 * on model creation, when the model does not already have one set.
 */
trait GeneratesReference
{
    protected static function bootGeneratesReference(): void
    {
        static::creating(function ($model) {
            if (empty($model->reference)) {
                $model->reference = static::generateReference();
            }
        });
    }

    /**
     * The next number follows the highest reference issued this year, not
     * the number of rows: once a record is deleted, counting would hand out
     * a reference that is already taken.
     */
    public static function generateReference(): string
    {
        $prefixe = sprintf('%s-%d-', static::referencePrefix(), now()->year);

        $derniere = static::query()
            ->where('reference', 'like', $prefixe.'%')
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $derniere === null ? 1 : (int) substr($derniere, strlen($prefixe)) + 1;

        return sprintf('%s%04d', $prefixe, $sequence);
    }

    abstract public static function referencePrefix(): string;
}
