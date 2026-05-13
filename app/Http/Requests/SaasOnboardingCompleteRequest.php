<?php

namespace App\Http\Requests;

use App\Models\ISP;
use App\Services\PlatformSaasBillingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaasOnboardingCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->routeIs('admin.isps.saas-onboarding.*')) {
            return $user->isSuperAdmin();
        }

        if ($this->routeIs('isp.saas-onboarding.*')) {
            return $user->isIspAdmin() && (bool) $user->isp_id;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->routeIs('admin.isps.saas-onboarding.*')) {
            return [
                'platform_saas_plan_id' => ['required', 'exists:platform_saas_plans,id'],
                'company_name' => ['required', 'string', 'max:255'],
                'saas_company_pin' => ['nullable', 'string', 'max:32'],
                'saas_billing_method' => ['required', 'in:mpesa,bank,card'],
                'saas_billing_phone' => ['required_if:saas_billing_method,mpesa', 'nullable', 'string', 'max:20'],
                'billing_email' => ['required_if:saas_billing_method,card', 'nullable', 'email', 'max:255'],
                'bank_name' => ['required_if:saas_billing_method,bank', 'nullable', 'string', 'max:120'],
                'bank_account_name' => ['required_if:saas_billing_method,bank', 'nullable', 'string', 'max:120'],
                'bank_account_number' => ['required_if:saas_billing_method,bank', 'nullable', 'string', 'max:64'],
                'bank_branch' => ['nullable', 'string', 'max:120'],
                'cardholder_name' => ['nullable', 'string', 'max:120'],
                'payment_reference_note' => ['nullable', 'string', 'max:500'],
                'saas_white_label_opt_in' => ['nullable', 'boolean'],
            ];
        }

        $step = (int) $this->input('wizard_step', 1);
        $base = ['wizard_step' => ['required', 'integer', 'in:1,2,3,4']];

        if ($this->routeIs('isp.saas-onboarding.*') && $this->input('wizard_action') === 'go_back') {
            $isp = ISP::query()->find($this->user()->isp_id);
            $serverStep = min(4, max(1, (int) ($isp?->saas_onboarding_step ?? 1)));

            return [
                'wizard_action' => ['required', 'in:go_back'],
                'wizard_step' => ['required', 'integer', Rule::in([$serverStep])],
            ];
        }

        if ($step === 2 && $this->input('wizard_action') === 'continue_after_verify') {
            return array_merge($base, [
                'wizard_action' => ['required', 'in:continue_after_verify'],
            ]);
        }

        if ($step === 2 && $this->input('wizard_action') === 'local_dev_skip_mpesa_verify') {
            return array_merge($base, [
                'wizard_action' => ['required', 'in:local_dev_skip_mpesa_verify'],
            ]);
        }

        return match ($step) {
            1 => array_merge($base, [
                'platform_saas_plan_id' => ['required', 'exists:platform_saas_plans,id'],
            ]),
            2 => array_merge($base, [
                'company_name' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:2000'],
                'support_phone' => ['nullable', 'string', 'max:32'],
                'saas_company_pin' => ['nullable', 'string', 'max:32'],
                'saas_billing_method' => ['required', 'in:mpesa,bank,card'],
                'saas_billing_phone' => ['required_if:saas_billing_method,mpesa', 'nullable', 'string', 'max:20'],
                'billing_email' => ['required_if:saas_billing_method,card', 'nullable', 'email', 'max:255'],
                'bank_name' => ['required_if:saas_billing_method,bank', 'nullable', 'string', 'max:120'],
                'bank_account_name' => ['required_if:saas_billing_method,bank', 'nullable', 'string', 'max:120'],
                'bank_account_number' => ['required_if:saas_billing_method,bank', 'nullable', 'string', 'max:64'],
                'bank_branch' => ['nullable', 'string', 'max:120'],
                'cardholder_name' => ['nullable', 'string', 'max:120'],
                'payment_reference_note' => ['nullable', 'string', 'max:500'],
                'saas_white_label_opt_in' => ['nullable', 'boolean'],
            ]),
            3 => array_merge($base, [
                'router_name' => ['required', 'string', 'max:255'],
                'router_ip_address' => ['required', 'string', 'max:255'],
                'router_port' => ['required', 'integer', 'min:1', 'max:65535'],
                'router_username' => ['required', 'string', 'max:255'],
                'router_password' => ['required', 'string', 'max:255'],
                'import_profile_names_csv' => ['nullable', 'string', 'max:5000'],
                'new_plan_name' => ['nullable', 'string', 'max:255'],
                'new_plan_speed_profile' => ['nullable', 'string', 'max:255'],
                'new_plan_price' => ['nullable', 'numeric', 'min:0'],
            ]),
            4 => array_merge($base, [
                'mpesa_consumer_key' => ['required', 'string', 'max:255'],
                'mpesa_consumer_secret' => ['required', 'string', 'max:255'],
                'mpesa_shortcode' => ['required', 'string', 'max:50'],
                'mpesa_passkey' => ['required', 'string', 'max:255'],
                'mpesa_environment' => ['nullable', 'in:sandbox,production'],
                'mpesa_active' => ['nullable', 'boolean'],
            ]),
            default => array_merge($base, [
                'wizard_step' => ['in:0'],
            ]),
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->routeIs('isp.saas-onboarding.*') && $this->input('wizard_action') === 'go_back') {
                return;
            }

            if ($this->routeIs('admin.isps.saas-onboarding.*')) {
                if ($this->input('saas_billing_method') !== 'mpesa') {
                    return;
                }
                $raw = (string) $this->input('saas_billing_phone', '');
                if (! PlatformSaasBillingService::normalizeKenyanMsisdn($raw)) {
                    $validator->errors()->add(
                        'saas_billing_phone',
                        'Enter a valid Kenyan M-Pesa number in international format (2547XXXXXXXX).'
                    );
                }

                return;
            }

            if ((int) $this->input('wizard_step') === 2 && ! in_array($this->input('wizard_action'), ['continue_after_verify', 'local_dev_skip_mpesa_verify'], true)) {
                if ($this->input('saas_billing_method') === 'mpesa') {
                    $raw = (string) $this->input('saas_billing_phone', '');
                    if (! PlatformSaasBillingService::normalizeKenyanMsisdn($raw)) {
                        $validator->errors()->add(
                            'saas_billing_phone',
                            'Enter a valid Kenyan M-Pesa number in international format (2547XXXXXXXX).'
                        );
                    }
                }
            }

            if ((int) $this->input('wizard_step') === 3) {
                $n = $this->input('new_plan_name');
                $p = $this->input('new_plan_speed_profile');
                if (filled($n) && ! filled($p)) {
                    $validator->errors()->add('new_plan_speed_profile', 'Enter the MikroTik profile name for this plan, or clear the plan name.');
                }
                if (filled($p) && ! filled($n)) {
                    $validator->errors()->add('new_plan_name', 'Enter a display name for the plan, or clear the profile field.');
                }
            }
        });
    }

    /**
     * Payload for {@see \App\Services\PlatformSaasBillingService::completeOnboarding()}.
     *
     * @return array<string, mixed>
     */
    public function billingPayload(): array
    {
        return $this->only([
            'company_name',
            'saas_company_pin',
            'saas_billing_phone',
            'saas_billing_method',
            'billing_email',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'bank_branch',
            'cardholder_name',
            'payment_reference_note',
        ]);
    }
}
