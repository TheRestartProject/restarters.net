<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Feature
{
    public function __construct(
        public string $name,
        public string $description = '',
    ) {}
}
