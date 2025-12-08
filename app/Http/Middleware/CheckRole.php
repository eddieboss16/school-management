<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // check if user is logged in
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Check if user has the required role
        if (Auth::user()->usertype !== $role) {
            // Redirect based on their actual role
            return match(Auth::user()->usertype) {
                'admin' => redirect('/admin/dashboard')->with('error', 'Access denied.'),
                'teacher' => redirect('/teacher/dashboard')->with('error', 'Access denied.'),
                'student' => redirect('/dashboard')->with('error', 'Access denied.'),
                default => redirect('/login'),
            };
        }
        return $next($request);
    }
}
