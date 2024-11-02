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
use Soneso\StellarSDK\Soroban\SorobanServer;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Account\Builder\AccountOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Asset\Creator\AssetCreator;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Interfaces\IPathAccountTransfer;

class PathAccountTransfer implements IPathAccountTransfer
{
    /**
     * Transfer X amount of Asset A, to destination account, that will receive Y amount of
     * asset B. For that should be used 1 or more intermediate accounts and assets
     * (called middleman - each middleman has itself asset, trustlimit, transfer amount and
     * transfer price [ratio between buy/sell assets operation]).
     * The transfer operations will be always executed in order of middlemans
     * (position on array) wich one with their respectives asset, trustlimit, transfer amount and
     * transfer price.
     * - $final_destination_amount_exact optimizes the $final_destination_amount
     * that destination account will receive.
     * When true: the $final_destination_amount will be the exact informed,
     * optimizing the $transfer_amount to minimum value required to obtain
     * the $final_destination_amount.
     * When false: will used all $transfer_amount to get at least $final_destination_amount
     * (or more), optimizing the final asset amount.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     * - $stellar_sdk provides utilitary transaction functionalities also related to
     * respective network type: Mainnet or Testnet.
     */
    public static function transferPathAccountAssets(
        KeyPair $account_key_par_issuer,
        KeyPair $account_key_par_origin,
        KeyPair $account_key_par_destination,
        array $account_key_par_middlemans,
        array $asset_codes,
        array $change_trust_asset_limits,
        array $transfer_amount_middlemans,
        array $transfer_price_middlemans,
        float $transfer_amount,
        float $final_destination_amount,
        bool $final_destination_amount_exact,
        SorobanServer $server,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool
    {
        try{
            if(
                (count($asset_codes) - 1)
                    !== count($account_key_par_middlemans)
                || count($change_trust_asset_limits)
                    !== count($asset_codes)
            ){
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
            
            $middleman_accounts = [];
            
            foreach($account_key_par_middlemans as $account_key_par_middleman){
                $middleman_account = $stellar_sdk
                    ->requestAccount(
                        $account_key_par_middleman
                            ->getAccountId()
                    );
                    
                if(is_null($middleman_account)){
                    return null;
                }
                
                $middleman_accounts[] = $middleman_account;
            }
            
            $assets = [];
            
            foreach($asset_codes as $asset_code){
                $asset = AssetCreator::createAsset(
                    $asset_code,
                    $account_key_par_issuer
                        ->getAccountId()
                );
                
                if(is_null($asset)){
                    return null;
                }
                
                $assets[] = $asset;
            }
            
            $origin_account_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
                $assets[0],
                $change_trust_asset_limits[0]
            );
            
            if(is_null($origin_account_trust_operation)){
                return null;
            }
            
            $origin_account_trust_transaction_result = TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_origin
                ],
                $account_origin,
                $origin_account_trust_operation,
                $stellar_net,
                $stellar_sdk
            );
            
            if(is_null($origin_account_trust_transaction_result)){
                return null;
            }
            
            $origin_account_trust_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
                $server,
                trim($origin_account_trust_transaction_result->getHash())
            );
        
            if(is_null($origin_account_trust_transaction_status)){
                return null;
            }
            
            $last_trust_operation = $origin_account_trust_operation;
            
            $count = 0;
            
            foreach($account_key_par_middlemans as $account_key_par_middleman){
                $middleman_account_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
                    $assets[$count + 1],
                    $change_trust_asset_limits[$count + 1]
                );
                
                if(is_null($middleman_account_trust_operation)){
                    return null;
                }
                
                $middleman_account_trust_transaction_result = TransactionUtil::createAndSendMultiOperationTransaction(
                    [
                        $account_key_par_middleman
                    ],
                    $middleman_accounts[$count],
                    [
                        $last_trust_operation,
                        $middleman_account_trust_operation
                    ],
                    $stellar_net,
                    $stellar_sdk
                );
                
                if(is_null($middleman_account_trust_transaction_result)){
                    return null;
                }
                
                $middleman_account_trust_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
                    $server,
                    trim($middleman_account_trust_transaction_result->getHash())
                );
            
                if(is_null($middleman_account_trust_transaction_status)){
                    return null;
                }
                
                $count ++;
                
                $last_trust_operation = $middleman_account_trust_operation;
            }
            
            $destination_account_trust_transaction_result = TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_destination
                ],
                $account_destination,
                $last_trust_operation,
                $stellar_net,
                $stellar_sdk
            );
            
            if(is_null($destination_account_trust_transaction_result)){
                return null;
            }
            
            $destination_account_trust_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
                $server,
                trim($destination_account_trust_transaction_result->getHash())
            );
        
            if(is_null($destination_account_trust_transaction_status)){
                return null;
            }
            
            $counter = 0;
            
            $payment_operations = [];
            
            $origin_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
                $assets[$counter],
                $transfer_amount,
                $account_key_par_origin
                    ->getAccountId()
            );
            
            if(is_null($origin_payment_operation)){
                return null;
            }
            
            $payment_operations[] = $origin_payment_operation;
            
            $counter ++;
            
            foreach($account_key_par_middlemans as $account_key_par_middleman){
                $middleman_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
                    $assets[$counter],
                    $transfer_amount,
                    $account_key_par_middleman
                        ->getAccountId()
                );
                
                if(is_null($middleman_payment_operation)){
                    return null;
                }
                
                $payment_operations[] = $middleman_payment_operation;
                
                $counter ++;
            }
            
            $payment_operations_transaction_result = TransactionUtil::createAndSendMultiOperationTransaction(
                [
                    $account_key_par_issuer
                ],
                $account_issuer,
                $payment_operations,
                $stellar_net,
                $stellar_sdk
            );
            
            if(is_null($payment_operations_transaction_result)){
                return null;
            }
            
            $payment_operations_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
                $server,
                trim($payment_operations_transaction_result->getHash())
            );
        
            if(is_null($payment_operations_transaction_status)){
                return null;
            }
            
            $assets_size = count($assets);
            
            for($i = 0; $i < $assets_size - 1; $i++){
                
                var_dump(
                json_encode([
                    $i,
                 $assets[$i + 1],
                    $assets[$i],
                    $transfer_amount_middlemans[$i],
                    $transfer_price_middlemans[$i]
                 ]    )     
                );
                
                $sell_offer_operation = ContractOperationBuilder::buildManageSellOfferOperation(
                    $assets[$i + 1],
                    $assets[$i],
                    $transfer_amount_middlemans[$i],
                    $transfer_price_middlemans[$i]
                );
                
                if(is_null($sell_offer_operation)){
                    return null;
                }
                
                $sell_offer_transaction_result = TransactionUtil::createAndSendTransaction(
                    [
                        $account_key_par_middlemans[$i]
                    ],
                    $middleman_accounts[$i],
                    $sell_offer_operation,
                    $stellar_net,
                    $stellar_sdk
                );
                
                if(is_null($sell_offer_transaction_result)){
                    return null;
                }
                
                $sell_offer_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
                    $server,
                    trim($sell_offer_transaction_result->getHash())
                );
            
                if(is_null($sell_offer_transaction_status)){
                    return null;
                }
            }
            
            $contract_path_payment_operation = null;
            
            if($final_destination_amount_exact){
                $contract_path_payment_operation = ContractOperationBuilder::buildPathPaymentStrictReceiveOperation(
                    $assets[0],
                    $assets[count($assets) - 1],
                    $account_key_par_destination
                        ->getAccountId(),
                    $transfer_amount,
                    $final_destination_amount,
                    $stellar_sdk
                );
            }
            
            if(!$final_destination_amount_exact){
                $contract_path_payment_operation = ContractOperationBuilder::buildPathPaymentStrictSendOperation(
                    $assets[0],
                    $assets[count($assets) - 1],
                    $account_key_par_destination
                        ->getAccountId(),
                    $transfer_amount,
                    $final_destination_amount,
                    $stellar_sdk
                );
            }
            
            if(is_null($contract_path_payment_operation)){
                LoggerUtil::info("PPP");
                return null;
            }
            
            $path_transfer_transaction_response = TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_origin
                ],
                $account_origin,
                $contract_path_payment_operation,
                $stellar_net,
                $stellar_sdk
            );
        
            if(is_null($path_transfer_transaction_response)){
                return null;
            }
            
            $path_transfer_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
                $server,
                trim($path_transfer_transaction_response->getHash())
            );
        
            if(is_null($path_transfer_transaction_status)){
                return null;
            }
            
            return true;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}
