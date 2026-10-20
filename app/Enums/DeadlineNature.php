<?php

namespace App\Enums;

enum DeadlineNature: string
{
    use HasTranslatedLabel;

    case Explicit = 'explicit';
    case Inferred = 'inferred';
}
