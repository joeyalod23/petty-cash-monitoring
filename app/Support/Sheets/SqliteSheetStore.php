<?php

namespace App\Support\Sheets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Development/test fallback driver that mirrors the Google Sheets interface
 * on top of the local SQLite tables so the application can run while Google
 * credentials are not configured.
 */
class SqliteSheetStore implements SheetStore
{
    public function all(string $table): array
    {
        $columns = $this->columns($table);

        return DB::table($table)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => array_intersect_key((array) $row, array_flip($columns)))
            ->all();
    }

    public function find(string $table, string|int $id): ?array
    {
        $row = DB::table($table)->where('id', $id)->first();

        if (!$row) {
            return null;
        }

        return array_intersect_key((array) $row, array_flip($this->columns($table)));
    }

    public function insert(string $table, array $row): array
    {
        $id = $row[$this->key()] ?? $this->nextId($table);
        $row[$this->key()] = $id;

        $insert = $this->filterColumns($table, $row);
        $insert[$this->key()] = $id;
        $timestamps = $this->hasColumn($table, 'created_at');

        if ($timestamps) {
            $insert['created_at'] = $row['created_at'] ?? now()->toDateTimeString();
            $insert['updated_at'] = $row['updated_at'] ?? now()->toDateTimeString();
        }

        DB::table($table)->insert($insert);

        return $this->find($table, $id) ?? $insert;
    }

    public function update(string $table, string|int $id, array $row): array
    {
        $update = $this->filterColumns($table, $row);
        $update = array_merge($update, [$this->key() => $id]);

        if ($this->hasColumn($table, 'updated_at') && !isset($update['updated_at'])) {
            $update['updated_at'] = now()->toDateTimeString();
        }

        DB::table($table)->where($this->key(), $id)->update($update);

        return $this->find($table, $id) ?? array_merge($update, [$this->key() => $id]);
    }

    public function delete(string $table, string|int $id): bool
    {
        return (bool) DB::table($table)->where($this->key(), $id)->delete();
    }

    public function count(string $table): int
    {
        return DB::table($table)->count();
    }

    public function nextId(string $table): int
    {
        return (int) DB::table($table)->max('id') + 1;
    }

    public function provisionSchema(array $tables): void
    {
        // Tables already exist from the Laravel migrations.
    }

    public function syncRows(string $table, array $rows): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
        }

        foreach ($rows as $row) {
            $this->insert($table, $row);
        }
    }

    public function driver(): string
    {
        return 'local';
    }

    private function key(): string
    {
        return config('gsheet.primary_key', 'id');
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    private function filterColumns(string $table, array $row): array
    {
        return array_intersect_key($row, array_flip($this->columns($table)));
    }
}