<?php

namespace App\Models;

use App\Casts\Datetime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use Prunable;

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => Datetime::class,
            'created_at' => Datetime::class,
        ];
    }

    // The parent fills a Carbon, which the class-cast cache then returns
    // verbatim, bypassing the Datetime cast on serialization of this instance.
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()->toISOString()])->save();
        }
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}
