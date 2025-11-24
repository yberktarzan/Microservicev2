<?php

namespace App\Exceptions\User;

use App\Exceptions\BaseException;

class DeleteFailedException extends BaseException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'Failed to delete user',
            statusCode: 500,
            errorCode: 'USER_DELETE_FAILED',
            errorData: ['user_id' => $userId]
        );
    }
}
