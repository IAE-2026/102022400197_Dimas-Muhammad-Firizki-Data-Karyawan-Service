<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLocalRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->attributes->get('federated_user')?->role?->name;

        if (! in_array($role, $roles, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role lokal tidak diizinkan melakukan transaksi ini.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
