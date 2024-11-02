<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\ManageSellOfferOperation;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperation;
use Soneso\StellarSDK\StellarSDK;

interface IContractOperationBuilder
{
    public static function buildPathPaymentStrictReceiveOperation(
        Asset $asset_origin,
        Asset $asset_destination,
        string $account_destination_id,
        float $transfer_amount,
        float $desired_receive_amount,
        StellarSDK $stellar_sdk
    ): ?PathPaymentStrictReceiveOperation;
    
    public static function buildPathPaymentStrictSendOperation(
        Asset $asset_origin,
        Asset $asset_destination,
        string $account_destination_id,
        float $transfer_amount,
        float $minimum_receive_amount,
        StellarSDK $stellar_sdk
    ): ?PathPaymentStrictSendOperation;
    
    public static function buildManageSellOfferOperation(
        Asset $asset_selling,
        Asset $asset_buying,
        float $asset_selling_amount,
        float $asset_selling_price
    ): ?ManageSellOfferOperation;
    
    public static function buildPaymentContractOperation(
        Asset $asset,
        float $asset_amount,
        string $destination_account_id,
        string $source_account_id = null
    ): ?PaymentOperation;
    
    public static function buildChangeTrustContractOperation(
        Asset $asset,
        float $change_trust_asset_limit,
        string $source_account_id = null
    ): ?ChangeTrustOperation;
    
    public static function buildDeploySACWithAssetHostFunction(
        Asset $asset
    ): ?InvokeHostFunctionOperation;
    
    public static function buildDeployTokenContractFunction(
        string $account_id
    ): ?InvokeHostFunctionOperation;
    
    public static function buildContractFunction(
        string $contract_id,
        string $function_name,
        array $arguments
    ): ?InvokeHostFunctionOperation;
    
    public static function buildContract(
        string $account_id,
        string $contract_wasm_id
    ): ?InvokeHostFunctionOperation;
    
    public static function uploadAndBuildContract(
        string $contract_file_path                                              
    ): ?InvokeHostFunctionOperation;
}