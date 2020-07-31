<?php

namespace App\Services\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class GroupDescription extends Exception
{
    public static function notFound()
    {
        throw new NotFoundHttpException('Unable to find Rows Group',);
    }
}