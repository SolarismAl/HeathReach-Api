<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebAuthMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!session('user') || !session('firebase_token')) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $user = session('user');

        // Check if user has required role
        if (!empty($roles) && !in_array($user['role'], $roles)) {
            return redirect()->back()->with('error', 'Access denied. Insufficient permissions.');
        }

        return $next($request);
    }
}
