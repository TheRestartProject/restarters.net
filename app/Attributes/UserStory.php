<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class UserStory
{
    public function __construct(
        public string $story,
        public string $persona,
        public string $feature = '',
        public string $theme = '',
    ) {}
}
