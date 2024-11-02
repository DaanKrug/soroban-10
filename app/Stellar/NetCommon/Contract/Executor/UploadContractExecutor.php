<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Executor;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Contract\Executor\ContractUploader;
use App\Stellar\NetCommon\Contract\Executor\ContractBuilder;
use App\Stellar\NetCommon\Contract\Executor\ContractExecutor;
use App\Stellar\NetCommon\Interfaces\IUploadContractExecutor;

class UploadContractExecutor implements IUploadContractExecutor
{
    /**
     * Executes a complete steps sequence to execute a smart contract contained
     * on $contract_file_path web assembly file against the $account account.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     */
    public static function uploadAndExecuteContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net,
        string $contract_file_path,
        string $function_name,
        array $symbol_array
    ): ?GetTransactionResponse
    {
        try{
            $contract_wasm_id = ContractUploader::uploadContract(
                $server,
                $account_key_par,
                $account,
                $stellar_net,
                $contract_file_path
            );
            
            if(is_null($contract_wasm_id)){
                return null;
            }
            
            $created_contract_id = ContractBuilder::buildContract(
                $server,
                $account_key_par,
                $account,
                $stellar_net,
                $contract_wasm_id
            );
            
            if(is_null($created_contract_id)){
                return null;
            }
            
            return ContractExecutor::executeContract(
                $server,
                $account_key_par,
                $account,
                $stellar_net,
                $created_contract_id,
                $function_name,
                $symbol_array
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}