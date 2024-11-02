<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Executor;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Contract\Executor\ContractProcessor;
use App\Stellar\NetCommon\Interfaces\IContractUploader;

class ContractUploader implements IContractUploader
{
    /**
     * Executes the $contract_file_path contract upload operation, to upload a web assembly
     * contract file to a stellar network as a smartcontract relative to $account account.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     */
    public static function uploadContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net,
        string $contract_file_path
    ): ?string
    {
        try{
            $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
                $contract_file_path
            );
            
            $pool_status_response = ContractProcessor::processContractOperation(
                $server,
                $account_key_par,
                $account,
                $contract_operation,
                $stellar_net
            );
            
            if(is_null($pool_status_response)){
                return null;
            }
            
            return $pool_status_response
                ->getWasmId();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}