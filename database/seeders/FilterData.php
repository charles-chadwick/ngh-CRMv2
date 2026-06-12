<?php

/** @noinspection PhpUnused */

namespace Database\Seeders;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FilterData
{
    public const BAD_WORDS = [
        'titty',
        'boob',
        'fuck',
        'shit',
        'vagina',
        'dick',
        'cock',
        'damn',
        'dick',
        'bitch',
        'balls',
        'nip',
        'testicle',
        'poopy',
        'elon',
        'fascist',
        'hepatitis',
        'slut',
        'jacker',
        'abradolf',
        'hitler',
        'butt',
        'cunt',
        'retard',
        'tortured',
        'jew',
        'douche',
        'damn',
        'crap',
        'damn',
        'bitch',
        'butt',
        'genital',
        'sperm',
        'taint',
        'erotic',
        'shmlangela',
    ];

    private Collection $data;

    public function __construct()
    {
        $this->data = collect(file(database_path('src/rickandmorty-scripts.csv')))->map(fn ($line) => trim($line));
    }

    public static function hasBadWords(string $string): bool
    {
        foreach (self::BAD_WORDS as $bad_word) {
            if (stripos($string, $bad_word) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function censor(string $string): string
    {
        foreach (self::BAD_WORDS as $bad_word) {
            $string = str_replace($bad_word, str_repeat('*', strlen($bad_word)), $string);
        }

        return $string;
    }

    public function randomData(int $count, $title = true, $limit = 25): string
    {
        $text = Str::of($this->data->random($count)
            ->map(function ($line) {
                return trim(self::censor($line));
            })
            ->implode("\n"))
            ->limit($limit, '', true)
            ->replace('"', '')
            ->trim();

        if ($title) {
            $text->title();
        }

        return $text->toString();
    }
}
