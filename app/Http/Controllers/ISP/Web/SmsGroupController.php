<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SmsGroupController extends Controller
{
    public function index(): View
    {
        $ispId = Auth::user()->isp_id;
        $groups = SmsGroup::forIsp($ispId)
            ->withCount('customers')
            ->orderBy('name')
            ->get();

        $customers = Customer::forIsp($ispId)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone_number']);

        return view('isp.communications.groups', compact('groups', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $ispId = Auth::user()->isp_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer|exists:customers,id',
        ]);

        $group = SmsGroup::create([
            'isp_id' => $ispId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (! empty($validated['customer_ids'])) {
            $allowed = Customer::forIsp($ispId)
                ->whereIn('id', $validated['customer_ids'])
                ->pluck('id');

            $group->customers()->sync($allowed);
        }

        return redirect()
            ->route('isp.communications.groups.index')
            ->with('success', 'SMS group created.');
    }

    public function destroy(SmsGroup $group): RedirectResponse
    {
        if ($group->isp_id !== Auth::user()->isp_id) {
            abort(403);
        }

        $group->delete();

        return redirect()
            ->route('isp.communications.groups.index')
            ->with('success', 'SMS group deleted.');
    }
}
