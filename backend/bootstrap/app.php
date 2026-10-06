<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint tidak ditemukan.',
            ], 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
            ], 403);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Metode HTTP tidak diizinkan untuk endpoint ini.',
            ], 405);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi beberapa saat lagi.',
            ], 429);
        });
    })->create();
