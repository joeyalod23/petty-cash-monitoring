<?php

namespace App\Support\Sheets;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SheetQuery
{
    private array $wheres = [];

    private array $whereIns = [];

    private array $orders = [];

    private ?int $limit = null;

    private array $countRelations = [];

    private string $class;

    public function __construct(private string $modelClass)
    {
        $this->class = $modelClass;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operatorOrValue;
            $operatorOrValue = '=';
        }

        $this->wheres[] = [$column, $operatorOrValue, $value];

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->whereIns[] = [$column, $values];

        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = [$column, '=', ''];

        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = [$column, '!=', ''];

        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        array_unshift($this->orders, [$column, 'desc']);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = [$column, $direction];

        return $this;
    }

    public function take(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function with(string|array $relations): static
    {
        return $this;
    }

    public function withCount(string|array $relations): static
    {
        $relations = (array) $relations;

        $this->countRelations = array_merge($this->countRelations, $relations);

        return $this;
    }

    public function get(): Collection
    {
        $rows = $this->rows();
        $models = $this->hydrateModels($rows);

        $this->attachCountRelations(new Collection($models));

        return new Collection($models);
    }

    public function first(): ?SheetModel
    {
        $rows = $this->rows();
        $row = $rows[0] ?? null;

        if ($row === null) {
            return null;
        }

        return $this->hydrateOne($row);
    }

    public function count(): int
    {
        return count($this->rows());
    }

    public function sum(string $column): float
    {
        $total = 0.0;

        foreach ($this->rows() as $row) {
            if (isset($row[$column]) && $row[$column] !== '') {
                $total += (float) $row[$column];
            }
        }

        return $total;
    }

    public function value(string $column): mixed
    {
        $row = $this->first();

        return $row ? $row->getAttribute($column) : null;
    }

    public function find(mixed $id): ?SheetModel
    {
        $this->where($this->getKeyColumn(), '=', $id);

        return $this->first();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $rows = $this->rows();
        $total = count($rows);

        $page = (int) request()->input('page', 1);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sliced = array_slice($rows, $offset, $perPage);
        $models = $this->hydrateModels($sliced);

        $this->attachCountRelations(new Collection($models));

        return new LengthAwarePaginator(
            $models,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    public function pluck(string $column): Collection
    {
        return $this->get()->pluck($column);
    }

    public function exists(): bool
    {
        return $this->first() !== null;
    }

    public function update(array $attributes): bool
    {
        $updated = 0;

        foreach ($this->rows() as $row) {
            $id = $row['id'] ?? null;
            if ($id === null) continue;

            resolve(SheetDatabase::class)->update(
                $this->table(),
                $id,
                array_merge($row, $attributes)
            );

            $updated++;
        }

        return $updated > 0;
    }

    public function delete(): int
    {
        $deleted = 0;

        foreach ($this->rows() as $row) {
            $id = $row['id'] ?? null;
            if ($id === null) continue;

            resolve(SheetDatabase::class)->delete($this->table(), $id);
            $deleted++;
        }

        return $deleted;
    }

    private function rows(): array
    {
        $rows = resolve(SheetDatabase::class)->all($this->table());

        foreach ($this->wheres as [$column, $operator, $value]) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => $this->matchWhere($row[$column] ?? null, $operator, $value)
            ));
        }

        foreach ($this->whereIns as [$column, $values]) {
            $values = array_map(fn ($v) => (string) $v, $values);
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => in_array((string) ($row[$column] ?? ''), $values, true)
            ));
        }

        foreach (array_reverse($this->orders) as [$column, $direction]) {
            usort($rows, function (array $a, array $b) use ($column, $direction) {
                $aVal = $a[$column] ?? null;
                $bVal = $b[$column] ?? null;
                $cmp = $this->compare($aVal, $bVal);

                return $direction === 'desc' ? -$cmp : $cmp;
            });
        }

        if ($this->limit !== null) {
            $rows = array_slice($rows, 0, $this->limit);
        }

        return $rows;
    }

    private function matchWhere(mixed $cell, string $operator, mixed $value): bool
    {
        $cell = (string) ($cell ?? '');
        $value = (string) $value;

        return match ($operator) {
            '=' => $cell === $value,
            '!=' => $cell !== $value,
            '<>' => $cell !== $value,
            '<' => (float) $cell < (float) $value,
            '<=' => (float) $cell <= (float) $value,
            '>' => (float) $cell > (float) $value,
            '>=' => (float) $cell >= (float) $value,
            'like' => str_contains(mb_strtolower($cell), mb_strtolower($value)),
            default => $cell === $value,
        };
    }

    private function compare(mixed $a, mixed $b): int
    {
        if ($a === $b) return 0;

        if (is_numeric($a) && is_numeric($b)) {
            return $a <=> $b;
        }

        return strcmp((string) $a, (string) $b);
    }

    private function hydrateModels(array $rows): array
    {
        $class = $this->class;

        return array_map(
            fn (array $row) => $class::fromRow($row),
            $rows
        );
    }

    private function hydrateOne(array $row): SheetModel
    {
        return $this->class::fromRow($row);
    }

    private function attachCountRelations(Collection $models): void
    {
        foreach ($this->countRelations as $relation) {
            foreach ($models as $model) {
                $model->setAttribute($relation . '_count', $model->$relation()->count());
            }
        }
    }

    private function table(): string
    {
        return $this->class::table();
    }

    private function getKeyColumn(): string
    {
        return $this->class::keyName();
    }
}