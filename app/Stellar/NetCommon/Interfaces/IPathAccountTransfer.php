<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Soroban\SorobanServer;

interface IPathAccountTransfer
{
    public static function transferPathAccountAssets(
        KeyPair $account_key_par_issuer,
        KeyPair $account_key_par_origin,
        KeyPair $account_key_par_destination,
        array $account_key_par_middlemans,
        array $asset_codes,
        array $change_trust_asset_limits,
        array $transfer_amount_middlemans,
        array $transfer_price_middlemans,
        float $transfer_amount,
        float $final_destination_amount,
        bool $final_destination_amount_exact,
        SorobanServer $server,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool;
}