<?php

namespace App\Support;

use App\Models\ISP;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IspCustomFields
{
    /**
     * @return list<array{id: string, key: string, label: string, type: string, required: bool, options: list<string>, rules: string}>
     */
    public static function definitionsFor(ISP $isp, string $entity = 'customer'): array
    {
        $defs = $isp->settings['custom_field_definitions'][$entity] ?? [];

        return collect($defs)
            ->filter(fn ($row) => is_array($row) && ! empty($row['key']))
            ->values()
            ->map(function (array $row) {
                return [
                    'id' => (string) ($row['id'] ?? $row['key']),
                    'key' => (string) $row['key'],
                    'label' => (string) ($row['label'] ?? $row['key']),
                    'type' => in_array($row['type'] ?? 'text', self::allowedTypes(), true)
                        ? (string) $row['type']
                        : 'text',
                    'required' => filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'options' => array_values(array_filter(array_map('trim', $row['options'] ?? []))),
                    'rules' => (string) ($row['rules'] ?? 'nullable|string|max:2000'),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validateAndExtract(Request $request, ISP $isp, string $entity = 'customer'): array
    {
        $definitions = self::definitionsFor($isp, $entity);
        if ($definitions === []) {
            return [];
        }

        $payload = $request->input('custom_fields', []);
        if (! is_array($payload)) {
            $payload = [];
        }

        $rules = [];
        foreach ($definitions as $def) {
            $field = 'custom_fields.'.$def['key'];
            $parts = [$def['required'] ? 'required' : 'nullable'];
            $parts = array_merge($parts, match ($def['type']) {
                'textarea' => ['string', 'max:10000'],
                'number' => ['numeric'],
                'email' => ['string', 'email', 'max:255'],
                'date' => ['date'],
                'select' => ['string', 'max:500'],
                default => ['string', 'max:2000'],
            });
            if ($def['type'] === 'select' && count($def['options'] ?? []) > 0) {
                $parts[] = Rule::in($def['options']);
            }
            $rules[$field] = $parts;
        }

        Validator::make(['custom_fields' => $payload], $rules)->validate();

        $out = [];
        foreach ($definitions as $def) {
            $k = $def['key'];
            if (! array_key_exists($k, $payload)) {
                continue;
            }
            $val = $payload[$k];
            if ($val === null || $val === '') {
                continue;
            }
            $out[$k] = $def['type'] === 'number' && is_numeric($val) ? 0 + $val : (is_string($val) ? trim($val) : $val);
        }

        return $out;
    }

    /**
     * Normalize posted repeater into storage array for ISP settings.
     *
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    public static function normalizePostedDefinitions(array $input, string $entity = 'customer'): array
    {
        $keys = Arr::get($input, 'cf_key', []);
        if (! is_array($keys)) {
            return [];
        }

        $labels = Arr::get($input, 'cf_label', []);
        $types = Arr::get($input, 'cf_type', []);
        $required = Arr::get($input, 'cf_required', []);
        $rules = Arr::get($input, 'cf_rules', []);
        $options = Arr::get($input, 'cf_options', []);

        $out = [];
        foreach ($keys as $i => $keyRaw) {
            $key = strtolower(trim((string) $keyRaw));
            $key = preg_replace('/[^a-z0-9_]/', '_', $key) ?? '';
            $key = trim($key, '_');
            if ($key === '') {
                continue;
            }
            $label = trim((string) ($labels[$i] ?? $key));
            $type = (string) ($types[$i] ?? 'text');
            if (! in_array($type, self::allowedTypes(), true)) {
                $type = 'text';
            }
            $optsRaw = (string) ($options[$i] ?? '');
            $opts = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $optsRaw) ?: [])));

            $out[] = [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'key' => $key,
                'label' => $label !== '' ? $label : $key,
                'type' => $type,
                'required' => ! empty($required[$i]),
                'options' => $opts,
                'rules' => trim((string) ($rules[$i] ?? 'nullable|string|max:2000')) ?: 'nullable|string|max:2000',
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return ['text', 'textarea', 'number', 'email', 'date', 'select'];
    }
}
