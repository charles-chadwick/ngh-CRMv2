<?php

namespace App\Enum;

enum TaskType: string
{
    case Call = 'Call';
    case Meeting = 'Meeting';
    case Email = 'Email';
    case FollowUp = 'Follow Up';
    case ToDo = 'To-Do';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->value,
        ], self::cases());
    }
}
