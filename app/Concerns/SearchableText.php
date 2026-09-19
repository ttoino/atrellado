<?php

namespace App\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;

// Replaces the old trigger-maintained fts_search tsvector column with
// driver-aware search: real FTS + rank ordering on pgsql, approximate LIKE
// matching elsewhere (sqlite only runs in tests).
trait SearchableText
{
    public function scopeSearchText(Builder $query, string $term): Builder
    {
        $table = $this->getTable();

        /** @var Connection $connection */
        $connection = $query->getConnection();

        if ($connection->getDriverName() !== 'pgsql') {
            return $query->where(function (Builder $query) use ($table, $term) {
                $query->whereLike("{$table}.name", "%{$term}%")
                    ->orWhereLike("{$table}.description", "%{$term}%");
            });
        }

        return $query
            ->whereFullText(["{$table}.name", "{$table}.description"], $term)
            ->orderByRaw(
                "ts_rank(to_tsvector('english', coalesce({$table}.name, '') || ' ' || coalesce({$table}.description, '')), plainto_tsquery('english', ?)) DESC",
                [$term],
            );
    }
}
