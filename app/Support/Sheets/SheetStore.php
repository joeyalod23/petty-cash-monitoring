<?php

namespace App\Support\Sheets;

interface SheetStore
{
    /**
     * @return array<int, array<string, mixed>> rows keyed by 'id'
     */
    public function all(string $table): array;

    public function find(string $table, string|int $id): ?array;

    /**
     * @return array<string, mixed> the persisted row including its id
     */
    public function insert(string $table, array $row): array;

    /**
     * @return array<string, mixed> the persisted row including its id
     */
    public function update(string $table, string|int $id, array $row): array;

    public function delete(string $table, string|int $id): bool;

    public function count(string $table): int;

    public function nextId(string $table): int;

    /**
     * Create/ensure tables (worksheets) and header rows exist.
     */
    public function provisionSchema(array $tables): void;

    /**
     * Replace every row of a table (used when seeding).
     */
    public function syncRows(string $table, array $rows): void;

    public function driver(): string;
}