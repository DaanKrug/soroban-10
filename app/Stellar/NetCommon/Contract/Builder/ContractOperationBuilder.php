<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Contract\Builder;

use Soneso\StellarSDK\UploadContractWasmHostFunction;
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\DeploySACWithSourceAccountHostFunction;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperation;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\PaymentOperation;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperation;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperation;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\NetCommon\Interfaces\IContractOperationBuilder;
use App\Stellar\NetCommon\Log\LoggerUtil;

class ContractOperationBuilder implements IContractOperationBuilder
{
    private const VALID_CONTRACT_FILE_EXTENSIONS = ['wasm'];
    
    private const MAX_RETRIES = 5;
    
    private const SLEEP_NANOSECONDS = 500000; //  0.5 second
    
    /**
     * Builds one PathPaymentStrictReceiveOperation operation, for
     * transact $maximum_transfer_amount maximum amount of $asset_origin asset
     * by a $desired_receive_amount desired amount of $asset_destination asset,
     * for $account_destination_id account destination.
     * - $stellar_sdk provides utilitary functions for stellar network (Testnet/Mainnet).
     */
    public static function buildPathPaymentStrictReceiveOperation(
        Asset $asset_origin,
        Asset $asset_destination,
        string $account_destination_id,
        float $maximum_transfer_amount,
        float $desired_receive_amount,
        StellarSDK $stellar_sdk
    ): ?PathPaymentStrictReceiveOperation
    {
        try{
            $strict_receive_paths_page = $stellar_sdk
                ->findStrictReceivePaths()
                ->forDestinationAsset(
                    $asset_destination                      
                )
                ->forDestinationAmount(
                    (string)$receive_amount
                )
                ->forSourceAccount(
                    $account_destination_id                   
                )
                ->execute();
                
            if(is_null($strict_receive_paths_page)){
                return null;
            }
            
            $path_array = $strict_receive_paths_page
                ->getPaths()
                ->toArray()[0]
                ->getPath()
                ->toArray();
              
            if(
                is_null($path_array)
                || count($path_array) === 0
            ){
                return null;
            }
            
            $builder = new PathPaymentStrictReceiveOperationBuilder(
                $asset_origin,
                (string)$maximum_transfer_amount,
                $account_destination_id,
                $asset_destination,
                (string)$desired_receive_amount
            );
            
            return $builder
                ->setPath(
                    $path_array
                )
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Builds one PathPaymentStrictSendOperation operation, for
     * transact $transfer_amount amount of $asset_origin asset
     * by a $minimum_receive_amount minimum amount of $asset_destination asset,
     * for $account_destination_id account destination.
     * - $stellar_sdk provides utilitary functions for stellar network (Testnet/Mainnet).
     */
    public static function buildPathPaymentStrictSendOperation(
        Asset $asset_origin,
        Asset $asset_destination,
        string $account_destination_id,
        float $transfer_amount,
        float $minimum_receive_amount,
        StellarSDK $stellar_sdk
    ): ?PathPaymentStrictSendOperation
    {
        try{
            $array_paths = self::readPathPaymentStrictSend(
                $asset_origin,
                $asset_destination,
                $transfer_amount,
                $stellar_sdk
            );
            
            if(
                is_null($array_paths)
                || count($array_paths) === 0
            ){
                return null;
            }
            
            $path_array = $array_paths[0]
                ->getPath()
                ->toArray();
                
            if(
                is_null($path_array)
                || count($path_array) === 0
            ){
                return null;
            }
            
            $builder = new PathPaymentStrictSendOperationBuilder(
                $asset_origin,
                (string)$transfer_amount,
                $account_destination_id,
                $asset_destination,
                (string)$minimum_receive_amount
            );
            
            return $builder
                ->setPath(
                    $path_array
                )
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Build a sell offer of an asset for another, selling $asset_selling_amount amount
     * of $asset_selling asset by the $asset_selling_price price,
     * to obtain $asset_buying asset.
     * - price 1: 1 $asset_selling unit buys 1 $asset_buying unit
     * - price 0.5: 1 $asset_selling unit buys 2 $asset_buying units
     */
    public static function buildManageSellOfferOperation(
        Asset $asset_selling,
        Asset $asset_buying,
        float $asset_selling_amount,
        float $asset_selling_price
    ): ?ManageSellOfferOperation
    {
        try{
            $builder = new ManageSellOfferOperationBuilder(
                $asset_selling,
                $asset_buying,
                (string)$asset_selling_amount,
                (string)$asset_selling_price
            );
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
            
    /**
     * - Creates a PaymentOperation object required to transactions
     * related to payment/transfer of assets between accounts.
     * One operation from $asset_amount amount of $asset asset type
     * will be transfered to another account.
     * - $source_account_id source account could be one issuer account,
     * or an trustor account or the origin account from transfer.
     * - Some operations define $source_account_id in diferent moment/operation
     * then $source_account_id could be null here.
     */
    public static function buildPaymentContractOperation(
        Asset $asset,
        float $asset_amount,
        string $destination_account_id,
        string $source_account_id = null
    ): ?PaymentOperation
    {
        try{
            $builder = new PaymentOperationBuilder(
                $destination_account_id,
                $asset,
                (string)$asset_amount
            );
            
            if(!is_null($source_account_id)){
                $builder
                    ->setSourceAccount(
                        $source_account_id
                    );
            }
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
           
    /**
     * - Creates a ChangeTrustOperation object required to trustline operations
     * related to payment/transfer of assets between accounts.
     * - $change_trust_asset_limit is the trustline maximum amount limit.
     * - $source_account_id source account could be one issuer account,
     * or an trustor account or the origin account from transfer.
     * - Some operations define $source_account_id in diferent moment/operation
     * then $source_account_id could be null here.
     */ 
    public static function buildChangeTrustContractOperation(
        Asset $asset,
        float $change_trust_asset_limit,
        string $source_account_id = null
    ): ?ChangeTrustOperation
    {
        try{
            $builder = new ChangeTrustOperationBuilder(
                $asset,
                (string)$change_trust_asset_limit
            );
            
            if(!is_null($source_account_id)){
                $builder
                    ->setSourceAccount(
                        $source_account_id
                    );
            }
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * SAC = Stellar Asset Contract
     * Creates an InvokeHostFunctionOperation operation
     * to be used to deploy a SAC contract for the $asset asset.
     */
    public static function buildDeploySACWithAssetHostFunction(
        Asset $asset
    ): ?InvokeHostFunctionOperation
    {
        try{
            $deploy_SAC_with_asset_host_function = new DeploySACWithAssetHostFunction(
                $asset
            );
            
            $builder = new InvokeHostFunctionOperationBuilder(
                $deploy_SAC_with_asset_host_function
            );
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * SAC = Stellar Asset Contract
     * Creates an InvokeHostFunctionOperation operation
     * to be used to deploy a SAC contract for the $address address source account
     * and an token that is defined in other moment/operation.
     */
    public static function buildDeployTokenContractFunction(
        string $account_id
    ): ?InvokeHostFunctionOperation
    {
        try{
            $address = Address::fromAccountId(
                $account_id
            );
            
            $deploy_SAC_with_source_account_host_function = new DeploySACWithSourceAccountHostFunction(
                $address
            );
            
            $builder = new InvokeHostFunctionOperationBuilder(
                $deploy_SAC_with_source_account_host_function
            );
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * - Builds one smart contract InvokeHostFunctionOperation
     * to $function_name function after the smart contract file
     * was already uploaded and processed a bit (respective to $contract_id).
     * - $arguments contains the argument values to be passed to smart contract function
     * on execution moment. The same contract function could be used undefined times with
     * diferent values to generate multiple results.
     */
    public static function buildContractFunction(
        string $contract_id,
        string $function_name,
        array $arguments
    ): ?InvokeHostFunctionOperation
    {
        try{
            $arguments_values = [];
            
            // TODO: improve to other argument types: string, int, float, double, etc
            foreach($arguments as $argument){
                $arguments_values[] = XdrSCVal::forSymbol($argument);
            }
            
            $invoke_contract_host_function = new InvokeContractHostFunction(
                $contract_id,
                $function_name,
                $arguments_values
            );
            
            $builder = new InvokeHostFunctionOperationBuilder(
                $invoke_contract_host_function
            );
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * - Builds a smart contract InvokeHostFunctionOperation for first time use,
     * after the contract uploaded (that generated the $contract_wasm_id).
     * The operation will be applied to $account_id account id.
     */
    public static function buildContract(
        string $account_id,
        string $contract_wasm_id
    ): ?InvokeHostFunctionOperation
    {
        try{
            $address = Address::fromAccountId($account_id);
            
            $create_contract_host_function = new CreateContractHostFunction(
                $address,
                $contract_wasm_id
            );
            
            $builder = new InvokeHostFunctionOperationBuilder(
                $create_contract_host_function
            );
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Upload a smart contract from web assembly file (.wasm)
     * and prepares one InvokeHostFunctionOperation
     * to be used at first build that will generate the "contract_wasm_id"
     * key on stellar network (and in this way the contract is registered on network).
     */
    public static function uploadAndBuildContract(
        string $contract_file_path                                              
    ): ?InvokeHostFunctionOperation
    {
        if(!self::validateContractFile($contract_file_path)){
            return null;
        }
        
        try{
            $contract_code = file_get_contents(
                $contract_file_path,
                false
            );
            
            if(
                $contract_code === false
                || trim($contract_code) === ""
            ){
                return null;
            }
            
            $upload_contract_host_function = new UploadContractWasmHostFunction(
                $contract_code
            );
            
            if(
                is_null($upload_contract_host_function)
            ){
                return null;
            }
            
            $builder = new InvokeHostFunctionOperationBuilder(
                $upload_contract_host_function                                                  
            );
        
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Gets the strict send paths for "buildPathPaymentStrictSendOperation" method.
     * Retries untill self::MAX_RETRIES times or fail.
     */
    private static function readPathPaymentStrictSend(
        Asset $asset_origin,
        Asset $asset_destination,
        float $transfer_amount,
        StellarSDK $stellar_sdk,
        int $retries = 0
    ): ?array
    {
        try{
            if($retries > self::MAX_RETRIES){
                return null;
            }
            
            $strict_send_paths_page = $stellar_sdk
                ->findStrictSendPaths()
                ->forSourceAsset(
                    $asset_origin
                )
                ->forSourceAmount(
                    (string)$transfer_amount
                )
                ->forDestinationAssets(
                    [
                        $asset_destination
                    ]
                )
                ->execute();
                
            if(is_null($strict_send_paths_page)){
                return null;
            }
            
            $array_paths = $strict_send_paths_page
                ->getPaths()
                ->toArray();
                
            if(
                !is_null($array_paths)
                && count($array_paths) > 0
            ){
                return $array_paths;
            }
            
            usleep(self::SLEEP_NANOSECONDS);
            
            return self::readPathPaymentStrictSend(
                $asset_origin,
                $asset_destination,
                $transfer_amount,
                $stellar_sdk,
                $retries++
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Validate the contract file path against basic mistakes.
     */
    private static function validateContractFile(
        string $contract_file_path
    ): bool
    {
        if(
            is_null($contract_file_path)
            || trim($contract_file_path) === ""
        ){
            return false;
        }
        
        $contract_file_path = trim($contract_file_path);
        
        if(
            !file_exists($contract_file_path)
            || !is_readable($contract_file_path)
        ){
            return false;
        }
        
        $extension_array = explode(
            ".",
            $contract_file_path                           
        );
        
        $extension = $extension_array[
            count($extension_array) - 1
        ];
        
        return in_array(
            $extension,
            self::VALID_CONTRACT_FILE_EXTENSIONS
        );   
    }
}