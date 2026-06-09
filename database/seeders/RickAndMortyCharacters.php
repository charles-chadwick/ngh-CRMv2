<?php

namespace Database\Seeders;

class RickAndMortyCharacters
{
    private static array $pool = [];

    private static int $cursor = 0;

    public static function next(): array
    {
        if (empty(self::$pool)) {
            self::load();
        }

        $character = self::$pool[self::$cursor % count(self::$pool)];
        self::$cursor++;

        return $character;
    }

    public static function avatarPath(int $id): string
    {
        return database_path("rickandmorty/avatars/{$id}.jpeg");
    }

    private static function load(): void
    {
        $json = file_get_contents(database_path('rickandmorty/characters.json'));
        $characters = json_decode($json, true);

        mt_srand(42);
        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        self::$pool = $characters;
    }
}
