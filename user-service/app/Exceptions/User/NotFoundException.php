<?php

namespace App\Exceptions\User;

use App\Exceptions\BaseException;

class NotFoundException extends BaseException
{
    /**
     * NotFoundException constructor.
     */
    public function __construct(string $userId)
    {
        parent::__construct(
            message: 'User not found',
            statusCode: 404,
            errorCode: 'USER_NOT_FOUND',
            errorData: ['user_id' => $userId]
        );
    }
}
