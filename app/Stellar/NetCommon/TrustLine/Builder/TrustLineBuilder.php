<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\TrustLine\Builder;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Asset\Creator\AssetCreator;
use App\Stellar\NetCommon\Interfaces\ITrustLineBuilder;
use App\Stellar\NetCommon\Log\LoggerUtil;

class TrustLineBuilder implements ITrustLineBuilder
{
    /**
     * Builds a trustline to a transfer asset operation,
     * with the asset issuer creating the $asset_code asset and
     * $account_trustor account trustor granting the $trust_asset_limit
     * trust limit for the operation.
     * - $account_key_par_trustor is the keypar respective to $account_trustor account.
     * - $stellar_net is used to define the network type: Mainnet or Testnet
     * - $stellar_sdk provides utilitary transaction functionalities
     * also related to respective network type: Mainnet or Testnet.
     */
    public static function buildTrustLine(
        KeyPair $account_key_par_trustor,
        AccountResponse $account_trustor,
        AccountResponse $account_issuer,
        string $asset_code,
        float $trust_asset_limit,
        Network $stellar_net,
        StellarSDK $stellar_sdk
    ): ?bool
    {
        try{
            $asset = AssetCreator::createAsset(
                $asset_code,
                $account_issuer
                    ->getAccountId()
            );
            
            if(is_null($asset)){
                return null;
            }
            
            $change_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
                $asset,
                $trust_asset_limit
            );
            
            if(is_null($change_trust_operation)){
                return null;
            }
            
            return TransactionUtil::createAndSendTransaction(
                [
                    $account_key_par_trustor 
                ],
                $account_trustor,
                $change_trust_operation,
                $stellar_net,
                $stellar_sdk
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}