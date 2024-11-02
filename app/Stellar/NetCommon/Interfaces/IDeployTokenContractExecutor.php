<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;

interface IDeployTokenContractExecutor
{
    public static function deployTokenContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net
    ): ?GetTransactionResponse;
}