<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Services\PolicyPdfManager;

class AuthController extends Controller
{
  public function scraber()
  {
      $url = 'https://www.uhcprovider.com/content/dam/provider/docs/public/policies/comm-medical-drug/medical-policy-update-bulletin-june-2025.pdf';

      activity('download')->event('manual_download')
          ->withProperties([
              'url'           => $url,
              'file'          => basename($url),
              'downloaded_by' => auth()->user()->email ?? 'guest',
              'datetime'      => now()->toDateTimeString(),
              'ip'            => request()->ip(),
          ])
          ->log('Manual PDF download by: ' . (auth()->user()->email ?? 'guest'));

      echo PolicyPdfManager::downloadPdf($url);
  }

  public function registerPost(Request $request)
  {
//    $user = new User();
//    $user->name = $request->name;
//    $user->email = $request->email;
//    $user->password = Hash::make($request->password);
//    $user->save();
    return back()->with('success', 'Register successfully');
  }
  public function login()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs]);
  }

  public function loginPost(Request $request)
  {
     $credetials = [
      'email' => $request->email,
      'password' => $request->password,
    ];
    if (Auth::attempt($credetials)) {
      $user = Auth::user();

      // All users must enable 2FA
      if (!$user->google2fa_enabled) {
          // User needs to setup 2FA first - keep them logged in
          return redirect()->route('2fa.setup')
              ->with('info', 'Please setup Two-Factor Authentication to continue.');
      }

      // User has 2FA enabled, verify OTP
      session(['2fa_user_id' => $user->id]);
      Auth::logout();
      return redirect()->route('2fa.challenge');
    }
    return back()->with('error', 'Invalid email or password. Please try again.');
  }
  public function logout()
  {


    if ($user = Auth::user()) {
      activity('user')
        ->event('logout')
        ->withProperties([
          'email' => $user->email,
          'ip' => request()->ip(), // get IP without injecting Request
        ])
        ->log("User logged out: {$user->email}");
    }

    Auth::logout();
    return redirect()->route('login');
  }

}
