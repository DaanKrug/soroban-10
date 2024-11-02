<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

interface ILoggerUtil
{
    public static function logThrowable(\Throwable $t): void;
    
    public static function info(string $message): void;
}