<?php

namespace App\Models\Concerns;

/**
 * `name` stays the canonical (English) value used for matching; `label` is what people see,
 * in Tamil when the request's locale is Tamil and a Tamil name exists.
 */
trait HasTamilName
{
    public function initializeHasTamilName(): void
    {
        $this->append('label');
    }

    public function getLabelAttribute(): string
    {
        return app()->getLocale() === 'ta' && ! empty($this->attributes['name_ta'] ?? null)
            ? $this->attributes['name_ta']
            : (string) ($this->attributes['name'] ?? '');
    }
}
