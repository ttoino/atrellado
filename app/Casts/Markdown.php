<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class Markdown implements CastsAttributes
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
        return [
            'raw' => $value ?? '',
            'formatted' => app(MarkdownRenderer::class)->toHtml($value ?? ''),
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
        return is_array($value) ? $value['raw'] : $value;
    }
}
