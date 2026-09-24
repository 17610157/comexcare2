<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Login2faToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function show(Request $request)
    {
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $blockedUntil = Session::get('2fa_blocked_until');
        $isBlocked = $blockedUntil && now()->lt($blockedUntil);

        return view('auth.two-factor', [
            'isBlocked' => $isBlocked,
            'blockedUntil' => $blockedUntil,
            'emailError' => Session::get('2fa_email_error'),
        ]);
    }

    public function verify(Request $request)
    {
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $blockedUntil = Session::get('2fa_blocked_until');
        if ($blockedUntil && now()->lt($blockedUntil)) {
            return back()->withErrors([
                'token' => 'Demasiados intentos. Intenta de nuevo a las ' . $blockedUntil->format('H:i'),
            ]);
        }

        $request->validate([
            'token' => 'required|string',
        ]);

        $userId = Session::get('2fa_user_id');
        $tokenId = Session::get('2fa_token_id');
        $attempts = (int) Session::get('2fa_attempts', 0);

        $tokenRecord = Login2faToken::where('id', $tokenId)
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->first();

        if (!$tokenRecord) {
            return back()->withErrors(['token' => 'Token no válido. Solicita uno nuevo.']);
        }

        if ($tokenRecord->expires_at->isPast()) {
            return back()->withErrors(['token' => 'Token expirado. Solicita uno nuevo.']);
        }

        $inputHash = hash('sha256', $request->token);

        if (!hash_equals($tokenRecord->token_hash, $inputHash)) {
            $attempts++;
            Session::put('2fa_attempts', $attempts);

            if ($attempts >= 5) {
                $blockedUntil = now()->addMinutes(15);
                Session::put('2fa_blocked_until', $blockedUntil);

                return back()->withErrors([
                    'token' => 'Demasiados intentos fallidos. Bloqueado hasta las ' . $blockedUntil->format('H:i'),
                ]);
            }

            return back()->withErrors([
                'token' => 'Token incorrecto. Intentos restantes: ' . (5 - $attempts),
            ]);
        }

        $tokenRecord->update(['used_at' => now()]);

        $user = \App\Models\User::findOrFail($userId);
        $rememberDevice = $request->boolean('remember_device');
        auth()->guard('web')->login($user, $rememberDevice);

        Session::forget([
            '2fa_user_id', '2fa_token_id', '2fa_attempts',
            '2fa_blocked_until', '2fa_last_resend_at',
            '2fa_email_error',
        ]);

        if ($request->boolean('remember_device')) {
            Cookie::queue('2fa_trusted', $user->id . '|' . $user->password, 60 * 24 * 30);
        }

        return redirect()->intended('/home');
    }

    public function resend(Request $request)
    {
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $lastResend = Session::get('2fa_last_resend_at');
        if ($lastResend && now()->diffInSeconds(now()->parse($lastResend)) < 30) {
            return back()->withErrors([
                'resend' => 'Espera 30 segundos antes de reenviar.',
            ]);
        }

        Login2faToken::where('id', Session::get('2fa_token_id'))->update(['used_at' => now()]);

        $user = \App\Models\User::findOrFail(Session::get('2fa_user_id'));

        $rawToken = Login2faToken::generate();
        $tokenRecord = Login2faToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(5),
        ]);

        Session::put([
            '2fa_token_id' => $tokenRecord->id,
            '2fa_last_resend_at' => now()->toIso8601String(),
            '2fa_attempts' => 0,
            '2fa_blocked_until' => null,
        ]);

        try {
            $user->notify(new \App\Notifications\Login2faNotification($rawToken));
            Session::forget('2fa_email_error');

            return back()->with('resend_success', 'Código reenviado a tu correo.');
        } catch (\Exception $e) {
            Session::put('2fa_email_error', true);

            return back()->withErrors([
                'resend' => 'No se pudo enviar el correo. Intenta de nuevo.',
            ]);
        }
    }
}
