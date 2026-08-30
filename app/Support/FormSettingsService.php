<?php

namespace App\Support;

use App\Models\FormFieldSetting;
use App\Models\FormSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FormSettingsService
{
    /** @var array<string, array<string, array<string, mixed>>> */
    protected array $resolvedFieldCache = [];

    public function clearResolvedCache(): void
    {
        $this->resolvedFieldCache = [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function registryForm(string $formKey): array
    {
        return config('form_registry.forms')[$formKey] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function registryFields(string $formKey): array
    {
        return $this->registryForm($formKey)['fields'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function registryField(string $formKey, string $fieldKey): array
    {
        return $this->registryFields($formKey)[$fieldKey] ?? [];
    }

    public function formsForScope(string $scope): array
    {
        return collect(config('form_registry.forms', []))
            ->filter(fn (array $meta) => ($meta['scope'] ?? 'isp') === $scope)
            ->all();
    }

    public function withoutHiddenInputs(Request $request, string $formKey, ?int $ispId = null): Request
    {
        $hiddenKeys = collect($this->resolvedFields($formKey, $ispId))
            ->filter(fn (array $config) => ($config['hidden'] ?? false) || ! ($config['visible'] ?? true))
            ->keys()
            ->all();

        $request->replace(Arr::except($request->all(), $hiddenKeys));

        return $request;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function resolvedFields(string $formKey, ?int $ispId = null): array
    {
        $cacheKey = implode(':', [$formKey, $ispId ?? 'platform']);

        if (isset($this->resolvedFieldCache[$cacheKey])) {
            return $this->resolvedFieldCache[$cacheKey];
        }

        return $this->resolvedFieldCache[$cacheKey] = collect($this->registryFields($formKey))
            ->mapWithKeys(fn (array $meta, string $fieldKey) => [
                $fieldKey => $this->fieldConfig($formKey, $fieldKey, $ispId),
            ])
            ->sortBy('sort_order')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function adminFieldConfig(string $formKey, string $fieldKey, ?int $ispId = null): array
    {
        return $this->buildFieldConfig($formKey, $fieldKey, $ispId, forConfiguration: true);
    }

    /**
     * Runtime field config (honors form is_active).
     *
     * @return array<string, mixed>
     */
    public function fieldConfig(string $formKey, string $fieldKey, ?int $ispId = null): array
    {
        return $this->buildFieldConfig($formKey, $fieldKey, $ispId, forConfiguration: false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFieldConfig(string $formKey, string $fieldKey, ?int $ispId, bool $forConfiguration): array
    {
        $registry = $this->registryField($formKey, $fieldKey);
        $registryRequired = (bool) ($registry['required'] ?? false);
        $source = $this->resolveDbField($formKey, $fieldKey, $ispId, $forConfiguration);

        if ($source === null) {
            $visible = (bool) ($registry['visible'] ?? true);

            return [
                'field_key' => $fieldKey,
                'label' => $registry['label'] ?? $fieldKey,
                'type' => $registry['type'] ?? 'text',
                'required' => $registryRequired,
                'visible' => $visible,
                'hidden' => ! $visible,
                'read_only' => (bool) ($registry['read_only'] ?? false),
                'default' => $registry['default'] ?? null,
                'sort_order' => (int) ($registry['sort_order'] ?? 0),
                'registry_required' => $registryRequired,
                'is_custom' => false,
            ];
        }

        return $this->buildFieldConfigFromSource($source, $registry, $registryRequired, $fieldKey);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFieldConfigFromSource(
        FormFieldSetting $source,
        array $registry,
        bool $registryRequired,
        string $fieldKey,
    ): array {
        $meta = $source->default_value ?? [];
        $required = $registryRequired || (bool) $source->is_required;
        $hidden = (bool) $source->is_hidden;

        if ($required || $registryRequired) {
            $hidden = false;
        }

        $visible = ! $hidden && (bool) $source->is_visible;

        return [
            'field_key' => $fieldKey,
            'label' => $registry['label'] ?? $fieldKey,
            'type' => $registry['type'] ?? 'text',
            'required' => $required,
            'visible' => $visible,
            'hidden' => $hidden,
            'read_only' => (bool) ($meta['read_only'] ?? false),
            'default' => $meta['data'] ?? ($registry['default'] ?? null),
            'sort_order' => (int) ($source->sort_order ?: ($registry['sort_order'] ?? 0)),
            'registry_required' => $registryRequired,
            'is_custom' => false,
        ];
    }

    /**
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, mixed>
     */
    public function validateRequest(
        Request $request,
        string $formKey,
        array $rules,
        ?int $ispId = null,
        array $serverProvidedFields = [],
    ): array {
        $this->withoutHiddenInputs($request, $formKey, $ispId);

        return $request->validate(
            $this->mergeValidationRules($formKey, $rules, $ispId, $serverProvidedFields),
        );
    }

    /**
     * @param  array<string, array<int, mixed>>  $baseRules
     * @return array<string, array<int, mixed>>
     */
    public function mergeValidationRules(
        string $formKey,
        array $baseRules,
        ?int $ispId = null,
        array $serverProvidedFields = [],
    ): array {
        $registryFields = $this->registryFields($formKey);

        foreach ($baseRules as $fieldKey => $rules) {
            // Fields not governed by form controls keep their original rules.
            if (! array_key_exists($fieldKey, $registryFields)) {
                continue;
            }

            $config = $this->fieldConfig($formKey, $fieldKey, $ispId);

            if ($config['hidden'] || ! $config['visible']) {
                if (in_array($fieldKey, $serverProvidedFields, true)) {
                    unset($baseRules[$fieldKey]);
                } else {
                    $baseRules[$fieldKey] = ['prohibited'];
                }

                continue;
            }

            $rules = array_values(array_filter(
                (array) $rules,
                fn ($rule) => ! in_array($rule, ['required', 'nullable'], true)
                    && ! (is_string($rule) && str_starts_with($rule, 'required:')),
            ));

            if ($config['required']) {
                array_unshift($rules, 'required');
            } else {
                array_unshift($rules, 'nullable');
            }

            $baseRules[$fieldKey] = $rules;
        }

        return $baseRules;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyDefaults(string $formKey, array $data, ?int $ispId = null): array
    {
        foreach ($this->resolvedFields($formKey, $ispId) as $fieldKey => $config) {
            if (! $config['visible']) {
                continue;
            }

            if (! array_key_exists($fieldKey, $data) || $data[$fieldKey] === null || $data[$fieldKey] === '') {
                if ($config['default'] !== null && $config['default'] !== '') {
                    $data[$fieldKey] = $config['default'];
                }
            }
        }

        return $data;
    }

    protected function resolveDbField(string $formKey, string $fieldKey, ?int $ispId, bool $forConfiguration = false): ?FormFieldSetting
    {
        $formSetting = $this->resolveFormSetting($formKey, $ispId, $forConfiguration);

        if (! $formSetting) {
            return null;
        }

        return $formSetting->fields->firstWhere('field_key', $fieldKey);
    }

    protected function resolveFormSetting(string $formKey, ?int $ispId, bool $forConfiguration = false): ?FormSetting
    {
        return FormSetting::query()
            ->where('form_key', $formKey)
            ->where('isp_id', $ispId)
            ->when(! $forConfiguration, fn ($query) => $query->where('is_active', true))
            ->with('fields')
            ->first();
    }
}
