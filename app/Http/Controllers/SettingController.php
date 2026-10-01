<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $enableCreditLimit = Setting::isCreditLimitEnabled();
        $strictZero = Setting::isCreditLimitStrictZero();

        return view('settings.index', compact('enableCreditLimit', 'strictZero'));
    }

    public function update(Request $request)
    {
        $enableCreditLimit = $request->boolean('enable_customer_credit_limit');
        $strictZero = $request->boolean('credit_limit_strict_zero');

        Setting::set(
            'enable_customer_credit_limit',
            $enableCreditLimit,
            'boolean',
            'sales',
            'Enforces credit limits for customers when creating sales or POS orders.'
        );

        Setting::set(
            'credit_limit_strict_zero',
            $strictZero,
            'boolean',
            'sales',
            'When enabled, customers with credit_limit = 0 cannot purchase on credit.'
        );

        return redirect()->route('settings.index')->with('success', 'System settings updated successfully.');
    }
}
