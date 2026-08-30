<?php

namespace App\Support;

use App\Models\FormFieldSetting;
use App\Models\FormSetting;
use Illuminate\Support\Collection;

class FormSettingsManager
{
    public function __construct(
        protected FormSettingsService $forms,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(?int $ispId, string $scope = 'isp'): Collection
    {
        $this->ensureForms($ispId, $scope);

        return collect($this->forms->formsForScope($scope))
            ->map(function (array $meta, string $formKey) use ($ispId) {
                $formSetting = $this->findFormSetting($ispId, $formKey);

                $fields = collect($meta['fields'])
                    ->map(fn (array $fieldMeta, string $fieldKey) => $this->forms->adminFieldConfig(
                        $formKey,
                        $fieldKey,
                        $ispId,
                    ))
                    ->sortBy('sort_order')
                    ->values()
                    ->all();

                return [
                    'form_key' => $formKey,
                    'label' => $meta['label'],
                    'description' => $meta['description'] ?? '',
                    'scope' => $meta['scope'] ?? 'isp',
                    'is_active' => (bool) ($formSetting?->is_active ?? true),
                    'fields' => $fields,
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, array<string, mixed>>  $payload
     */
    public function save(?int $ispId, string $scope, array $payload): void
    {
        foreach ($this->forms->formsForScope($scope) as $formKey => $meta) {
            if (! isset($payload[$formKey])) {
                continue;
            }

            $input = $payload[$formKey];

            $formSetting = FormSetting::query()->updateOrCreate(
                [
                    'isp_id' => $ispId,
                    'form_key' => $formKey,
                ],
                [
                    'is_active' => filter_var($input['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ],
            );

            foreach ($input['fields'] ?? [] as $fieldKey => $fieldInput) {
                $fieldMeta = $meta['fields'][$fieldKey] ?? null;

                if (! $fieldMeta) {
                    continue;
                }

                $this->persistFieldSetting($formSetting, $fieldKey, $fieldInput, $fieldMeta);
            }
        }

        $this->forms->clearResolvedCache();
    }

    /**
     * @param  array<string, mixed>  $fieldInput
     * @param  array<string, mixed>  $fieldMeta
     */
    protected function persistFieldSetting(
        FormSetting $formSetting,
        string $fieldKey,
        array $fieldInput,
        array $fieldMeta,
    ): void {
        $registryRequired = (bool) ($fieldMeta['required'] ?? false);
        $visibility = $fieldInput['visibility'] ?? 'visible';
        $requirement = $registryRequired ? 'required' : ($fieldInput['requirement'] ?? 'optional');

        if ($requirement === 'required') {
            $visibility = 'visible';
        }

        $readOnly = filter_var($fieldInput['read_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $defaultData = filled($fieldInput['default_value'] ?? null)
            ? $fieldInput['default_value']
            : null;

        FormFieldSetting::query()->updateOrCreate(
            [
                'form_setting_id' => $formSetting->id,
                'field_key' => $fieldKey,
            ],
            [
                'is_required' => $requirement === 'required',
                'is_visible' => $visibility !== 'hidden',
                'is_hidden' => $visibility === 'hidden',
                'default_value' => [
                    'data' => $defaultData,
                    'read_only' => $readOnly,
                ],
                'sort_order' => (int) ($fieldMeta['sort_order'] ?? 0),
            ],
        );
    }

    public function ensureForms(?int $ispId, string $scope = 'isp'): void
    {
        foreach ($this->forms->formsForScope($scope) as $formKey => $meta) {
            $formSetting = FormSetting::query()->firstOrCreate(
                [
                    'isp_id' => $ispId,
                    'form_key' => $formKey,
                ],
                ['is_active' => true],
            );

            foreach ($meta['fields'] as $fieldKey => $fieldMeta) {
                FormFieldSetting::query()->firstOrCreate(
                    [
                        'form_setting_id' => $formSetting->id,
                        'field_key' => $fieldKey,
                    ],
                    [
                        'is_required' => (bool) ($fieldMeta['required'] ?? false),
                        'is_visible' => (bool) ($fieldMeta['visible'] ?? true),
                        'is_hidden' => ! ($fieldMeta['visible'] ?? true),
                        'default_value' => isset($fieldMeta['default'])
                            ? ['data' => $fieldMeta['default'], 'read_only' => false]
                            : null,
                        'sort_order' => (int) ($fieldMeta['sort_order'] ?? 0),
                    ],
                );
            }
        }
    }

    protected function findFormSetting(?int $ispId, string $formKey): ?FormSetting
    {
        return FormSetting::query()
            ->where('isp_id', $ispId)
            ->where('form_key', $formKey)
            ->with('fields')
            ->first();
    }
}
