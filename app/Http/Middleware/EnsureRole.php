<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the request through only when the logged-in user has the given role.
 *
 * Usage in routes: `->middleware('role:admin')` or `->middleware('role:renter')`.
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  $role  Required role value (see UserRole).
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role === UserRole::from($role), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
