<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;

interface IDeploySACWithAssetHostFunctionExecutor
{
    public static function deploySACWithAssetHostFunctionContract(
        SorobanServer $server,
        KeyPair $account_key_par_origin,
        KeyPair $account_key_par_destination,
        AccountResponse $account_origin,
        AccountResponse $account_destination,
        string $asset_code,
        float $change_trust_asset_amount,
        float $payment_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?GetTransactionResponse;
}