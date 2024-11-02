<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;

interface ITrustLineBuilder
{
    public static function buildTrustLine(
        KeyPair $account_key_par_trustor,
        AccountResponse $account_trustor,
        AccountResponse $account_issuer,
        string $asset_code,
        float $trust_asset_limit,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool;
}