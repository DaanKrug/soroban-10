<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\CreateAccountOperation;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\Asset;

interface IAccountOperationBuilder
{
    public static function buildAccountForParentAccountOperation(
        string $account_id,
        float $initial_amount
    ): ?CreateAccountOperation;
}