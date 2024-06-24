<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

use App\Application\Events\ScriptFailed;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    // Any translation of exceptions generic accross endpoints should go here

    /**
     * @param $request
     * @param \Throwable $e
     *
     * @return JsonResponse
     */
    public function render($request, \Throwable $e): JsonResponse
    {
        // TODO: Add any other translation for exceptions to common http responses here.

        // Local Debugging
        if (config('app.debug') == true && config('app.env') != 'local') {
            // Dump and die any requests to the web routes for exceptions
            foreach ($request->route()->getAction()['middleware'] as $middleware) {
                if ($middleware == 'web') {
                    dd($e);
                }
            }
        }

        // 500 Exceptions
        // This is the default for any other exceptions we catch
        ScriptFailed::dispatch($e);

        return new JsonResponse(
            [
                '_metadata' => [
                    'success' => false,
                ],
                'result' => [
                    'message' => 'An error was encountered while attempting to process the request. Please contact the System Administrator.',
                ],
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
