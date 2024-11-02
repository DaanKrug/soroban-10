<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Executor;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Contract\Executor\ContractProcessor;
use App\Stellar\NetCommon\Asset\Creator\AssetCreator;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Interfaces\IDeploySACWithAssetHostFunctionExecutor;

class DeploySACWithAssetHostFunctionExecutor implements IDeploySACWithAssetHostFunctionExecutor
{
    public const ASSET_CONTRACT_TYPE = 18;
    
    /**
     * SAC = Stellar Asset Contract
     * Executes a deploy from a SAC contract operation to stellar network.
     * This contract will transfer an $asset_code asset type $payment_amount amount
     * from $account_origin origin account to $account_destination destination account.
     * - $account_key_par_origin is the keypar respective to $account_origin origin account.
     * - $account_key_par_destination is the keypar respective
     * to $account_destination destination account.
     * - $server and $stellar_net are used to define the network type: Mainnet or Testnet.
     * - $stellar_sdk provides utilitary transaction functionalities
     * also related to respective network type: Mainnet or Testnet.
     */
    public static function deploySACWithAssetHostFunctionContract(
        SorobanServer $server,
        KeyPair $account_key_par_origin,
        KeyPair $account_key_par_destination,
        AccountResponse $account_origin,
        AccountResponse $account_destination,
        string $asset_code,
        float $change_trust_asset_amount,
        float $payment_amount,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?GetTransactionResponse
    {
        try{
            $asset = AssetCreator::createAsset(
                $asset_code,
                $account_key_par_origin
                    ->getAccountId()
            );
            
            if(is_null($asset)){
                return null;
            }
            
            $change_trust_operation_to_destination = ContractOperationBuilder::buildChangeTrustContractOperation(
                $asset,
                $change_trust_asset_amount,
                $account_key_par_destination
                    ->getAccountId()
            );
            
            if(is_null($change_trust_operation_to_destination)){
                return null;
            }
            
            $payment_operation_from_origin = ContractOperationBuilder::buildPaymentContractOperation(
                $change_trust_operation_to_destination
                    ->getAsset(),
                $payment_amount,
                $account_key_par_destination
                    ->getAccountId(),
                $account_key_par_origin
                    ->getAccountId()
            );
            
            if(is_null($payment_operation_from_origin)){
                return null;
            }
            
            $asset_transaction = ContractTransactionBuilder::createAssetTransaction(
                $account_destination,
                $change_trust_operation_to_destination,
                $payment_operation_from_origin
            );
            
            if(is_null($asset_transaction)){
                return null;
            }
            
            $signed_asset_transaction = TransactionUtil::signTransaction(
                $asset_transaction,
                [
                    $account_key_par_origin,
                    $account_key_par_destination
                ],
                $stellar_net
            );
            
            if(is_null($signed_asset_transaction)){
                return null;
            }
            
            $submitted = TransactionUtil::submitTransaction(
                $stellar_sdk,
                $signed_asset_transaction
            );
            
            if(!$submitted){
                return null;
            }
            
            $contract_operation = ContractOperationBuilder::buildDeploySACWithAssetHostFunction(
                $change_trust_operation_to_destination
                    ->getAsset()
            );
            
            return ContractProcessor::processContractOperation(
                $server,
                $account_key_par_destination,
                $account_destination,
                $contract_operation,
                $stellar_net
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}