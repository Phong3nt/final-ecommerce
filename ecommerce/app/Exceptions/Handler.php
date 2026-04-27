<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     * Handles common HTTP errors with friendly responses instead of debug pages.
     */
    public function render($request, Throwable $e)
    {
        // Expired / invalid email verification link → redirect to notice page with a message
        if ($e instanceof InvalidSignatureException) {
            if ($request->is('email/verify/*')) {
                return redirect()->route('verification.notice')
                    ->with('error', 'The verification link has expired or is invalid. Please request a new one.');
            }
        }

        // 404 Not Found — show clean error page even when APP_DEBUG=true
        if ($e instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found.'], 404);
            }
            return response()->view('errors.404', ['message' => 'Page not found.'], 404);
        }

        return parent::render($request, $e);
    }
}
