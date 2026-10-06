<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
            ], 401);
        }

        if (! empty($roles)) {
            $allowed = false;
            foreach ($roles as $role) {
                if ($user->role === $role) {
                    $allowed = true;
                    break;
                }
                // Allow both 'staff' and 'karyawan' interchangeably
                if (($role === 'staff' && $user->role === 'karyawan') || ($role === 'karyawan' && $user->role === 'staff')) {
                    $allowed = true;
                    break;
                }
            }

            if (! $allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
                ], 403);
            }
        }

        return $next($request);
    }
}
