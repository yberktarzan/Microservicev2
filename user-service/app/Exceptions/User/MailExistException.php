<?php

namespace App\Exceptions\User;

use App\Exceptions\BaseException;

class MailExistException extends BaseException
{
    /**
     * MailExistException constructor.
     */
    public function __construct(int $email)
    {
        parent::__construct(
            message: 'Email already exists',
            statusCode: 409,
            errorCode: 'EMAIL_ALREADY_EXISTS',
            errorData: ['email' => $email]
        );
    }
}
