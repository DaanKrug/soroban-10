<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\Contracts;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Contract\Executor\DeploySACWithAssetHostFunctionExecutor;
use App\Stellar\NetCommon\Asset\Creator\AssetCreator;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;
use Tests\Unit\Stellar\TestNet\Horizon\HorizonUtilTest;

class DeploySACWithAssetHostFunctionContractTest extends TestCase
{
    public function testDeploySACWithAssetHostFunction()
    {
        $account_key_par_origin = AccountUtil::generateAccountKeyPar();
            
        $account_id_origin = $account_key_par_origin->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_origin              
            );
    
        $account_origin = AccountUtil::requestAccount($account_id_origin);
            
        $this
            ->assertNotNull(
                $account_origin
            );
            
        $account_key_par_destination = AccountUtil::generateAccountKeyPar();
            
        $account_id_destination = $account_key_par_destination->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_destination               
            );
    
        $account_destination = AccountUtil::requestAccount($account_id_destination);
            
        $this
            ->assertNotNull(
                $account_destination
            );
            
        $asset_code = "IOM";
        
        $change_trust_asset_amount = 200999;
        
        $payment_amount = 100;
        
        $asset = AssetCreator::createAsset(
            $asset_code,
            $account_id_origin
        );
         
        $change_trust_operation_to_destination = ContractOperationBuilder::buildChangeTrustContractOperation(
            $asset,
            $change_trust_asset_amount,
            $account_id_destination
        );
        
        $this
            ->assertNotNull(
                $change_trust_operation_to_destination
            );
        
        $payment_operation_from_origin = ContractOperationBuilder::buildPaymentContractOperation(
            $change_trust_operation_to_destination
                ->getAsset(),
            $payment_amount,
            $account_id_destination,
            $account_id_origin
        );
        
        $this
            ->assertNotNull(
                $payment_operation_from_origin
            );
        
        $asset_transaction = ContractTransactionBuilder::createAssetTransaction(
            $account_destination,
            $change_trust_operation_to_destination,
            $payment_operation_from_origin
        );
        
        $this
            ->assertNotNull(
                $asset_transaction
            );
            
        $signed_asset_transaction = TransactionUtil::signTransaction(
            $asset_transaction,
            [
                $account_key_par_origin,
                $account_key_par_destination
            ],
            SorobanServerSingletonFactory::getServerNetwork()
        );
        
        $this
            ->assertNotNull(
                $signed_asset_transaction
            );
            
        $response = TransactionUtil::submitTransaction(
            StellarSDK::getFutureNetInstance(),
            $signed_asset_transaction
        );
        
        $this
            ->assertTrue(
                $response
            );
            
        $contract_operation = ContractOperationBuilder::buildDeploySACWithAssetHostFunction(
            $change_trust_operation_to_destination
                ->getAsset()
        );
        
        $this
            ->assertNotNull(
                $contract_operation
            );
        
        $contract_transaction = ContractTransactionBuilder::createTransaction(
            $contract_operation,
            $account_destination
        );
        
        $this
            ->assertNotNull(
                $contract_transaction
            );
        
        $simulated_transaction_response = SorobanServerSingletonFactory::getServerInstance()
            ->simulateTransaction(
                $contract_transaction
            );
        
        $this
            ->assertSimulateTransactionResponse(
                $simulated_transaction_response
            );
            
        $this
            ->assertEquals(
                1,
                $simulated_transaction_response
                    ->results
                    ->count()
            );
        
        $this
            ->assertNotNull(
                $simulated_transaction_response
                    ->results
                    ->toArray()[0]
                    ->getResultValue()
            );
        
        $signed_transaction = TransactionUtil::prepareAndSignFromSimulatedResponse(
            $contract_transaction,
            $simulated_transaction_response,
            $account_key_par_destination,
            SorobanServerSingletonFactory::getServerNetwork()
        );
        
        $this
            ->assertNotNull(
                $signed_transaction
            );
            
        $transacion_envelope_xdr = $signed_transaction->toEnvelopeXdrBase64();
        
        $transaction2 = Transaction::fromEnvelopeBase64XdrString(
            $transacion_envelope_xdr
        );
        
        $this
            ->assertEquals(
                $transacion_envelope_xdr,
                $transaction2->toEnvelopeXdrBase64()
            );
        
        $send_response = PollTransactionUtil::pollSendTransaction(
            SorobanServerSingletonFactory::getServerInstance(),
            $signed_transaction
        );
        
        $this
            ->assertNotNull(
                $send_response
            );
            
        $this
            ->assertNotEquals(
                SendTransactionResponse::STATUS_ERROR,
                $send_response->status
            );
            
        $pool_status_response = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            $send_response->hash
        );
        
        $this
            ->assertNotNull(
                $pool_status_response
            );
        
        $this
            ->assertNotNull(
                $pool_status_response->getCreatedContractId()
            );
            
        $horizon_util_test = new HorizonUtilTest("__construct");
        
        $horizon_util_test
            ->assertHorizonOperationResponseHash(
                $send_response,
                $transacion_envelope_xdr,
                "HostFunctionTypeHostFunctionTypeCreateContract"
            );
            
        $account_key_par_origin2 = AccountUtil::generateAccountKeyPar();
            
        $account_id_origin2 = $account_key_par_origin2->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_origin2              
            );
    
        $account_origin2 = AccountUtil::requestAccount($account_id_origin2);
            
        $this
            ->assertNotNull(
                $account_origin2
            );
            
        $deploy_SAC_with_asset_host_function_executor_response = DeploySACWithAssetHostFunctionExecutor::deploySACWithAssetHostFunctionContract(
            SorobanServerSingletonFactory::getServerInstance(),
            $account_key_par_origin2,
            $account_key_par_destination,
            $account_origin2,
            $account_destination,
            $asset_code,
            $change_trust_asset_amount,
            $payment_amount,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $deploy_SAC_with_asset_host_function_executor_response
            );
            
        $this
            ->assertNotNull(
                $deploy_SAC_with_asset_host_function_executor_response
                    ->getCreatedContractId()
            );
         
        $this
            ->assertEquals(
                (int)DeploySACWithAssetHostFunctionExecutor::ASSET_CONTRACT_TYPE,
                (int)$deploy_SAC_with_asset_host_function_executor_response
                    ->getResultValue()
                    ->getType()
                    ->getValue()
            );
        
        $this
            ->assertEquals(
                (int)$pool_status_response
                    ->getResultValue()
                    ->getType()
                    ->getValue(),
                (int)$deploy_SAC_with_asset_host_function_executor_response
                    ->getResultValue()
                    ->getType()
                    ->getValue()
            );
    }
    
    private function assertSimulateTransactionResponse(object $simulated_transaction_response)
    {
        $this
            ->assertNull(
                $simulated_transaction_response->error
            );
        
        $this
            ->assertNull(
                $simulated_transaction_response->resultError
            );
        
        $this
            ->assertNotNull(
                $simulated_transaction_response->results
            );
        
        $this
            ->assertNotNull(
                $simulated_transaction_response->latestLedger
            );
        
        $this
            ->assertEquals(
                1,
                $simulated_transaction_response->results->count()
            );
        
        $this
            ->assertNotNull(
                $simulated_transaction_response->getTransactionData()
            );
        
        $this
            ->assertNotNull(
                $simulated_transaction_response->getFootprint()
            );
            
        $this
            ->assertGreaterThan(
                1,
                $simulated_transaction_response->cost->cpuInsns
            );
        
        $this
            ->assertGreaterThan(
                1,
                $simulated_transaction_response->cost->memBytes
            );
        
        $this
            ->assertGreaterThan(
                1,
                $simulated_transaction_response->minResourceFee
            );
    }
}
