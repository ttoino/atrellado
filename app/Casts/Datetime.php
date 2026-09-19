<?php

namespace App\Casts;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Datetime implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return $value;
        }

        $carbon = Carbon::parse($value);

        return [
            'iso' => $carbon->toISOString(),
            'long_diff' => $carbon->diffForHumans(['aUnit' => true]),
            'diff' => $carbon->diffForHumans(['aUnit' => true, 'short' => true, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]),
            'datetime' => $carbon->isoFormat('MMM D Y, H:mm'),
            'date' => $carbon->isoFormat('MMM D Y'),
            'time' => $carbon->isoFormat('H:mm'),
        ];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  mixed  $value
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return is_array($value) ? $value['iso'] : ($value instanceof Carbon ? $value->toISOString() : $value);
    }
}
