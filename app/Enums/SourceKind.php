<?php

namespace App\Enums;

enum SourceKind: string
{
    use HasTranslatedLabel;

    case Article = 'article';
    case Video = 'video';
    case Audio = 'audio';
    case Programme = 'programme';
    case SocialMedia = 'social_media';
    case OfficialJournal = 'official_journal';
    case PublicData = 'public_data';
}
