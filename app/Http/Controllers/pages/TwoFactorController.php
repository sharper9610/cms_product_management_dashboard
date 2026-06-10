<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = app('pragmarx.google2fa');
    }

    // Show QR code setup page
    public function setup()
    {
        $user   = Auth::user();
        $secret = $this->google2fa->generateSecretKey();

        session(['google2fa_secret_temp' => $secret]);

        $qrImage = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );

        $google2fa_enabled = $user->google2fa_enabled;

        return view('content.pages.2fa-setup', compact('secret', 'qrImage', 'google2fa_enabled'));
    }

    // Enable 2FA after user scans QR and confirms OTP
    public function enable(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $secret = session('google2fa_secret_temp');
        if (!$secret) {
            return back()->withErrors(['error' => 'Session expired. Please try again.']);
        }

        $valid  = $this->google2fa->verifyKey($secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Invalid OTP. Please try again.']);
        }

        $user = Auth::user();
        $user->google2fa_secret  = $secret;
        $user->google2fa_enabled = true;
        $user->save();

        session()->forget('google2fa_secret_temp');

        activity('user')->event('2fa_enabled')
            ->withProperties(['email' => $user->email, 'id' => $user->id])
            ->log("User enabled 2FA: {$user->email}");

        // User just setup 2FA for the first time, now needs to verify with OTP
        session(['2fa_user_id' => $user->id]);
        Auth::logout();

        return redirect()->route('2fa.challenge')
            ->with('success', '2FA setup successful! Please verify with your OTP.');
    }

    // Disable 2FA
    public function disable(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $user  = Auth::user();
        if (!$user->google2fa_enabled) {
            return back()->withErrors(['error' => '2FA is not enabled.']);
        }

        $valid = $this->google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Invalid OTP.']);
        }

        $user->google2fa_secret  = null;
        $user->google2fa_enabled = false;
        $user->save();

        activity('user')->event('2fa_disabled')
            ->withProperties([
                'email'    => $user->email,
                'datetime' => now()->toDateTimeString(),
                'ip'       => request()->ip(),
            ])
            ->log('2FA disabled by: ' . $user->email);

        return redirect('/users')->with('success', '2FA disabled.');
    }
}
