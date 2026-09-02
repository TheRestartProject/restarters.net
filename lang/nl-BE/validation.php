<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute moet goedgekeurd zijn.',
    'accepted_if' => ':attribute moet goedgekeurd zijn wanneer :other gelijk is aan :value.',
    'active_url' => ':attribute is geen geldige URL.',
    'after' => ':attribute moet een datum na :date zijn',
    'after_or_equal' => ':attribute moet een datum na of gelijk aan :date zijn.',
    'alpha' => ':attribute mag enkel letters bevatten.',
    'alpha_dash' => ':attribute mag enkel letters, getallen en streepjes bevatten.',
    'alpha_num' => ':attribute mag enkel letters en getallen bevatten.',
    'array' => ':attribute moet een reeks zijn.',
    'before' => ':attribute moet een datum voor :date zijn.',
    'before_or_equal' => ':attribute moet een datum voor of gelijk aan :date zijn.',
    'between' => [
        'array' => ':attribute moet tussen de :min en :max items bevatten.',
        'file' => ':attribute moet tussen de :min en :max kilobytes zijn.',
        'numeric' => ':attribute moet tussen :min en :max zijn.',
        'string' => ':attribute moet tussen de :min en :max tekens zijn.',
    ],
    'boolean' => 'Het veld :attribute moet waar of niet waar zijn.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'current_password' => 'Het paswoord is onjuist.',
    'date' => ':attribute is geen geldige datum.',
    'date_equals' => ':attribute moet een datum gelijk aan :date zijn.',
    'date_format' => ':attribute komt niet overeen met het formaat :format.',
    'declined' => ':attribute moet geweigerd zijn.',
    'declined_if' => ':attribute moet geweigerd zijn wanneer :other gelijk is aan :value.',
    'different' => ':attribute en :other moeten verschillend zijn.',
    'digits' => ':attribute moet uit :digits cijfers bestaan.',
    'digits_between' => ':attribute moet tussen de :min en :max cijfers bevatten.',
    'dimensions' => 'de afbeeldingsgrootte van :attribute is ongeldig.',
    'distinct' => 'Het veld :attribute heeft een dubbele waarde.',
    'doesnt_end_with' => ':attribute mag niet eindigen met een van de volgende: :values.',
    'doesnt_start_with' => ':attribute mag niet beginnen met een van de volgende: :values.',
    'email' => ':attribute moet een geldig e-mailadres zijn.',
    'ends_with' => ':attribute moet eindigen met een van de volgende: :values.',
    'enum' => 'Het geselecteerde :attribute is ongeldig.',
    'exists' => 'Het geselecteerde :attribute is ongeldig.',
    'file' => ':attribute moet een bestand zijn.',
    'filled' => 'Het veld :attribute moet een waarde hebben.',
    'gt' => [
        'array' => ':attribute moet meer dan :value items bevatten.',
        'file' => ':attribute moet groter zijn dan :value kilobytes.',
        'numeric' => ':attribute moet groter zijn dan :value.',
        'string' => ':attribute moet meer dan :value tekens tellen.',
    ],
    'gte' => [
        'array' => ':attribute moet :value of meer items zijn.',
        'file' => ':attribute moet groter of gelijk aan :value kilobytes zijn.',
        'numeric' => ':attribute moet groter of gelijk aan :value zijn.',
        'string' => 'Het aantal tekens van :attribute moet groter of gelijk aan :value zijn.',
    ],
    'image' => ':attribute moet een afbeelding zijn.',
    'in' => 'Het geselecteerde :attribute is ongeldig.',
    'in_array' => 'Het veld :attribute bestaat niet in :other.',
    'integer' => ':attribute moet een geheel getal zijn.',
    'ip' => ':attribute moet een geldig IP adres zijn.',
    'ipv4' => ':attribute moet een geldig IPv4 adres zijn.',
    'ipv6' => ':attribute moet een geldig IPv6 adres zijn.',
    'json' => ':attribute moet een geldige JSON string zijn.',
    'lt' => [
        'array' => ':attribute moet minder dan :value items bevatten.',
        'file' => ':attribute moet kleiner dan :value kilobytes zijn.',
        'numeric' => ':attribute moet minder zijn dan :value.',
        'string' => ':attribute moet minder dan :value tekens tellen.',
    ],
    'lte' => [
        'array' => ':attribute mag niet meer dan :value items bevatten.',
        'file' => ':attribute moet kleiner of gelijk zijn aan :value kilobytes.',
        'numeric' => ':attribute moet kleiner dan of gelijk zijn aan :value.',
        'string' => ':attribute moet minder of evenveel tekens tellen als :value.',
    ],
    'mac_address' => ':attribute moet een geldig MAC-adres zijn.',
    'max' => [
        'array' => ':attribute mag niet meer dan :max items bevatten.',
        'file' => ':attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => ':attribute mag niet groter zijn dan :max.',
        'string' => ':attribute mag niet meer dan :max tekens tellen.',
    ],
    'max_digits' => ':attribute mag niet meer dan :max cijfers hebben.',
    'mimes' => ':attribute moet één van volgende documentstypes zijn: :values.',
    'mimetypes' => ':attribute moet één van volgende documentstypes zijn: :values.',
    'min' => [
        'array' => ':attribute moet minstens :min items bevatten.',
        'file' => ':attribute moet minstens :min kilobytes zijn.',
        'numeric' => ':attribute moet minstens :min zijn.',
        'string' => ':attribute moet minstens :min tekens tellen.',
    ],
    'min_digits' => ':attribute moet minstens :min cijfers hebben.',
    'multiple_of' => ':attribute moet een veelvoud zijn van :value.',
    'not_in' => 'Het geselecteerde :attribute is ongeldig.',
    'not_regex' => 'Het format van :attribute is ongeldig.',
    'numeric' => ':attribute moet een getal zijn.',
    'password' => [
        'letters' => ':attribute moet minstens één letter bevatten.',
        'mixed' => ':attribute moet minstens één hoofdletter en één kleine letter bevatten.',
        'numbers' => ':attribute moet minstens één cijfer bevatten.',
        'symbols' => ':attribute moet minstens één symbool bevatten.',
        'uncompromised' => 'Het opgegeven :attribute is verschenen in een datalek. Kies een ander :attribute.',
    ],
    'present' => 'Het veld :attribute moet aanwezig zijn.',
    'prohibited' => 'Het veld :attribute is niet toegestaan.',
    'prohibited_if' => 'Het veld :attribute is niet toegestaan wanneer :other gelijk is aan :value.',
    'prohibited_unless' => 'Het veld :attribute is niet toegestaan tenzij :other in :values staat.',
    'prohibits' => 'Het veld :attribute verhindert dat :other aanwezig is.',
    'regex' => 'Het format van :attribute is ongeldig.',
    'required' => 'Het veld :attribute is verplicht.',
    'required_array_keys' => 'Het veld :attribute moet vermeldingen bevatten voor: :values.',
    'required_if' => 'Het veld :attribute is verplicht als :other gelijk is aan :value.',
    'required_if_accepted' => 'Het veld :attribute is verplicht wanneer :other is aanvaard.',
    'required_unless' => 'Het veld :attribute is verplicht tenzij :other in :values staat.',
    'required_with' => 'Het veld :attribute is verplicht als :values aanwezig zijn.',
    'required_with_all' => 'Het veld :attribute is verplicht als :values aanwezig zijn.',
    'required_without' => 'Het veld :attribute is verplicht als :values niet  aanwezig zijn.',
    'required_without_all' => 'Het veld :attribute is verplicht als geen van de :values aanwezig zijn.',
    'same' => ':attribute en :other moeten overeenkomen.',
    'size' => [
        'array' => ':attribute moet :size items bevatten.',
        'file' => ':attribute moet :size kilobytes zijn.',
        'numeric' => ':attribute moet :size zijn.',
        'string' => ':attribute moet :size tekens tellen.',
    ],
    'starts_with' => ':attribute moet beginnen met een van de volgende: :values.',
    'string' => ':attribute moet een string zijn.',
    'timezone' => ':attribute moet een geldige zone zijn.',
    'unique' => ':attribute is al in gebruik.',
    'uploaded' => 'Upload van :attribute is mislukt.',
    'url' => 'Het format van :attribute is ongeldig.',
    'uuid' => ':attribute moet een geldige UUID zijn.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'Aangepaste boodschap',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
