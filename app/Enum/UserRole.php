<?php
namespace App\Enum;

enum UserRole: string {
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case Staff = 'Staff';
    case Customer = 'Customer';
}