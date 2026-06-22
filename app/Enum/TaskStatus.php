<?php

namespace App\Enum;

enum TaskStatus: string
{
    case Open = 'Open';
    case Completed = 'Completed';
    case Canceled = 'Canceled';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->value,
        ], self::cases());
    }
}
