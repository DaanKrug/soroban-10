<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;

interface ITransactionUtil
{
    public static function createAndSendMultiOperationTransaction(
        array $account_key_par_array,
        AccountResponse $account,
        array $contract_operations,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?SubmitTransactionResponse;
    
    public static function createAndSendTransaction(
        array $account_key_par_array,
        AccountResponse $account,
        AbstractOperation $contract_operation,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?SubmitTransactionResponse;
    
    public static function signTransaction(
        Transaction $transaction,
        array $account_key_par_array,
        Network $stellar_net
    ): ?Transaction;
    
    public static function submitTransaction(
        StellarSDK $stellar_sdk,
        Transaction $transaction
    ): ?SubmitTransactionResponse;
    
    public static function getResponseOperations(
        StellarSDK $stellar_sdk,
        string $send_response_hash,
        int $limit = 10,
        string $order = "desc"
    ): array;
    
    public static function prepareAndSignFromSimulatedResponse(
        Transaction $transaction,
        SimulateTransactionResponse $simulate_response,
        KeyPair $account_key_par,
        Network $stellar_net
    ): ?Transaction;
}