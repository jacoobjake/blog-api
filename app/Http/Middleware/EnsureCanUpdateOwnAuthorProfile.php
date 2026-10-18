<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanUpdateOwnAuthorProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $profile = $user?->authorProfile;

        if ($profile === null || ! $user->can('update', $profile)) {
            abort(403);
        }

        return $next($request);
    }
}
