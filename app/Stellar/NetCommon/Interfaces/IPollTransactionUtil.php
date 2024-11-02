<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Transaction;

interface IPollTransactionUtil
{
    public static function pollSendTransaction(
        SorobanServer $server,
        Transaction $signed_transaction
    ): ?SendTransactionResponse;
    
    public static function pollRequestTransaction(
        StellarSDK $stellar_net,
        string $send_response_hash
    ): ?TransactionResponse;
    
    public static function pollTransactionResponseStatus(
        SorobanServer $server,
        string $transaction_id
    ): ?GetTransactionResponse;
}