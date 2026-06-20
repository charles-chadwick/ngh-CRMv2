<?php

namespace App\Enum;

enum LeadStatus: string
{
    case New = 'New';
    case Working = 'Working';
    case Qualified = 'Qualified';
    case Closed = 'Closed';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->name,
        ], self::cases());
    }
}
