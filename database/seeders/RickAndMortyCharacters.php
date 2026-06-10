<?php

namespace Database\Seeders;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class RickAndMortyCharacters
{
    private static Collection $pool;

    private static int $cursor = 0;

    public static function next(): array|Closure
    {
        if (! isset(self::$pool)) {
            self::load();
        }

        return self::$pool->get(self::$cursor++ % self::$pool->count());
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
