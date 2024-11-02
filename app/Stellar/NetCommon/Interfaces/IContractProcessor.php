<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Network;

interface IContractProcessor
{
    public static function processContractOperation(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        InvokeHostFunctionOperation $contract_operation,
        Network $stellar_net
    ): ?GetTransactionResponse;
}