<?php

namespace App\Enums;

enum FileCategory: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
    case Archive = 'archive';
    case Data = 'data';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $category) => $category->value,
            self::cases(),
        );
    }
}
