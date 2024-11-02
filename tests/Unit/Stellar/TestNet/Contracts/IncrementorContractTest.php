<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\Contracts;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;
use App\Stellar\NetCommon\Contract\Executor\UploadContractExecutor;

class IncrementorContractTest extends TestCase
{
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    public function testIncrementorContract()
    {
        $contract_file_path = self:: CONTRACT_PATH . "incrementor.wasm";
        
        $contract_code = file_get_contents($contract_file_path, false);
        
        $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
            $contract_file_path
        );
        
        $this
            ->assertNotNull(
                $contract_operation
            );
            
        $account_key_par = AccountUtil::generateAccountKeyPar();
            
        $account_id = $account_key_par->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id                
            );
    
        $account = AccountUtil::requestAccount($account_id);
            
        $this
            ->assertNotNull(
                $account
            );
            
        $contract_transaction = ContractTransactionBuilder::createTransaction(
            $contract_operation,
            $account
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
            
        $signed_transaction = TransactionUtil::prepareAndSignFromSimulatedResponse(
            $contract_transaction,
            $simulated_transaction_response,
            $account_key_par,
            SorobanServerSingletonFactory::getServerNetwork()
        );
        
        $this
            ->assertNotNull(
                $signed_transaction
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
            
        $contract_wasm_id = $pool_status_response->getWasmId();
        
        $contract_operation = ContractOperationBuilder::buildContract(
            $account_id,
            $contract_wasm_id
        );
        
        $contract_transaction = ContractTransactionBuilder::createTransaction(
            $contract_operation,
            $account
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
            ->assertNotNull(
                $simulated_transaction_response->getSorobanAuth()
            );
            
        $signed_transaction = TransactionUtil::prepareAndSignFromSimulatedResponse(
            $contract_transaction,
            $simulated_transaction_response,
            $account_key_par,
            SorobanServerSingletonFactory::getServerNetwork()
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
                $send_response
                    ->status
            );
            
        $pool_status_response = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            $send_response
                ->hash
        );
            
        $this
            ->assertNotNull(
                $pool_status_response
            );
         
        $created_contract_id = $pool_status_response
            ->getCreatedContractId();
        
        $this
            ->assertNotNull(
                $created_contract_id
            );
            
        $contract_function = ContractOperationBuilder::buildContractFunction(
            $created_contract_id,
            "increment",
            []
        );
        
        $contract_transaction = ContractTransactionBuilder::createTransaction(
            $contract_function,
            $account
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

        $signed_transaction = TransactionUtil::prepareAndSignFromSimulatedResponse(
            $contract_transaction,
            $simulated_transaction_response,
            $account_key_par,
            SorobanServerSingletonFactory::getServerNetwork()
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
        
        $hello_contract_result_by_executor = UploadContractExecutor::uploadAndExecuteContract(
            SorobanServerSingletonFactory::getServerInstance(),
            $account_key_par,
            $account,
            SorobanServerSingletonFactory::getServerNetwork(),
            $contract_file_path,
            "increment",
            []
        );
        
        $this
            ->assertEquals(
                $pool_status_response
                    ->getResultValue()
                    ->getU32(),
                1
            );
            
        $this
            ->assertEquals(
                $pool_status_response
                    ->getResultValue()
                    ->getU32(),
                $hello_contract_result_by_executor
                    ->getResultValue()
                    ->getU32()
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
