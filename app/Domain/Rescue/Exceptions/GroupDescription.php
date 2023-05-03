<?php

namespace App\Domain\Rescue\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GroupDescription extends \Exception
{
    public static function notFound()
    {
        throw new NotFoundHttpException('Unable to find Rows Group');
    }
}
