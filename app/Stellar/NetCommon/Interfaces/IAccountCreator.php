<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

interface IAccountCreator
{
    public static function createAccountFromParentAccount(
        AccountResponse $parent_account,
        KeyPair $parent_account_key_par,
        float $initial_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?AccountResponse;
}