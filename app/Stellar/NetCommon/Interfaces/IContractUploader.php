<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Interfaces;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

interface IContractUploader
{
    public static function uploadContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net,
        string $contract_file_path
    ): ?string;
}