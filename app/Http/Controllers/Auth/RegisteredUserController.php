<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'role_type' => ['required', 'string', 'in:customer,seller,supplier'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'lastname' => $request->lastname,
            'username' => $request->username ?? str()->slug($request->name.'-'.uniqid()),
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_type' => $request->role_type,
            'status' => 'active',
        ]);

        // Assign the corresponding Spatie role
        $user->assignRole($request->role_type);

        // Notify active admins in-app about the new registration. This is
        // deliberately in-app only (no Gmail/SMTP), and must never fail the
        // registration itself.
        try {
            $this->notifyActiveAdmins($user);
        } catch (\Throwable $e) {
            Log::warning('Failed to create new-user admin notification', [
                'user_id' => $user->id,
                'exception' => $e,
            ]);
        }

        // The Registered event triggers the verification email synchronously
        // via SMTP. A mail outage or bad SMTP credentials must never fail
        // the registration itself (previously an unhandled 500 after the
        // user row was already created). Log it; the user can re-request
        // verification from the verify-email notice (resend route exists).
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::warning('Registration verification email failed to send', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e,
            ]);
        }

        Auth::login($user);

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect($user->getDashboardRoute());
    }

    /**
     * Create an in-app UserNotification for every active admin.
     */
    private function notifyActiveAdmins(User $user): void
    {
        $admins = User::query()
            ->where('role_type', 'admin')
            ->where('status', 'active')
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'role_type' => $user->role_type,
            'registered_at' => now()->toIso8601String(),
        ];

        foreach ($admins as $admin) {
            UserNotification::create([
                'id' => (string) Str::uuid(),
                'type' => 'new_user',
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => $payload,
                'title' => 'New user registered: '.$user->name,
                'icon' => 'user',
                'link' => route('admin.users.show', $user),
            ]);
        }
    }
}
