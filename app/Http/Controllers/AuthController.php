<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function create_user(Request $request) {
        $validateData = $request->validate([
            "name" => "string",
            "email" => "string",
            "password" => "string"
        ]);

        $user = User::create([
            "name" => $validateData['name'],
            "email" => $validateData["email"],
            "password" => Hash::make($validateData['password']),
            "password_sftp" => encrypt($this->generate_password())
        ]);

        return $user->id;
    }

    public function login(Request $request) {

    }

    public function getUser(Request $request) {
        $validateData = $request->validate([
            "email" => "string"
        ]);

        return User::where("email", $validateData['email'])->firstOrFail();
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
}
