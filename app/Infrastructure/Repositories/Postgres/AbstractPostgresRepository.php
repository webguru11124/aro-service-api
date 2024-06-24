<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class AbstractPostgresRepository
{
    protected function getQueryBuilder(): Builder
    {
        return DB::table($this->getTableName() . ' as t');
    }

    /**
     * @param string $table
     * @param array<array{column?: string, operator?: string, value?: mixed}> $conditions
     *
     * @return void
     */
    protected function markAsDeleted(string $table, array $conditions): void
    {
        $query = DB::table($table);

        foreach ($conditions as $condition) {
            $column = $condition['column'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if ($column && $value) {
                $query->where($column, $operator, $value);
            }
        }

        $query->update(['deleted_at' => Carbon::now()]);
    }

    abstract protected function getTableName(): string;
}
