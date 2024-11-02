<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Builder;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\AbstractOperation;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\PaymentOperation;
use App\Stellar\NetCommon\Interfaces\IContractTransactionBuilder;
use App\Stellar\NetCommon\Log\LoggerUtil;

class ContractTransactionBuilder implements IContractTransactionBuilder
{
    /**
     * Creates a Transaction respective to a asset transfer operation
     * with $destination_account destination account.
     * - $change_trust_operation_to_destination_account is
     * the trustline operation from destination account.
     * - $payment_operation_from_source_account is the payment operation
     * originated from origin/source/issuer account.
     */
    public static function createAssetTransaction(
        AccountResponse $destination_account,
        ChangeTrustOperation $change_trust_operation_to_destination_account,
        PaymentOperation $payment_operation_from_source_account
    ): ?Transaction
    {
        try{
            $transaction_builder = new TransactionBuilder(
                $destination_account
            );
            
            if(is_null($transaction_builder)){
                return null;
            }
            
            return $transaction_builder
                ->addOperation(
                    $change_trust_operation_to_destination_account
                )
                ->addOperation(
                    $payment_operation_from_source_account
                )
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Creates a Transaction respective to
     * an $contract_operation array (of contract operations)
     * and an $account account.
     */
    public static function createMultiOperationTransaction(
        array $contract_operations,
        AccountResponse $account
    ): ?Transaction
    {
        try{
            $transaction_builder = new TransactionBuilder(
                $account
            );
            
            if(is_null($transaction_builder)){
                return null;
            }
            
            foreach($contract_operations as $contract_operation){
                $transaction_builder
                    ->addOperation(
                        $contract_operation
                    );
            }
            
            return $transaction_builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
            
    /**
     * Creates a Transaction respective to
     * a $contract_operation contract operation
     * and an $account account.
     */
    public static function createTransaction(
        AbstractOperation $contract_operation,
        AccountResponse $account
    ): ?Transaction
    {
        try{
            return self::createMultiOperationTransaction(
                [
                    $contract_operation 
                ],
                $account
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}