<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Login2faToken;
use App\Models\User;
use App\Notifications\Login2faNotification;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendFailedLoginResponse($request);
        }

        if (! $user->activo) {
            return back()->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ])->onlyInput('email');
        }

        if ($this->isTrustedDevice($request, $user)) {
            auth()->guard('web')->login($user, false);

            return redirect()->intended($this->redirectPath());
        }

        $rawToken = Login2faToken::generate();
        $tokenRecord = Login2faToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(5),
        ]);

        Session::put([
            '2fa_user_id' => $user->id,
            '2fa_token_id' => $tokenRecord->id,
            '2fa_attempts' => 0,
            '2fa_blocked_until' => null,
            '2fa_last_resend_at' => null,
        ]);

        try {
            $user->notify(new Login2faNotification($rawToken));
        } catch (\Exception $e) {
            Session::put('2fa_email_error', true);
        }

        return redirect()->route('2fa.show');
    }

    protected function isTrustedDevice(Request $request, User $user): bool
    {
        $cookie = $request->cookie('2fa_trusted');

        if (! $cookie) {
            return false;
        }

        $parts = explode('|', $cookie, 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$userId, $passwordHash] = $parts;

        return (int) $userId === $user->id && hash_equals($user->password, $passwordHash);
    }
}