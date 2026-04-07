<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit(Request $request)
    {
        $customer = $request->user();
        
        return view('customer.profile.edit', compact('customer'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $customer = $request->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customer.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
