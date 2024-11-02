<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Executor;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Contract\Executor\ContractProcessor;
use App\Stellar\NetCommon\Interfaces\IContractExecutor;

class ContractExecutor implements IContractExecutor
{
    /**
     * Creates a contract operation for created contract ($created_contract_id)
     * that executes the $function_name function for $account account,
     * using the parameter values contained on $symbol_array array.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     */
    public static function executeContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net,
        string $created_contract_id,
        string $function_name,
        array $symbol_array
    ): ?GetTransactionResponse
    {
        try{
            $contract_operation = ContractOperationBuilder::buildContractFunction(
                $created_contract_id,
                $function_name,
                $symbol_array
            );
            
            return ContractProcessor::processContractOperation(
                $server,
                $account_key_par,
                $account,
                $contract_operation,
                $stellar_net
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}