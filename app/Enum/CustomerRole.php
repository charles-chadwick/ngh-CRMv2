<?php

namespace App\Enum;

enum CustomerRole: string
{
    case Owner = 'Owner';
    case CEO = 'CEO';
    case Sales = 'Sales';
}
