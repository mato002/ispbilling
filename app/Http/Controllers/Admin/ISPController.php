<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ISPController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $query = ISP::withCount(['customers', 'routers', 'plans']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $isps = $query->latest()->paginate(20);

        return view('admin.isps.index', compact('isps'));
    }

    public function create()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('admin.isps.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:i_s_p_s,email',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'currency' => 'required|string|in:KES,USD,TZS,UGX',
            'timezone' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        ISP::create($validated);

        return redirect()->route('admin.isps.index')->with('success', 'ISP created successfully');
    }

    public function show(ISP $isp)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isp->loadCount(['customers', 'routers', 'plans', 'invoices', 'payments', 'users']);
        return view('admin.isps.show', compact('isp'));
    }

    public function edit(ISP $isp)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('admin.isps.edit', compact('isp'));
    }

    public function update(Request $request, ISP $isp)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('i_s_p_s', 'email')->ignore($isp->id)],
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'currency' => 'required|string|in:KES,USD,TZS,UGX',
            'timezone' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $isp->update($validated);

        return redirect()->route('admin.isps.index')->with('success', 'ISP updated successfully');
    }

    public function destroy(ISP $isp)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $isp->delete();

        return redirect()->route('admin.isps.index')->with('success', 'ISP deleted successfully');
    }
}
