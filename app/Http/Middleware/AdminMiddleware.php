<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // check if user is logged in AND is an admin
        if (Auth::check() && Auth::user()->usertype === 'admin') {
            return $next($request); // let them through
        }

        // if NOT admin, redirect them away
        return redirect('/dashboard')->with('error', 'Access denied. Admin only.');
    }
}
