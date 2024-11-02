<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Asset\Creator;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use App\Stellar\NetCommon\Interfaces\IAssetCreator;
use App\Stellar\NetCommon\Log\LoggerUtil;

class AssetCreator implements IAssetCreator
{
    /**
     * Creates an Asset representation from a $asset_code (Ex: "XRP", "BTC", "ASTROZ")
     * related with their respective issuer account ($issuer_account_id).
     * Returns an AssetTypeCreditAlphanum4 object for $asset_code shorter than 5 letters ("XRP", "BTC")
     * and an AssetTypeCreditAlphanum12 object for $asset_code bigger than 4 letters ("ASTROZ").
     */
    public static function createAsset(
        string $asset_code,
        string $issuer_account_id
    ): ?Asset
    {
        try{
            $asset_code = trim(
                $asset_code
            );
            
            $size = strlen(
                $asset_code
            );
            
            if($size < 5){
                return new AssetTypeCreditAlphanum4(
                    $asset_code,
                    $issuer_account_id
                );
            }
            
            return new AssetTypeCreditAlphanum12(
                $asset_code,
                $issuer_account_id
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}