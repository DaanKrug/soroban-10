<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Account\Transfer;

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\Asset;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Account\Builder\AccountOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Asset\Creator\AssetCreator;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Interfaces\IAccountTransfer;

class AccountTransfer implements IAccountTransfer
{
    /**
     * Transfer $transfer_amount amount of NON native tokens (with code respective to $asset_code)
     * from origin account (respective to $account_key_par_origin)
     * to destination account (respective to $account_key_par_destination).
     * It needs a asset issuer account (respective to $account_key_par_issuer) that
     * is the responsible by this kind of asset,
     * and also a $change_trust_asset_limit maximum limit number of the asset that is
     * trusted to be transactioned (agreed between origin account and destination account).
     */
    public static function transferNonNativeAsset(
        KeyPair $account_key_par_issuer,
        KeyPair $account_key_par_origin,
        KeyPair $account_key_par_destination,
        string $asset_code,
        float $change_trust_asset_limit,
        float $transfer_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool
    {
        try{
            $asset = AssetCreator::createAsset(
                $asset_code,
                $account_key_par_issuer
                    ->getAccountId()
            );
            
            if(is_null($asset)){
                return null;
            }
            
            $change_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
                $asset,
                $change_trust_asset_limit
            );
            
            if(is_null($change_trust_operation)){
                return null;
            }
            
            $account_issuer = $stellar_sdk
                ->requestAccount(
                    $account_key_par_issuer
                        ->getAccountId()
                );
                
            if(is_null($account_issuer)){
                return null;
            }
            
            $account_origin = $stellar_sdk
                ->requestAccount(
                    $account_key_par_origin
                        ->getAccountId()
                );
                
            if(is_null($account_origin)){
                return null;
            }
                
            $account_destination = $stellar_sdk
                ->requestAccount(
                    $account_key_par_destination
                        ->getAccountId()
                );
                
            if(is_null($account_destination)){
                return null;
            }
                
            $trust_origin_result = TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_origin 
                ],
                $account_origin,
                $change_trust_operation,
                $stellar_net,
                $stellar_sdk
            );
            
            if(!$trust_origin_result){
                return null;
            }
            
            $trust_destination_result = TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_destination 
                ],
                $account_destination,
                $change_trust_operation,
                $stellar_net,
                $stellar_sdk
            );
            
            if(!$trust_destination_result){
                return null;
            }
            
            $issuer_to_origin_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
                $asset,
                $transfer_amount,
                $account_origin
                    ->getAccountId()
            );
            
            if(is_null($issuer_to_origin_payment_operation)){
                return null;
            }
            
            $issuer_transfer_to_origin_result = TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_issuer 
                ],
                $account_issuer,
                $issuer_to_origin_payment_operation,
                $stellar_net,
                $stellar_sdk
            );
            
            if(!$issuer_transfer_to_origin_result){
                return null;
            }
            
            $origin_to_destination_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
                $asset,
                $transfer_amount,
                $account_destination
                    ->getAccountId()
            );
            
            if(is_null($origin_to_destination_payment_operation)){
                return null;
            }
            
            return TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_origin 
                ],
                $account_origin,
                $origin_to_destination_payment_operation,
                $stellar_net,
                $stellar_sdk
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Transfer $transfer_amount amount of native tokens
     * from origin account (respective to $account_key_par_origin)
     * to destination account (respective to $account_id_destination).
     * It does not need a issuer.
     */
    public static function transferNativeAsset(
        KeyPair $account_key_par_origin,
        string $account_id_destination,
        float $transfer_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool
    {
        try{
            $payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
                Asset::native(),
                $transfer_amount,
                $account_id_destination,
                $account_key_par_origin
                    ->getAccountId()
            );
            
            if(is_null($payment_operation)){
                return null;
            }
            
            $account_origin = $stellar_sdk
                ->requestAccount(
                    $account_key_par_origin
                        ->getAccountId()
                );
                
            if(is_null($account_origin)){
                return null;
            }
            
            return TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_origin 
                ],
                $account_origin,
                $payment_operation,
                $stellar_net,
                $stellar_sdk
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
}