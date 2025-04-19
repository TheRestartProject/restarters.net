<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Timezone implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  mixed  $value
     */
    public function passes(string $attribute, $value): bool
    {
        return in_array($value, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC));
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('partials.validate_timezone');
    }
}
