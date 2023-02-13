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
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return in_array($value, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('partials.validate_timezone');
    }
}
