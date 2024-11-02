<?php

declare(strict_types=1);

namespace App\Stellar\TestNet\Interfaces;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;

interface IAccountUtil
{
    public static function generateAccountKeyPar(): ?KeyPair;
    
    public static function requestAccount($account_id): ?AccountResponse;
}