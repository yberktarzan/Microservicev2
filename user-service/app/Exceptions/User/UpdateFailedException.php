<?php

namespace App\Exceptions\User;

use App\Exceptions\BaseException;

class UpdateFailedException extends BaseException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'Failed to update user',
            statusCode: 500,
            errorCode: 'USER_UPDATE_FAILED',
            errorData: ['user_id' => $userId]
        );
    }
}
