<?php

namespace App\Enum;

enum LeadType: string
{
    case Web = 'Web';
    case Phone = 'Phone';
    case Email = 'Email';
    case Event = 'Event';
    case Other = 'Other';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->name,
        ], self::cases());
    }
}
