<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\DataTransferObjects\ApiError;
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
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions and return JSON responses
     */
    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json(
                (new ApiError('Validation failed', '422', $e->errors()))->toArray(),
                422
            );
        }

        if ($e instanceof AuthenticationException) {
            return response()->json(
                (new ApiError('Unauthenticated', '401', null))->toArray(),
                401
            );
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json(
                (new ApiError('Resource not found', '404', null))->toArray(),
                404
            );
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json(
                (new ApiError('Method not allowed', '405', null))->toArray(),
                405
            );
        }

        // Handle other HTTP exceptions
        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: 'An error occurred';
            
            return response()->json(
                (new ApiError($message, (string)$statusCode, null))->toArray(),
                $statusCode
            );
        }

        // Handle general exceptions
        $message = config('app.debug') ? $e->getMessage() : 'Internal server error';
        $statusCode = 500;

        return response()->json(
            (new ApiError($message, (string)$statusCode, null))->toArray(),
            $statusCode
        );
    }
}
