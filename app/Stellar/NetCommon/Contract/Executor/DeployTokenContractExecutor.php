<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Executor;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Executor\ContractProcessor;
use App\Stellar\NetCommon\Interfaces\IDeployTokenContractExecutor;

class DeployTokenContractExecutor implements IDeployTokenContractExecutor
{
    public const TOKEN_CONTRACT_TYPE = 18;
    
    /**
     * Executes a deploy token contract operation for $account account.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     */
    public static function deployTokenContract(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        Network $stellar_net
    ): ?GetTransactionResponse
    {
        try{
            $contract_operation = ContractOperationBuilder::buildDeployTokenContractFunction(
                $account_key_par
                    ->getAccountId()
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