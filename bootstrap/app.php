<?php

use App\Http\Middleware\ApplicantAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'applicant.auth' => ApplicantAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // PHP drops the whole request body when it exceeds post_max_size, and this
        // is thrown before the session starts, so flash messages would be lost.
        // Send the applicant back to the page they came from with a query flag instead.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $previous = $request->headers->get('referer') ?: url('/');
            $previous = preg_replace('/[?&]upload_error=[^&]*/', '', $previous);

            return redirect()->to($previous.(str_contains($previous, '?') ? '&' : '?').'upload_error=too_large');
        });
    })->create();
