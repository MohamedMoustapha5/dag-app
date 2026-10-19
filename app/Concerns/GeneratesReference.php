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

    public static function generateReference(): string
    {
        $year = now()->year;
        $sequence = static::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%d-%04d', static::referencePrefix(), $year, $sequence);
    }

    abstract public static function referencePrefix(): string;
}
