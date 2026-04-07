<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\User;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return redirect()->route('isp.settings.company');
    }

    public function company()
    {
        $isp = auth()->user()->isp;
        return view('isp.settings.company', compact('isp'));
    }

    public function updateCompany(Request $request)
    {
        $isp = auth()->user()->isp;
        $isp->update($request->only(['company_name', 'email', 'phone', 'address', 'currency', 'timezone']));
        return redirect()->route('isp.settings.company')->with('success', 'Company information updated successfully');
    }

    public function paymentGateways()
    {
        $isp = auth()->user()->isp;
        return view('isp.settings.payment-gateways', compact('isp'));
    }

    public function updatePaymentGateways(Request $request)
    {
        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];
        
        $settings['payment_gateways'] = [
            'mpesa' => [
                'enabled' => $request->has('mpesa_enabled'),
                'consumer_key' => $request->input('mpesa_consumer_key'),
                'consumer_secret' => $request->input('mpesa_consumer_secret'),
                'shortcode' => $request->input('mpesa_shortcode'),
                'passkey' => $request->input('mpesa_passkey'),
            ],
            'selcom' => [
                'enabled' => $request->has('selcom_enabled'),
                'api_key' => $request->input('selcom_api_key'),
                'api_secret' => $request->input('selcom_api_secret'),
                'vendor_id' => $request->input('selcom_vendor_id'),
            ],
            'manual' => [
                'enabled' => $request->has('manual_enabled'),
            ],
        ];
        
        $isp->update(['settings' => $settings]);
        return redirect()->route('isp.settings.payment-gateways')->with('success', 'Payment gateway settings updated successfully');
    }

    public function notifications()
    {
        $isp = auth()->user()->isp;
        return view('isp.settings.notifications', compact('isp'));
    }

    public function updateNotifications(Request $request)
    {
        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];
        $settings['notifications'] = [
            'email_notifications' => $request->has('email_notifications'),
            'notification_email' => $request->input('notification_email'),
            'sms_notifications' => $request->has('sms_notifications'),
            'sms_provider' => $request->input('sms_provider'),
            'invoice_reminders' => $request->has('invoice_reminders'),
            'reminder_days' => $request->input('reminder_days', 3),
        ];
        $isp->update(['settings' => $settings]);
        return redirect()->route('isp.settings.notifications')->with('success', 'Notification preferences updated successfully');
    }

    public function users()
    {
        $isp = auth()->user()->isp;
        $users = User::where('isp_id', $isp->id)->get();
        return view('isp.settings.users', compact('isp', 'users'));
    }

    public function billing()
    {
        $isp = auth()->user()->isp;
        return view('isp.settings.billing', compact('isp'));
    }

    public function updateBilling(Request $request)
    {
        $isp = auth()->user()->isp;
        $settings = $isp->settings ?? [];
        $settings['billing'] = [
            'invoice_prefix' => $request->input('invoice_prefix', 'INV'),
            'auto_generate_invoices' => $request->has('auto_generate_invoices'),
            'grace_period_days' => $request->input('grace_period_days', 3),
            'penalty_rate' => $request->input('penalty_rate', 5),
            'tax_rate' => $request->input('tax_rate', 0),
        ];
        $isp->update(['settings' => $settings]);
        return redirect()->route('isp.settings.billing')->with('success', 'Billing settings updated successfully');
    }
}

