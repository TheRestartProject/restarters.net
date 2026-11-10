<?php

namespace App\Services;

use App\Group;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A group's own item fields (RES-2063), shown in the item form when the custom_fields feature is on.
 *
 * Each field has a key that never changes, a label, a type (text, number or choice) and, for a choice, its
 * options. Fields are stored as JSON on the group. A removed field stays stored with "removed" set, so its
 * values on items are kept and its key is never given to another field. Values are stored as JSON on each item.
 */
class CustomFields
{
    public const MAX_FIELDS = 10;
    public const TYPES = ['text', 'number', 'choice'];
    public const MAX_TEXT = 255;

    /**
     * Validation rules for a list of field definitions, as sent by the group's Options.
     */
    public static function rules(string $prefix = 'custom_fields'): array
    {
        return [
            $prefix => ['array', 'max:' . self::MAX_FIELDS],
            "$prefix.*.key" => ['nullable', 'string', 'max:40'],
            "$prefix.*.label" => ['required', 'string', 'max:50'],
            "$prefix.*.type" => ['required', 'in:' . implode(',', self::TYPES)],
            "$prefix.*.options" => ["required_if:$prefix.*.type,choice", 'array', 'max:20'],
            "$prefix.*.options.*" => ['required', 'string', 'max:50', 'distinct'],
        ];
    }

    /**
     * The group's fields that are in use.
     *
     * @return array<int, array{key: string, label: string, type: string, options: string[]}>
     */
    public static function active(Group $group): array
    {
        $fields = is_array($group->custom_fields) ? $group->custom_fields : [];

        return array_values(array_map(
            fn ($field) => [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => array_values($field['options'] ?? []),
            ],
            array_filter($fields, fn ($field) => empty($field['removed']))
        ));
    }

    /**
     * The stored field list after the group sends its new list of fields in use. A field keeps its key when the
     * key is sent; a field without a known key gets a new one from its label; fields left out are marked removed.
     */
    public static function update(?array $stored, array $sent): array
    {
        $stored = $stored ?? [];
        $storedKeys = array_column($stored, 'key');
        $taken = $storedKeys;
        $kept = [];
        $result = [];

        foreach ($sent as $field) {
            $key = $field['key'] ?? null;

            if (! $key || ! in_array($key, $storedKeys, true) || in_array($key, $kept, true)) {
                $key = self::newKey($field['label'], $taken);
                $taken[] = $key;
            }

            $kept[] = $key;
            $type = $field['type'];
            $result[] = [
                'key' => $key,
                'label' => trim($field['label']),
                'type' => $type,
                'options' => $type === 'choice' ? array_values(array_map('trim', $field['options'] ?? [])) : [],
            ];
        }

        foreach ($stored as $field) {
            if (! in_array($field['key'], $kept, true)) {
                $result[] = ['removed' => true] + $field;
            }
        }

        return $result;
    }

    private static function newKey(string $label, array $taken): string
    {
        $base = Str::limit(Str::slug(Str::ascii($label), '_'), 30, '') ?: 'field';

        if (ctype_digit($base[0])) {
            $base = "f_$base";
        }

        $key = $base;

        for ($n = 2; in_array($key, $taken, true); $n++) {
            $key = "{$base}_$n";
        }

        return $key;
    }

    /**
     * An item's values after an update. Values not sent are kept; null or "" removes one. Only the group's
     * fields in use take new values, and each must suit its field's type.
     *
     * @throws ValidationException
     */
    public static function mergeValues(Group $group, ?array $existing, array $sent): array
    {
        $fields = array_column(self::active($group), null, 'key');
        $values = $existing ?? [];
        $errors = [];

        foreach ($sent as $key => $value) {
            $error = "custom_values.$key";

            if (! isset($fields[$key])) {
                $errors[$error] = "This group has no custom item field \"$key\".";
                continue;
            }

            if ($value === null || $value === '') {
                unset($values[$key]);
                continue;
            }

            $field = $fields[$key];

            if (! is_scalar($value)) {
                $errors[$error] = "{$field['label']} must be a single value.";
                continue;
            }

            switch ($field['type']) {
                case 'number':
                    $number = str_replace(',', '.', trim((string) $value));

                    if (! is_numeric($number)) {
                        $errors[$error] = "{$field['label']} must be a number.";
                        continue 2;
                    }

                    $values[$key] = $number + 0;
                    break;
                case 'choice':
                    if (! in_array((string) $value, $field['options'], true)) {
                        $errors[$error] = "{$field['label']} must be one of: " . implode(', ', $field['options']) . '.';
                        continue 2;
                    }

                    $values[$key] = (string) $value;
                    break;
                default:
                    if (mb_strlen((string) $value) > self::MAX_TEXT) {
                        $errors[$error] = "{$field['label']} must be at most " . self::MAX_TEXT . ' characters.';
                        continue 2;
                    }

                    $values[$key] = (string) $value;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $values;
    }
}
