<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

interface IAccountTransfer
{
    public static function transferNonNativeAsset(
        KeyPair $account_key_par_issuer,
        KeyPair $account_key_par_origin,
        KeyPair $account_key_par_destination,
        string $asset_code,
        float $change_trust_asset_limit,
        float $transfer_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool;
    
    public static function transferNativeAsset(
        KeyPair $account_key_par_origin,
        string $account_id_destination,
        float $transfer_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool;
}