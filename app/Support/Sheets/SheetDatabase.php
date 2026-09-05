<?php

namespace App\Support\Sheets;

/**
 * Application-level facade over whichever sheet store is configured.
 */
class SheetDatabase
{
    private ?SheetStore $store = null;

    public function store(): SheetStore
    {
        $this->store ??= match (config('gsheet.driver')) {
            'google' => new GoogleSheetStore(),
            default => new SqliteSheetStore(),
        };

        return $this->store;
    }

    public function driver(): string
    {
        return $this->store()->driver();
    }

    public function googleConfigured(): bool
    {
        return $this->store() instanceof GoogleSheetStore;
    }

    public function all(string $table): array
    {
        return $this->store()->all($table);
    }

    public function find(string $table, string|int $id): ?array
    {
        return $this->store()->find($table, $id);
    }

    public function insert(string $table, array $row): array
    {
        return $this->store()->insert($table, $row);
    }

    public function update(string $table, string|int $id, array $row): array
    {
        return $this->store()->update($table, $id, $row);
    }

    public function delete(string $table, string|int $id): bool
    {
        return $this->store()->delete($table, $id);
    }

    public function count(string $table): int
    {
        return $this->store()->count($table);
    }

    public function nextId(string $table): int
    {
        return $this->store()->nextId($table);
    }

    public function provisionSchema(array $tables): void
    {
        $this->store()->provisionSchema($tables);
    }

    public function syncRows(string $table, array $rows): void
    {
        $this->store()->syncRows($table, $rows);
    }
}