<?php

namespace App\Enums;

enum Role: string
{
    use HasTranslatedLabel;

    case Member = 'member';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
