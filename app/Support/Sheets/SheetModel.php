<?php

namespace App\Support\Sheets;

use ArrayAccess;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class SheetModel implements ArrayAccess, \JsonSerializable, UrlRoutable
{
    protected array $attributes = [];

    protected array $fillable = [];

    protected array $casts = [];

    protected array $relations = [];

    protected bool $usesTimestamps = true;

    public bool $exists = false;

    public static function table(): string
    {
        return (new static)->getTable();
    }

    public function getTable(): string
    {
        return Str::snake(Str::pluralStudly(class_basename(static::class)));
    }

    public static function keyName(): string
    {
        return 'id';
    }

    public function getKeyName(): string
    {
        return static::keyName();
    }

    public function getKey(): mixed
    {
        return $this->attributes[static::keyName()] ?? null;
    }

    public function getRouteKey(): mixed
    {
        return $this->getKey();
    }

    public function getRouteKeyName(): string
    {
        return static::keyName();
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        return static::where($field ?? static::keyName(), $value)->first();
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?static
    {
        return null;
    }

    public static function fromRow(array $attributes): static
    {
        $model = new static;
        $model->attributes = $attributes;
        $model->exists = true;

        return $model;
    }

    public static function query(): SheetQuery
    {
        return new SheetQuery(static::class);
    }

    public static function all(): Collection
    {
        return static::query()->get();
    }

    public static function find(mixed $id): ?static
    {
        return static::query()->where(static::keyName(), $id)->first();
    }

    public static function first(): ?static
    {
        return static::query()->first();
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    public static function create(array $attributes): static
    {
        $model = new static;
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $existing = static::query();

        foreach ($attributes as $key => $value) {
            $existing->where($key, $value);
        }

        $model = $existing->first();

        if ($model) {
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): SheetQuery
    {
        return static::query()->where($column, $operatorOrValue, $value);
    }

    public static function whereIn(string $column, array $values): SheetQuery
    {
        return static::query()->whereIn($column, $values);
    }

    public static function latest(string $column = 'created_at'): SheetQuery
    {
        return static::query()->latest($column);
    }

    public static function orderBy(string $column, string $direction = 'asc'): SheetQuery
    {
        return static::query()->orderBy($column, $direction);
    }

    public static function with(string|array $relations): SheetQuery
    {
        return static::query()->with($relations);
    }

    public static function withCount(string|array $relations): SheetQuery
    {
        return static::query()->withCount($relations);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable, true) || $key === static::keyName()) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function save(): bool
    {
        $db = resolve(SheetDatabase::class);
        $table = static::table();
        $now = now()->toDateTimeString();

        if ($this->usesTimestamps) {
            if ($this->exists) {
                $this->attributes['updated_at'] = $now;
            } else {
                $this->attributes['created_at'] ??= $now;
                $this->attributes['updated_at'] = $now;
            }
        }

        $raw = $this->attributesToStore();

        if ($this->exists) {
            $db->update($table, $this->getKey(), $raw);
        } else {
            $stored = $db->insert($table, $raw);
            $this->attributes[static::keyName()] = (string) $stored[static::keyName()];
            $this->exists = true;
        }

        return true;
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);

        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        resolve(SheetDatabase::class)->delete(static::table(), $this->getKey());
        $this->exists = false;

        return true;
    }

    public function load(string|array $relations): static
    {
        foreach ((array) $relations as $relation) {
            $this->getAttribute($relation);
        }

        return $this;
    }

    public function getAttribute(string $name): mixed
    {
        $accessor = 'get' . Str::studly($name) . 'Attribute';

        if (method_exists($this, $accessor)) {
            return $this->{$accessor}();
        }

        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        if (array_key_exists($name, $this->attributes)) {
            return $this->castAttribute($name, $this->attributes[$name]);
        }

        if (method_exists($this, $name)) {
            $result = $this->{$name}();
            $this->relations[$name] = $result instanceof SheetQuery
                ? $result->get()
                : $result;

            return $this->relations[$name];
        }

        return null;
    }

    public function setAttribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function setRawAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function relations(): array
    {
        return $this->relations;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            $result[$key] = $this->castAttribute($key, $value);
        }

        return $result;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        if (array_key_exists($name, $this->attributes)) {
            return true;
        }

        if (array_key_exists($name, $this->relations)) {
            return true;
        }

        return method_exists($this, $name)
            || method_exists($this, 'get' . Str::studly($name) . 'Attribute');
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        $cast = $this->casts[$key] ?? null;

        if ($cast === null && in_array($key, ['created_at', 'updated_at'], true)) {
            $cast = 'datetime';
        }

        if ($value === null || $value === '') {
            return $value;
        }

        return match (true) {
            $cast === 'date' => Carbon::parse($value)->startOfDay(),
            $cast === 'datetime' => Carbon::parse($value),
            is_string($cast) && str_starts_with($cast, 'decimal') => (float) round((float) $value, 2),
            $cast === 'integer' => (int) $value,
            $cast === 'boolean' => (bool) $value,
            default => $value,
        };
    }

    protected function attributesToStore(): array
    {
        $stored = [];

        foreach ($this->attributes as $key => $value) {
            $cast = $this->casts[$key] ?? null;

            if (($cast === 'date' || $cast === 'datetime') && $value instanceof Carbon) {
                $stored[$key] = $value->format($cast === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s');
            } else {
                $stored[$key] = $value;
            }
        }

        return $stored;
    }
}