<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Account\Creator;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Account\Builder\AccountOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Interfaces\IAccountCreator;

class AccountCreator implements IAccountCreator
{
    /**
     * Creates a new account from a existing account $parent_account,
     * with the initial amount $initial_amount from native tokens.
     * The $initial_amount amount will be transfered from $parent_account
     * funds/wallet to the new account funds/wallet.
     * If the $initial_amount is not disponible on $parent_account
     * the operation will fail.
     */
    public static function createAccountFromParentAccount(
        AccountResponse $parent_account,
        KeyPair $parent_account_key_par,
        float $initial_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?AccountResponse
    {
        try{
            $new_account_key_par = KeyPair::random();
            
            $create_account_operation = AccountOperationBuilder::buildAccountForParentAccountOperation(
                $new_account_key_par
                    ->getAccountId(),
                $initial_amount
            );
            
            if(is_null($create_account_operation)){
                return null;
            }
            
            $transaction_success = TransactionUtil::createAndSendTransaction(
                [
                    $parent_account_key_par 
                ],
                $parent_account,
                $create_account_operation,
                $stellar_net,
                $stellar_sdk
            );
            
            if(!$transaction_success){
                return null;
            }
            
            return $stellar_sdk
                ->requestAccount(
                    $new_account_key_par
                        ->getAccountId()
                );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
}