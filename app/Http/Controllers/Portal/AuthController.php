<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the customer login form.
     */
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    /**
     * Log a customer in using their mobile number.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'mobile_number' => 'required|string',
        ]);

        $customer = Customer::query()
            ->where('mobile_number', trim($data['mobile_number']))
            ->where('status', Customer::STATUS_ACTIVE)
            ->first();

        if (! $customer) {
            return back()
                ->withInput()
                ->withErrors(['mobile_number' => 'No active customer found with this mobile number.']);
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    /**
     * Log the customer out.
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
