<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityRelationshipType: string
{
    case BelongsTo = 'belongs_to';
    case HasMany = 'has_many';
    case HasOne = 'has_one';
    case ManyToMany = 'many_to_many';
    case References = 'references';
    case Polymorphic = 'polymorphic';
}
