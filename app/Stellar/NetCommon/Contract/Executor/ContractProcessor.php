<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Executor;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Network;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Interfaces\IContractProcessor;

class ContractProcessor implements IContractProcessor
{
    /**
     * Executes one previously generated $contract_operation contract operation
     * for $account account, making the contract transactions on stellar network.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     */
    public static function processContractOperation(
        SorobanServer $server,
        KeyPair $account_key_par,
        AccountResponse $account,
        InvokeHostFunctionOperation $contract_operation,
        Network $stellar_net
    ): ?GetTransactionResponse
    {
        try{
            if(is_null($contract_operation)){
                return null;
            }
            
            $contract_transaction = ContractTransactionBuilder::createTransaction(
                $contract_operation,
                $account
            );
            
            if(is_null($contract_transaction)){
                return null;
            }
            
            $simulated_transaction_response = $server
                ->simulateTransaction(
                    $contract_transaction
                );
                
            if(is_null($simulated_transaction_response)){
                return null;
            }
                
            $signed_transaction = TransactionUtil::prepareAndSignFromSimulatedResponse(
                $contract_transaction,
                $simulated_transaction_response,
                $account_key_par,
                $stellar_net
            );
            
            if(is_null($signed_transaction)){
                return null;
            }
            
            $send_response = PollTransactionUtil::pollSendTransaction(
                $server,
                $signed_transaction
            );
            
            if(
                is_null($send_response)
                || $send_response->status === SendTransactionResponse::STATUS_ERROR
                || is_null($send_response->hash)
                || trim($send_response->hash) === ""
            ){
                return null;
            }
            
            return PollTransactionUtil::pollTransactionResponseStatus(
                $server,
                trim($send_response->hash)
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}