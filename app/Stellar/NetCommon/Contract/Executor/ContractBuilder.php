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
use App\Stellar\NetCommon\Interfaces\IContractBuilder;

class ContractBuilder implements IContractBuilder
{
    /**
     * Uses a previous uploaded contract mapped on stellar network by $contract_wasm_id
     * to create a smart contract to $account account.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     */
    public static function buildContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net,
        string $contract_wasm_id
    ): ?string
    {
        try{
            $contract_operation = ContractOperationBuilder::buildContract(
                $account
                    ->getAccountId(),
                $contract_wasm_id
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
                ->getCreatedContractId();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}