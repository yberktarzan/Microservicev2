<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BaseException extends Exception
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Error code for client
     *
     * @var string|null
     */
    protected $errorCode;

    /**
     * Additional error data
     *
     * @var array|null
     */
    protected $errorData;

    /**
     * BaseException constructor.
     */
    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 500,
        ?string $errorCode = null,
        ?array $errorData = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the error data
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if ($this->errorCode) {
            $response['error_code'] = $this->errorCode;
        }

        if ($this->errorData) {
            $response['errors'] = $this->errorData;
        }

        // Development ortamında stack trace ekle
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($this),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTrace(),
            ];
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Report the exception.
     */
    public function report(): ?bool
    {
        // Log the exception
        logger()->error($this->getMessage(), [
            'exception' => get_class($this),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'status_code' => $this->statusCode,
            'error_code' => $this->errorCode,
            'error_data' => $this->errorData,
        ]);

        return true;
    }
}
