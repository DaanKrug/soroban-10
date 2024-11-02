<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Transaction;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Interfaces\ITransactionUtil;
use App\Stellar\NetCommon\Log\LoggerUtil;

class TransactionUtil implements ITransactionUtil
{
    /**
     * Creates, sign and submit one transaction against the stellar network,
     * respective to $contract_operation array (of contract operations) on $account account.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet
     * - $stellar_sdk provides utilitary transaction functionalities
     * also related to respective network type: Mainnet or Testnet.
     * - $account_key_par_array contain all $account_key_par that will sign the transaction
     * (at least one or more $account_key_par).
     */
    public static function createAndSendMultiOperationTransaction(
        array $account_key_par_array,
        AccountResponse $account,
        array $contract_operations,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?SubmitTransactionResponse
    {
        try{
            $contract_transaction = ContractTransactionBuilder::createMultiOperationTransaction(
                $contract_operations,
                $account
            );
            
            if(is_null($contract_transaction)){
                return null;
            }
            
            $signed_transaction = self::signTransaction(
                $contract_transaction,
                $account_key_par_array,
                $stellar_net
            );
            
            if(is_null($signed_transaction)){
                return null;
            }
            
            return self::submitTransaction(
                $stellar_sdk,
                $signed_transaction
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Creates, sign and submit one transaction against the stellar network,
     * respective to $contract_operation contract operation on $account account.
     * - $account_key_par is the keypar respective to $account account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet
     * - $stellar_sdk provides utilitary transaction functionalities
     * also related to respective network type: Mainnet or Testnet.
     * - $account_key_par_array contain all $account_key_par that will sign the transaction
     * (at least one or more $account_key_par).
     */
    public static function createAndSendTransaction(
        array $account_key_par_array,
        AccountResponse $account,
        AbstractOperation $contract_operation,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?SubmitTransactionResponse
    {
        try{
            $contract_transaction = ContractTransactionBuilder::createTransaction(
                $contract_operation,
                $account
            );
            
            if(is_null($contract_transaction)){
                return null;
            }
            
            $signed_transaction = self::signTransaction(
                $contract_transaction,
                $account_key_par_array,
                $stellar_net
            );
            
            if(is_null($signed_transaction)){
                return null;
            }
            
            return self::submitTransaction(
                $stellar_sdk,
                $signed_transaction
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Sign a $transaction transaction on stellar network.
     * - $stellar_net is used to define the network type: Mainnet or Testnet.
     * - $account_key_par_array contain all $account_key_par that will sign the transaction
     * (at least one or more $account_key_par).
     */
    public static function signTransaction(
        Transaction $transaction,
        array $account_key_par_array,
        Network $stellar_net
    ): ?Transaction
    {
        try{
            foreach($account_key_par_array as $account_key_par){
                $transaction
                    ->sign(
                        $account_key_par,
                        $stellar_net
                    );
            }
                
            return $transaction;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Submit one $transaction signed transaction to stellar network.
     * - $stellar_sdk provides utilitary transaction functionalities
     * related to respective network type: Mainnet or Testnet.
     */
    public static function submitTransaction(
        StellarSDK $stellar_sdk,
        Transaction $transaction
    ): ?SubmitTransactionResponse
    {
        try{
            $response = $stellar_sdk
                ->submitTransaction(
                    $transaction
                );
                
            if(!$response->isSuccessful()){
                return null;
            }
            
            return $response;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * After one transaction be successfully executed against stellar network
     * a $send_response_hash hash is returned. Based on this hash a collection/array
     * of the executed operation can be obtained.
     * - $stellar_sdk provides utilitary transaction functionalities
     * related to respective network type: Mainnet or Testnet.
     */
    public static function getResponseOperations(
        StellarSDK $stellar_sdk,
        string $send_response_hash,
        int $limit = 10,
        string $order = "desc"
    ): array
    {
        try{
            return $stellar_sdk
                ->operations()
                ->forTransaction(
                    $send_response_hash
                )
                ->limit(
                    $limit
                )
                ->order(
                    $order
                )
                ->execute()
                ->getOperations()
                ->toArray();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return [];
        }
    }
    
    /**
     * Uses a simulated transaction response to set data and fees to $transaction transaction.
     * Also uses $account_key_par to sign the transaction.
     * - $account_key_par is the keypar respective to the respective account.
     * - $stellar_net is used to define the network type: Mainnet or Testnet
     */
    public static function prepareAndSignFromSimulatedResponse(
        Transaction $transaction,
        SimulateTransactionResponse $simulate_response,
        KeyPair $account_key_par,
        Network $stellar_net
    ): ?Transaction
    {
        try{
            $transaction
                ->setSorobanTransactionData(
                    $simulate_response->transactionData                            
                );
                
            $transaction
                ->addResourceFee(
                    $simulate_response->minResourceFee
                );
                
            if(!is_null($simulate_response->getSorobanAuth())){
                $transaction
                    ->setSorobanAuth(
                        $simulate_response->getSorobanAuth()
                    );
            }
            
            return self::signTransaction(
                $transaction,
                [
                    $account_key_par
                ],
                $stellar_net
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}