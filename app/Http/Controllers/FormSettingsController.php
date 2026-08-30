<?php

namespace App\Http\Controllers;

use App\Support\FormSettingsManager;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormSettingsController extends Controller
{
    public function __construct(
        protected FormSettingsManager $manager,
    ) {}

    public function index(Request $request): View
    {
        $scope = $this->resolveScope($request);
        $ispId = $this->resolveIspId($scope);
        $forms = $this->manager->rows($ispId, $scope);
        $activeFormKey = trim((string) $request->query('form', ''));
        $activeForm = $activeFormKey !== ''
            ? $forms->first(fn (array $form) => $form['form_key'] === $activeFormKey)
            : null;

        return view('settings.forms.index', [
            'scope' => $scope,
            'ispId' => $ispId,
            'forms' => $forms,
            'activeForm' => $activeForm,
            'activeFormKey' => $activeFormKey,
            'settingsBackUrl' => $this->settingsBackUrl($scope),
            'updateRoute' => $this->updateRoute($scope),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $scope = $this->resolveScope($request);
        $ispId = $this->resolveIspId($scope);

        $validated = $request->validate([
            'forms' => ['required', 'array'],
            'forms.*.is_active' => ['nullable', 'in:0,1,true,false'],
            'forms.*.fields' => ['nullable', 'array'],
            'forms.*.fields.*.visibility' => ['nullable', 'in:visible,hidden'],
            'forms.*.fields.*.requirement' => ['nullable', 'in:required,optional'],
            'forms.*.fields.*.read_only' => ['nullable', 'in:0,1,true,false'],
            'forms.*.fields.*.default_value' => ['nullable', 'string', 'max:500'],
        ]);

        $this->manager->save($ispId, $scope, $validated['forms']);

        try {
            ActivityLogService::logAction(
                'form_settings_update',
                'Updated form controls ('.$scope.')',
                [
                    'scope' => $scope,
                    'isp_id' => $ispId,
                    'forms' => array_keys($validated['forms']),
                ]
            );
        } catch (\Throwable $e) {
            // Best-effort audit.
        }

        $returnForm = trim((string) $request->input('return_form', $request->query('form', '')));

        return redirect()
            ->route($scope === 'platform' ? 'admin.settings.forms.index' : 'isp.settings.forms.index', array_filter([
                'form' => $returnForm ?: null,
            ]))
            ->with('success', __('Form settings saved successfully.'));
    }

    protected function resolveScope(Request $request): string
    {
        if ($request->routeIs('admin.*')) {
            return 'platform';
        }

        return 'isp';
    }

    protected function resolveIspId(string $scope): ?int
    {
        if ($scope === 'platform') {
            return null;
        }

        return auth()->user()?->isp_id;
    }

    protected function settingsBackUrl(string $scope): string
    {
        return $scope === 'platform'
            ? route('admin.settings.index')
            : route('isp.settings.index');
    }

    protected function updateRoute(string $scope): string
    {
        return $scope === 'platform'
            ? route('admin.settings.forms.update')
            : route('isp.settings.forms.update');
    }
}
