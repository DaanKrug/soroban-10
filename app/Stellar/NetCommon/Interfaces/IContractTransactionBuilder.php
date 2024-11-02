<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\PaymentOperation;

interface IContractTransactionBuilder
{
    public static function createAssetTransaction(
        AccountResponse $destination_account,
        ChangeTrustOperation $change_trust_operation_to_destination_account,
        PaymentOperation $payment_operation_from_source_account
    ): ?Transaction;
    
    public static function createMultiOperationTransaction(
        array $contract_operations,
        AccountResponse $account
    ): ?Transaction;
    
    public static function createTransaction(
        AbstractOperation $contract_operation,
        AccountResponse $account
    ): ?Transaction;
}