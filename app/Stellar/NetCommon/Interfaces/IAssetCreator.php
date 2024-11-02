<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Asset;

interface IAssetCreator
{
    public static function createAsset(
        string $asset_code,
        string $issuer_account_id
    ): ?Asset;
}