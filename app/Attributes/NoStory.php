<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class NoStory
{
    public function __construct(
        public string $reason = '',
    ) {}
}
