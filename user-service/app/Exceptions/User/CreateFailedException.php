<?php

namespace App\Exceptions\User;

use App\Exceptions\BaseException;

class CreateFailedException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            message: $message ?? 'Failed to create user',
            statusCode: 500,
            errorCode: 'USER_CREATE_FAILED'
        );
    }
}
