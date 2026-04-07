<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\IspPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentSettingsController extends Controller
{
    public function index()
    {
        $ispId = auth()->user()->isp_id;
        
        $paymentMethods = IspPaymentMethod::forIsp($ispId)->get();
        
        // Group by provider for easier display
        $groupedMethods = $paymentMethods->groupBy('provider');
        
        return view('isp.settings.payment-settings', compact('groupedMethods'));
    }

    public function create()
    {
        $providers = [
            'mpesa' => 'M-Pesa (Safaricom)',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe (Card Payments)',
            'flutterwave' => 'Flutterwave',
            'selcom' => 'Selcom (Tanzania)',
        ];

        return view('isp.settings.payment-methods.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        
        $validator = $this->getValidator($request);
        
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        
        // Prepare credentials based on provider
        $credentials = $this->prepareCredentials($validated);
        
        // Create payment method
        IspPaymentMethod::create([
            'isp_id' => $ispId,
            'provider' => $validated['provider'],
            'credentials' => $credentials,
            'callback_url' => $validated['callback_url'] ?? null,
            'active' => $validated['active'] ?? false,
        ]);

        return redirect()
            ->route('isp.payment-settings.index')
            ->with('success', 'Payment method added successfully!');
    }

    public function edit(IspPaymentMethod $paymentMethod)
    {
        // Ensure user can only edit their own ISP's payment methods
        if ($paymentMethod->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $providers = [
            'mpesa' => 'M-Pesa (Safaricom)',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe (Card Payments)',
            'flutterwave' => 'Flutterwave',
            'selcom' => 'Selcom (Tanzania)',
        ];

        return view('isp.settings.payment-methods.edit', compact('paymentMethod', 'providers'));
    }

    public function update(Request $request, IspPaymentMethod $paymentMethod)
    {
        // Ensure user can only update their own ISP's payment methods
        if ($paymentMethod->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $validator = $this->getValidator($request, $paymentMethod);
        
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        
        // Prepare credentials based on provider
        $credentials = $this->prepareCredentials($validated);
        
        $paymentMethod->update([
            'credentials' => $credentials,
            'callback_url' => $validated['callback_url'] ?? null,
            'active' => $validated['active'] ?? false,
        ]);

        return redirect()
            ->route('isp.payment-settings.index')
            ->with('success', 'Payment method updated successfully!');
    }

    public function destroy(IspPaymentMethod $paymentMethod)
    {
        // Ensure user can only delete their own ISP's payment methods
        if ($paymentMethod->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $paymentMethod->delete();

        return redirect()
            ->route('isp.payment-settings.index')
            ->with('success', 'Payment method deleted successfully!');
    }

    public function toggle(IspPaymentMethod $paymentMethod)
    {
        // Ensure user can only toggle their own ISP's payment methods
        if ($paymentMethod->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if ($paymentMethod->active) {
            $paymentMethod->deactivate();
            $message = 'Payment method deactivated successfully!';
        } else {
            $paymentMethod->activate();
            $message = 'Payment method activated successfully!';
        }

        return back()->with('success', $message);
    }

    protected function getValidator(Request $request, ?IspPaymentMethod $paymentMethod = null)
    {
        $provider = $request->input('provider');
        
        $rules = [
            'provider' => 'required|string|in:mpesa,paypal,stripe,flutterwave,selcom',
            'callback_url' => 'nullable|url',
            'active' => 'nullable|boolean',
        ];

        // Add provider-specific validation rules
        switch ($provider) {
            case 'mpesa':
                $rules = array_merge($rules, [
                    'consumer_key' => 'required|string',
                    'consumer_secret' => 'required|string',
                    'passkey' => 'required|string',
                    'shortcode' => 'required|string',
                    'environment' => 'required|in:sandbox,production',
                ]);
                break;
                
            case 'paypal':
                $rules = array_merge($rules, [
                    'client_id' => 'required|string',
                    'client_secret' => 'required|string',
                    'environment' => 'required|in:sandbox,production',
                ]);
                break;
                
            case 'stripe':
                $rules = array_merge($rules, [
                    'publishable_key' => 'required|string',
                    'secret_key' => 'required|string',
                    'webhook_secret' => 'nullable|string',
                ]);
                break;
                
            case 'flutterwave':
                $rules = array_merge($rules, [
                    'public_key' => 'required|string',
                    'secret_key' => 'required|string',
                    'encryption_key' => 'required|string',
                    'environment' => 'required|in:sandbox,production',
                ]);
                break;
                
            case 'selcom':
                $rules = array_merge($rules, [
                    'api_key' => 'required|string',
                    'api_secret' => 'required|string',
                    'vendor' => 'required|string',
                    'environment' => 'required|in:sandbox,production',
                ]);
                break;
        }

        // Check for uniqueness if creating new payment method
        if (!$paymentMethod) {
            $rules['provider'] .= '|unique:isp_payment_methods,provider,NULL,id,isp_id,' . auth()->user()->isp_id;
        }

        return Validator::make($request->all(), $rules, [
            'provider.unique' => 'You already have a payment method configured for this provider.',
        ]);
    }

    protected function prepareCredentials(array $validated): array
    {
        $provider = $validated['provider'];
        $credentials = [];

        switch ($provider) {
            case 'mpesa':
                $credentials = [
                    'consumer_key' => $validated['consumer_key'],
                    'consumer_secret' => $validated['consumer_secret'],
                    'passkey' => $validated['passkey'],
                    'shortcode' => $validated['shortcode'],
                    'environment' => $validated['environment'],
                ];
                break;
                
            case 'paypal':
                $credentials = [
                    'client_id' => $validated['client_id'],
                    'client_secret' => $validated['client_secret'],
                    'environment' => $validated['environment'],
                ];
                break;
                
            case 'stripe':
                $credentials = [
                    'publishable_key' => $validated['publishable_key'],
                    'secret_key' => $validated['secret_key'],
                    'webhook_secret' => $validated['webhook_secret'] ?? null,
                ];
                break;
                
            case 'flutterwave':
                $credentials = [
                    'public_key' => $validated['public_key'],
                    'secret_key' => $validated['secret_key'],
                    'encryption_key' => $validated['encryption_key'],
                    'environment' => $validated['environment'],
                ];
                break;
                
            case 'selcom':
                $credentials = [
                    'api_key' => $validated['api_key'],
                    'api_secret' => $validated['api_secret'],
                    'vendor' => $validated['vendor'],
                    'environment' => $validated['environment'],
                ];
                break;
        }

        return $credentials;
    }
}
