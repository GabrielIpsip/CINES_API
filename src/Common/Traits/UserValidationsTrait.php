<?php

namespace App\Common\Traits;

use App\Common\Exceptions\UserValidationsException;
use App\Entity\Users;
use App\Entity\UserValidations;
use Symfony\Component\HttpFoundation\Response;

trait UserValidationsTrait
{

    /**
     * Return if user has user validation pending.
     * @param Users $user User User entity associated with the validation in pending.
     * @return UserValidations User validation entity associated with user.
     * @throws UserValidationsException 404 : Not validation associated with this user.
     */
    private function getUserValidationByUser(Users $user): UserValidations
    {
        $userValidation = $this->managerRegistry->getRepository(UserValidations::class)->findOneBy(array('user' => $user));
        if (!$userValidation)
        {
            throw new UserValidationsException('This user has not authentication pending.',
                Response::HTTP_NOT_FOUND);
        }
        return $userValidation;
    }
}