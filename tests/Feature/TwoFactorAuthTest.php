<?php

use App\Models\Login2faToken;
use App\Models\User;
use App\Notifications\Login2faNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Transport\AbstractTransport;

final class FailingMailTransport extends AbstractTransport
{
    protected function doSend(\Symfony\Component\Mailer\SentMessage $message): void
    {
        throw new \RuntimeException('SMTP connection refused');
    }

    public function __toString(): string
    {
        return 'failing://';
    }
}

if (! function_exists('createTrustedCookie')) {
    function createTrustedCookie(User $user): string
    {
        return $user->id . '|' . $user->password;
    }

    function createTrustedCookieFor(User $user, string $passwordHash): string
    {
        return $user->id . '|' . $passwordHash;
    }
}

function loginAndCaptureToken($test, $credentials): string
{
    $test->post(route('login'), $credentials);

    $notification = Notification::sent($test->user, Login2faNotification::class)->first();
    expect($notification)->not->toBeNull();

    return $notification->token;
}

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('secret123'),
        'activo' => true,
    ]);
    $this->credentials = ['email' => $this->user->email, 'password' => 'secret123'];
});

it('redirects to 2fa page after valid credentials', function () {
    Notification::fake();

    $this->post(route('login'), $this->credentials)
        ->assertRedirect(route('2fa.show'));

    $this->assertGuest();
    $this->assertDatabaseHas('login_2fa_tokens', ['user_id' => $this->user->id]);
    Notification::assertSentTo($this->user, Login2faNotification::class);
});

it('does not log user in before token verification', function () {
    Notification::fake();

    $this->post(route('login'), $this->credentials)->assertRedirect(route('2fa.show'));

    $this->get('/home')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('rejects invalid credentials', function () {
    $this->post(route('login'), ['email' => $this->user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseEmpty('login_2fa_tokens');
});

it('rejects inactive user even with valid credentials', function () {
    $this->user->update(['activo' => 0]);

    $this->post(route('login'), $this->credentials)
        ->assertSessionHasErrors('email')
        ->assertSessionHas('errors');

    $this->assertGuest();
    $this->assertDatabaseEmpty('login_2fa_tokens');
});

it('logs in user after successful token verification', function () {
    Notification::fake();

    $token = loginAndCaptureToken($this, $this->credentials);

    $this->post(route('2fa.verify'), ['token' => $token, 'remember_device' => false])
        ->assertRedirect('/home');

    $this->assertAuthenticatedAs($this->user);

    $record = Login2faToken::where('user_id', $this->user->id)->first();
    expect($record->used_at)->not->toBeNull();
});

it('clears 2fa session after successful login', function () {
    Notification::fake();

    $token = loginAndCaptureToken($this, $this->credentials);
    $this->post(route('2fa.verify'), ['token' => $token]);

    expect(session()->has('2fa_user_id'))->toBeFalse();
    expect(session()->has('2fa_token_id'))->toBeFalse();
});

it('rejects invalid token and increments attempts', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    $this->post(route('2fa.verify'), ['token' => 'wrong-token'])
        ->assertSessionHasErrors('token');

    expect((int) session('2fa_attempts'))->toBe(1);
    $this->assertGuest();
});

it('tracks multiple failed attempts', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    for ($i = 1; $i <= 4; $i++) {
        $this->post(route('2fa.verify'), ['token' => "wrong-$i"])
            ->assertSessionHasErrors('token');

        expect((int) session('2fa_attempts'))->toBe($i);
    }

    $this->assertGuest();
});

it('blocks after five failed attempts', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('2fa.verify'), ['token' => "wrong-$i"]);
    }

    $this->post(route('2fa.verify'), ['token' => 'wrong-final'])
        ->assertSessionHasErrors('token');

    expect(session('2fa_blocked_until'))->not->toBeNull();
    expect(now()->lessThan(session('2fa_blocked_until')))->toBeTrue();
});

it('rejects expired token', function () {
    Notification::fake();

    $token = loginAndCaptureToken($this, $this->credentials);

    Login2faToken::query()->update(['expires_at' => now()->subMinute()]);

    $this->post(route('2fa.verify'), ['token' => $token])
        ->assertSessionHasErrors('token');

    $this->assertGuest();
});

it('prevents reuse of used token', function () {
    $token = Login2faToken::generate();
    Login2faToken::create([
        'user_id' => $this->user->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addMinutes(5),
    ]);
    $tokenId = Login2faToken::where('user_id', $this->user->id)->first()->id;

    $this->session([
        '2fa_user_id' => $this->user->id,
        '2fa_token_id' => $tokenId,
        '2fa_attempts' => 0,
    ]);

    $this->post(route('2fa.verify'), ['token' => $token])->assertRedirect('/home');
    $this->assertAuthenticatedAs($this->user);

    auth('web')->logout();
    $this->session([
        '2fa_user_id' => $this->user->id,
        '2fa_token_id' => $tokenId,
        '2fa_attempts' => 0,
    ]);

    $this->post(route('2fa.verify'), ['token' => $token])
        ->assertSessionHasErrors('token');
});

it('requires 2fa session to access verify endpoint', function () {
    $this->post(route('2fa.verify'), ['token' => 'x'])
        ->assertRedirect(route('login'));
});

it('requires 2fa session to access 2fa show page', function () {
    $this->get(route('2fa.show'))
        ->assertRedirect(route('login'));
});

it('skips 2fa when device is trusted', function () {
    $trustedCookie = createTrustedCookie($this->user);

    $this->withCookie('2fa_trusted', $trustedCookie)
        ->post(route('login'), $this->credentials)
        ->assertRedirect('/home');

    $this->assertAuthenticatedAs($this->user);
    $this->assertDatabaseEmpty('login_2fa_tokens');
});

it('does not trust device with stale password hash', function () {
    $encrypted = createTrustedCookieFor($this->user, Hash::make('old-password'));

    $this->withCookie('2fa_trusted', $encrypted)
        ->post(route('login'), $this->credentials)
        ->assertRedirect(route('2fa.show'));

    $this->assertGuest();
    $this->assertDatabaseHas('login_2fa_tokens', ['user_id' => $this->user->id]);
});

it('does not trust device for non-existent user id', function () {
    $encrypted = '99999|' . $this->user->password;

    $this->withCookie('2fa_trusted', $encrypted)
        ->post(route('login'), $this->credentials)
        ->assertRedirect(route('2fa.show'));

    $this->assertGuest();
});

it('sets trusted device cookie when requested', function () {
    Notification::fake();

    $token = loginAndCaptureToken($this, $this->credentials);

    $response = $this->post(route('2fa.verify'), [
        'token' => $token,
        'remember_device' => true,
    ]);

    $response->assertRedirect('/home');
    $response->assertCookie('2fa_trusted');
});

it('does not set trusted device cookie when not requested', function () {
    Notification::fake();

    $token = loginAndCaptureToken($this, $this->credentials);

    $response = $this->post(route('2fa.verify'), [
        'token' => $token,
        'remember_device' => false,
    ]);

    $response->assertRedirect('/home');
    $response->assertCookieMissing('2fa_trusted');
});

it('resends token and invalidates previous', function () {
    Notification::fake();

    $oldToken = loginAndCaptureToken($this, $this->credentials);
    $oldHash = hash('sha256', $oldToken);

    $this->post(route('2fa.resend'))
        ->assertSessionHas('resend_success');

    expect(Login2faToken::where('token_hash', $oldHash)->first()->used_at)->not->toBeNull();
    expect(Login2faToken::where('user_id', $this->user->id)->whereNull('used_at')->count())->toBe(1);

    Notification::assertSentTo($this->user, Login2faNotification::class, 2);
});

it('throttles resend to once per 30 seconds', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    $this->post(route('2fa.resend'))->assertSessionHas('resend_success');
    $this->post(route('2fa.resend'))->assertSessionHasErrors('resend');
});

it('sets email error flag when sending fails', function () {
    Mail::extend('failing', fn () => new FailingMailTransport());
    config(['mail.default' => 'failing']);
    config(['mail.mailers.failing' => ['transport' => 'failing']]);

    $this->post(route('login'), $this->credentials)
        ->assertRedirect(route('2fa.show'));

    $this->get(route('2fa.show'))
        ->assertSuccessful()
        ->assertSee('NO SE PUDO ENVIAR');
});

it('displays resend link when not blocked', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    $this->get(route('2fa.show'))
        ->assertSuccessful()
        ->assertSee('REENVIAR');
});

it('hides resend link when blocked', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('2fa.verify'), ['token' => "wrong-$i"]);
    }

    $this->get(route('2fa.show'))
        ->assertSuccessful()
        ->assertDontSee('REENVIAR');
});

it('shows blocked message when rate limited', function () {
    Notification::fake();

    loginAndCaptureToken($this, $this->credentials);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('2fa.verify'), ['token' => "wrong-$i"]);
    }

    $this->get(route('2fa.show'))
        ->assertSee('DEMASIADOS INTENTOS');
});

it('does not leak user existence via 2fa page', function () {
    $this->get(route('2fa.show'))
        ->assertRedirect(route('login'));

    $this->post(route('2fa.verify'), ['token' => 'x'])
        ->assertRedirect(route('login'));
});

it('generates secure token hash', function () {
    $token = Login2faToken::generate();
    $hash = hash('sha256', $token);

    expect(strlen($token))->toBe(40);
    expect(strlen($hash))->toBe(64);
    expect(hash_equals($hash, hash('sha256', $token)))->toBeTrue();
});
