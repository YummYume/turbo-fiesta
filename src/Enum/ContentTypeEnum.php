<?php

namespace App\Enum;

use App\Enum\Traits\UtilsTrait;

enum ContentTypeEnum: string
{
    use UtilsTrait;

    case Article = 'article';
    case Formation = 'formation';
    case Other = 'other';
}
