<?php

namespace App\Support;

trait ResponseJson
{
    /**
     * Success response.
     */
    protected function ok($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => null,
        ], $code);
    }

    /**
     * Resource created response.
     */
    protected function created($data, string $message = 'Created')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    /**
     * Failed response with errors.
     */
    protected function fail(string $message = 'Failed', array $errors = [], int $code = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => ! empty($errors) ? $errors : null,
            'meta' => null,
        ], $code);
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
            'meta' => null,
        ], 401);
    }

    /**
     * Forbidden response.
     */
    protected function forbidden(string $message = 'Forbidden')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
            'meta' => null,
        ], 403);
    }

    /**
     * Not found response.
     */
    protected function notFound(string $message = 'Not Found')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
            'meta' => null,
        ], 404);
    }

    /**
     * Server error response.
     */
    protected function serverError(string $message = 'Server Error')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
            'meta' => null,
        ], 500);
    }

    /**
     * Bad request response.
     */
    protected function badRequest(string $message = 'Bad Request')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
            'meta' => null,
        ], 400);
    }
}
