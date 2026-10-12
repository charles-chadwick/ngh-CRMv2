<?php

namespace App\Enum;

enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case Staff = 'Staff';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return self::toOptions(self::cases());
    }

    /**
     * Select options for the roles the given role may assign.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function optionsAssignableBy(self $role): array
    {
        return self::toOptions(self::assignableBy($role));
    }

    /**
     * Roles at or below the given role.
     *
     * @return list<self>
     */
    public static function assignableBy(self $role): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $candidate): bool => $role->isAtLeast($candidate),
        ));
    }

    /**
     * Seniority of the role; a higher number outranks a lower one.
     */
    public function rank(): int
    {
        return match ($this) {
            self::SuperAdmin => 3,
            self::Admin => 2,
            self::Staff => 1,
        };
    }

    public function isAtLeast(self $role): bool
    {
        return $this->rank() >= $role->rank();
    }

    /**
     * @param  list<self>  $roles
     * @return list<array{value: string, label: string}>
     */
    private static function toOptions(array $roles): array
    {
        return array_map(fn (self $role): array => [
            'value' => $role->value,
            'label' => $role->name,
        ], $roles);
    }
}
