<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validatie taalregels
    |--------------------------------------------------------------------------
    |
    | De volgende taalregels bevatten de standaard foutmeldingen die worden
    | gebruikt door de validatieklasse. Sommige van deze regels hebben meerdere
    | versies, zoals de grootteregels. Voel je vrij om elk van deze berichten
    | hier aan te passen.
    |
    */

    'accepted' => 'Het veld :attribute moet worden geaccepteerd.',
    'accepted_if' => 'Het veld :attribute moet worden geaccepteerd wanneer :other gelijk is aan :value.',
    'active_url' => 'Het veld :attribute is geen geldige URL.',
    'after' => 'Het veld :attribute moet een datum zijn na :date.',
    'after_or_equal' => 'Het veld :attribute moet een datum zijn na of gelijk aan :date.',
    'alpha' => 'Het veld :attribute mag alleen letters bevatten.',
    'alpha_dash' => 'Het veld :attribute mag alleen letters, cijfers, koppeltekens en onderstrepingstekens bevatten.',
    'alpha_num' => 'Het veld :attribute mag alleen letters en cijfers bevatten.',
    'array' => 'Het veld :attribute moet een array zijn.',
    'before' => 'Het veld :attribute moet een datum zijn voor :date.',
    'before_or_equal' => 'Het veld :attribute moet een datum zijn voor of gelijk aan :date.',
    'between' => [
        'array' => 'Het veld :attribute moet tussen :min en :max items bevatten.',
        'file' => 'Het veld :attribute moet tussen :min en :max kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet tussen :min en :max zijn.',
        'string' => 'Het veld :attribute moet tussen :min en :max tekens bevatten.',
    ],
    'boolean' => 'Het veld :attribute moet waar of onwaar zijn.',
    'confirmed' => 'De bevestiging van het veld :attribute komt niet overeen.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => 'Het veld :attribute is geen geldige datum.',
    'date_equals' => 'Het veld :attribute moet een datum zijn gelijk aan :date.',
    'date_format' => 'Het veld :attribute komt niet overeen met het formaat :format.',
    'declined' => 'Het veld :attribute moet worden geweigerd.',
    'declined_if' => 'Het veld :attribute moet worden geweigerd wanneer :other gelijk is aan :value.',
    'different' => 'Het veld :attribute en :other moeten verschillend zijn.',
    'digits' => 'Het veld :attribute moet :digits cijfers bevatten.',
    'digits_between' => 'Het veld :attribute moet tussen :min en :max cijfers bevatten.',
    'dimensions' => 'Het veld :attribute heeft ongeldige afbeeldingsafmetingen.',
    'distinct' => 'Het veld :attribute heeft een dubbele waarde.',
    'doesnt_end_with' => 'Het veld :attribute mag niet eindigen op een van de volgende waarden: :values.',
    'doesnt_start_with' => 'Het veld :attribute mag niet beginnen met een van de volgende waarden: :values.',
    'email' => 'Het veld :attribute moet een geldig e-mailadres zijn.',
    'ends_with' => 'Het veld :attribute moet eindigen op een van de volgende waarden: :values.',
    'enum' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'exists' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'file' => 'Het veld :attribute moet een bestand zijn.',
    'filled' => 'Het veld :attribute moet een waarde bevatten.',
    'gt' => [
        'array' => 'Het veld :attribute moet meer dan :value items bevatten.',
        'file' => 'Het veld :attribute moet groter zijn dan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet groter zijn dan :value.',
        'string' => 'Het veld :attribute moet meer dan :value tekens bevatten.',
    ],
    'gte' => [
        'array' => 'Het veld :attribute moet :value of meer items bevatten.',
        'file' => 'Het veld :attribute moet groter zijn dan of gelijk aan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet groter zijn dan of gelijk aan :value.',
        'string' => 'Het veld :attribute moet :value of meer tekens bevatten.',
    ],
    'image' => 'Het veld :attribute moet een afbeelding zijn.',
    'in' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'in_array' => 'Het veld :attribute bestaat niet in :other.',
    'integer' => 'Het veld :attribute moet een geheel getal zijn.',
    'ip' => 'Het veld :attribute moet een geldig IP-adres zijn.',
    'ipv4' => 'Het veld :attribute moet een geldig IPv4-adres zijn.',
    'ipv6' => 'Het veld :attribute moet een geldig IPv6-adres zijn.',
    'json' => 'Het veld :attribute moet een geldige JSON-tekenreeks zijn.',
    'lt' => [
        'array' => 'Het veld :attribute moet minder dan :value items bevatten.',
        'file' => 'Het veld :attribute moet kleiner zijn dan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet kleiner zijn dan :value.',
        'string' => 'Het veld :attribute moet minder dan :value tekens bevatten.',
    ],
    'lte' => [
        'array' => 'Het veld :attribute mag niet meer dan :value items bevatten.',
        'file' => 'Het veld :attribute moet kleiner zijn dan of gelijk aan :value kilobytes.',
        'numeric' => 'Het veld :attribute moet kleiner zijn dan of gelijk aan :value.',
        'string' => 'Het veld :attribute moet :value of minder tekens bevatten.',
    ],
    'mac_address' => 'Het veld :attribute moet een geldig MAC-adres zijn.',
    'max' => [
        'array' => 'Het veld :attribute mag niet meer dan :max items bevatten.',
        'file' => 'Het veld :attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => 'Het veld :attribute mag niet groter zijn dan :max.',
        'string' => 'Het veld :attribute mag niet meer dan :max tekens bevatten.',
    ],
    'max_digits' => 'Het veld :attribute mag niet meer dan :max cijfers bevatten.',
    'mimes' => 'Het veld :attribute moet een bestand zijn van het type: :values.',
    'mimetypes' => 'Het veld :attribute moet een bestand zijn van het type: :values.',
    'min' => [
        'array' => 'Het veld :attribute moet minimaal :min items bevatten.',
        'file' => 'Het veld :attribute moet minimaal :min kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet minimaal :min zijn.',
        'string' => 'Het veld :attribute moet minimaal :min tekens bevatten.',
    ],
    'min_digits' => 'Het veld :attribute moet minimaal :min cijfers bevatten.',
    'multiple_of' => 'Het veld :attribute moet een veelvoud zijn van :value.',
    'not_in' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'not_regex' => 'Het formaat van het veld :attribute is ongeldig.',
    'numeric' => 'Het veld :attribute moet een getal zijn.',
    'password' => [
        'letters' => 'Het veld :attribute moet minimaal één letter bevatten.',
        'mixed' => 'Het veld :attribute moet minimaal één hoofdletter en één kleine letter bevatten.',
        'numbers' => 'Het veld :attribute moet minimaal één cijfer bevatten.',
        'symbols' => 'Het veld :attribute moet minimaal één symbool bevatten.',
        'uncompromised' => 'Het opgegeven :attribute is verschenen in een datalek. Kies een ander :attribute.',
    ],
    'present' => 'Het veld :attribute moet aanwezig zijn.',
    'prohibited' => 'Het veld :attribute is niet toegestaan.',
    'prohibited_if' => 'Het veld :attribute is niet toegestaan wanneer :other gelijk is aan :value.',
    'prohibited_unless' => 'Het veld :attribute is niet toegestaan tenzij :other aanwezig is in :values.',
    'prohibits' => 'Het veld :attribute staat niet toe dat :other aanwezig is.',
    'regex' => 'Het formaat van het veld :attribute is ongeldig.',
    'required' => 'Het veld :attribute is verplicht.',
    'required_array_keys' => 'Het veld :attribute moet vermeldingen bevatten voor: :values.',
    'required_if' => 'Het veld :attribute is verplicht wanneer :other gelijk is aan :value.',
    'required_if_accepted' => 'Het veld :attribute is verplicht wanneer :other geaccepteerd is.',
    'required_unless' => 'Het veld :attribute is verplicht tenzij :other aanwezig is in :values.',
    'required_with' => 'Het veld :attribute is verplicht wanneer :values aanwezig is.',
    'required_with_all' => 'Het veld :attribute is verplicht wanneer :values aanwezig zijn.',
    'required_without' => 'Het veld :attribute is verplicht wanneer :values niet aanwezig is.',
    'required_without_all' => 'Het veld :attribute is verplicht wanneer geen van :values aanwezig is.',
    'same' => 'Het veld :attribute en :other moeten overeenkomen.',
    'size' => [
        'array' => 'Het veld :attribute moet :size items bevatten.',
        'file' => 'Het veld :attribute moet :size kilobytes zijn.',
        'numeric' => 'Het veld :attribute moet :size zijn.',
        'string' => 'Het veld :attribute moet :size tekens bevatten.',
    ],
    'starts_with' => 'Het veld :attribute moet beginnen met een van de volgende waarden: :values.',
    'string' => 'Het veld :attribute moet een tekenreeks zijn.',
    'timezone' => 'Het veld :attribute moet een geldige tijdzone zijn.',
    'unique' => 'Het veld :attribute is al in gebruik.',
    'uploaded' => 'Het uploaden van :attribute is mislukt.',
    'url' => 'Het veld :attribute moet een geldige URL zijn.',
    'uuid' => 'Het veld :attribute moet een geldige UUID zijn.',

    /*
    |--------------------------------------------------------------------------
    | Aangepaste validatietaalregels
    |--------------------------------------------------------------------------
    |
    | Hier kun je aangepaste validatiemeldingen opgeven voor attributen met
    | de conventie "attribuut.regel" als naam van de regels. Hierdoor kun je
    | snel een specifieke aangepaste taalregel opgeven voor een bepaalde
    | attribuutregel.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Aangepaste validatieattributen
    |--------------------------------------------------------------------------
    |
    | De volgende taalregels worden gebruikt om onze attribuutplaceholder te
    | vervangen door iets leesvriendelijkers, zoals "E-mailadres" in plaats
    | van "email". Dit helpt onze berichten expressiever te maken.
    |
    */

    'attributes' => [],

];
