<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Read/write access to the settings table.
 *
 * The entire table is small and read on nearly every request, so it is loaded
 * once and cached forever, then invalidated on write. Nothing here defers work
 * to a queue: the target deployment is shared hosting with no worker process,
 * so a save flushes and warms the cache inside the same request.
 */
class SettingsRepository
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $memoized = null;

    /**
     * Every setting, merged over the configured defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->memoized !== null) {
            return $this->memoized;
        }

        /** @var array<string, mixed> $stored */
        $stored = Cache::rememberForever(
            $this->cacheKey(),
            fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );

        return $this->memoized = array_merge($this->defaults(), $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @return array<int, string>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? array_values(array_map(strval(...), $value)) : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * Persist several settings and refresh the cache once.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    /**
     * Drop the cached table so the next read reloads it.
     */
    public function flush(): void
    {
        $this->memoized = null;

        Cache::forget($this->cacheKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('settings.defaults', []);

        return $defaults;
    }

    private function cacheKey(): string
    {
        return (string) config('settings.cache_key', 'panchforon.settings');
    }
}
