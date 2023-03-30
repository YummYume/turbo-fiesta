<?php

namespace App\Enum;

use App\Enum\Traits\UtilsTrait;

enum ContentVisibilityEnum: string
{
    use UtilsTrait;

    case Employee = 'ROLE_ADMIN';
    case Member = 'ROLE_MEMBER';
    case Public = 'ROLE_USER';
}
