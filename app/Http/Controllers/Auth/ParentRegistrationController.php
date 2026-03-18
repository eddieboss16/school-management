<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class ParentRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.parent-register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'admission_number' => ['required', 'string', 'exists:users,admission_number'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $student = User::where('admission_number', $request->admission_number)->first();

        $parent = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'usertype' => 'parent',
        ]);

        $student->parent_id = $parent->id;
        $student->save();

        event(new Registered($parent));

        Auth::login($parent);

        return redirect()->route('parent.dashboard');
    }
}
