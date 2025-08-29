<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validateData = $request->validate([
            "name" => "string",
            "email" => "string",
            "password" => "string"
        ]);

        $user = User::create([
            "name" => $validateData['name'],
            "email" => $validateData["email"],
            "password" => Hash::make($validateData['password']),
            "username_sftp" => strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $validateData['name'])),
            "password_sftp" => encrypt($this->generate_password())
        ]);

        return $user->id;
    }

    public function getUser(Request $request) {
        $validateData = $request->validate([
            "email" => "required|string|email"
        ]);

        try {
            $user = User::where('email', $validateData['email'])->firstOrFail();
            return response()->json(['id' => $user->id]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Utilisateur non trouvé pour cet email.',
            ], 404);
        }
    }

    private function generate_password($length = 12) {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-={}[]<>?';

        $all = $lowercase . $uppercase . $numbers . $symbols;

        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Mélange les caractères pour plus de sécurité
        return str_shuffle($password);
    }

    public function viewLogin(Request $request) {
        return Inertia::render("auth/login", [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function login(Request $request) {
        $request->validate([
            "email" => "required|string",
            "password" => "required|string"
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user) {
            return back()->withErrors([
                'email' => 'Utilisateur non trouvé',
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Mot de passe incorrect',
            ]);
        }

       if ($user && Auth::attempt([
                'email' => $user->email,
                'password' => $request->password
            ], $request->boolean('remember'))) {
            
            $request->session()->regenerate();

            // ID de l'utilisateur connecté
            $userId = Auth::id(); // ou $user->id
            return redirect()->to("/");
        } else {
            return back()->withErrors(['email' => 'Identifiants invalides']);
        }
    }
}
