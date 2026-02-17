<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        return back()
            ->withErrors(['username' => 'Invalid credentials.'])
            ->onlyInput('username');
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', [
            'invite' => $request->query('invite', ''),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'invite' => ['required', 'string', 'size:32'],
            'username' => ['required', 'string', 'max:100', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $invite = Invite::where('code', $data['invite'])
            ->whereNull('used_at')
            ->first();

        if (!$invite) {
            return back()
                ->withErrors(['invite' => 'Invalid or already used invite code.'])
                ->withInput();
        }

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $invite->used_at = now();
        $invite->used_by = $user->id;
        $invite->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
