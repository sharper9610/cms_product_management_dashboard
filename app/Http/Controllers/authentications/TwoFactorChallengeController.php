<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    public function show()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.authentications.auth-2fa-challenge', ['pageConfigs' => $pageConfigs]);
    }

    public function verify(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user     = User::findOrFail($userId);
        $google2fa = app('pragmarx.google2fa');
        $valid    = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Invalid OTP. Please try again.']);
        }

        Auth::login($user);
        session()->forget('2fa_user_id');

        activity('user')->event('login')
            ->withProperties(['email' => $user->email, 'ip' => $request->ip()])
            ->log("User logged in (2FA verified): {$user->email}");

        return redirect('/users')->with('success', 'Login success');
    }
}
