<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * A model's 'date' cast always serializes through Carbon's default JSON
 * format — full ISO8601 with a midnight time component (e.g.
 * "2026-01-10T00:00:00.000000Z") — which reads wrong for a plain calendar
 * date. Overriding it per-field here (rather than on the model's own
 * serializeDate()) keeps real timestamps like created_at/posted_at/
 * voided_at at full precision; only genuinely date-only columns go through
 * this.
 */
trait FormatsPlainDates
{
    /**
     * @param  string[]  $fields  Names of 'date'-cast attributes to reformat.
     */
    protected function withPlainDates(Model $model, array $fields): array
    {
        $data = $model->toArray();

        foreach ($fields as $field) {
            if ($model->{$field} !== null) {
                $data[$field] = $model->{$field}->toDateString();
            }
        }

        return $data;
    }
}
