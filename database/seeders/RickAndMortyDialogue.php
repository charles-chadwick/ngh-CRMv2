<?php

namespace Database\Seeders;

use Illuminate\Support\Collection;

class RickAndMortyDialogue
{
    /**
     * Clean, title-length dialogue lines drawn from the show's scripts.
     *
     * @var Collection<int, string>
     */
    private static Collection $pool;

    /**
     * A random line of dialogue, suitable for use as a record title.
     */
    public static function next(): string
    {
        if (! isset(self::$pool)) {
            self::load();
        }

        return self::$pool->random();
    }

    /**
     * Load the `line` column from the script CSV, keeping only title-length
     * lines (10–120 characters) that contain no bad words.
     */
    private static function load(): void
    {
        $handle = fopen(database_path('src/rickandmorty-scripts.csv'), 'r');

        $header = fgetcsv($handle, 0, ',', '"', '');
        $line_index = array_search('line', $header);

        $lines = collect();

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line = trim($row[$line_index] ?? '');
            $length = mb_strlen($line);

            if ($length < 10 || $length > 120) {
                continue;
            }

            if (FilterData::hasBadWords($line)) {
                continue;
            }

            $lines->push($line);
        }

        fclose($handle);

        self::$pool = $lines->values();
    }
}
