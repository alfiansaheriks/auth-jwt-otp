<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class ProfileController extends Controller
{
    public function addPin(Request $request)
    {
        $phone_number = $request->get('phone_number');
        $pin = $request->get('pin');

        $user = User::where('phone_number', $phone_number)->firstOrFail();
        $user->pin = $pin;
        $user->save();

        return response()->json(['message' => 'PIN added successfully'], 200);
    }

    public function addNameandEmail(Request $request)
    {
        $phone_number = $request->get('phone_number');
        $name = $request->get('name');
        $email = $request->get('email');

        $user = User::where('phone_number', $phone_number)->firstOrFail();
        $user->name = $name;
        $user->email = $email;
        $user->save();

        return response()->json(['message' => 'Name and email added successfully'], 200);
    }
}
