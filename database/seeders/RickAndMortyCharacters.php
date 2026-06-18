<?php

namespace Database\Seeders;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class RickAndMortyCharacters
{
    private static Collection $pool;

    private static int $cursor = 0;

    /**
     * IDs of characters that have already been assigned to a record.
     *
     * @var array<int, true>
     */
    private static array $used = [];

    /**
     * @return array{id: int, first_name: string, last_name: string}
     */
    public static function next(): array|Closure
    {
        if (! isset(self::$pool)) {
            self::load();
        }

        $character = self::$pool->get(self::$cursor++ % self::$pool->count());

        self::markUsed($character['id']);

        return $character;
    }

    public static function markUsed(int $id): void
    {
        self::$used[$id] = true;
    }

    /**
     * Characters that haven't been assigned to a record yet.
     *
     * @return Collection<int, array{id: int, first_name: string, last_name: string}>
     */
    public static function remaining(): Collection
    {
        if (! isset(self::$pool)) {
            self::load();
        }

        return self::$pool
            ->reject(fn (array $character): bool => isset(self::$used[$character['id']]))
            ->values();
    }

    public static function avatarPath(int $id): string
    {
        return database_path("rickandmorty/avatars/{$id}.jpeg");
    }

    private static function load(): void
    {
        self::$pool = collect(File::json(database_path('rickandmorty/characters.json')))->shuffle();
    }
}
