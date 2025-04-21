<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class Timezone implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $_attribute, mixed $value, \Closure $fail): void
    {
        if (!in_array($value, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC))) {
            $fail(__('partials.validate_timezone'));
        }
    }
}
