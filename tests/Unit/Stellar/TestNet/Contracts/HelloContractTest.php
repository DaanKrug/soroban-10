<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\Contracts;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\InvokeHostFunctionOperation;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;
use App\Stellar\NetCommon\Contract\Executor\ContractProcessor;
use App\Stellar\NetCommon\Contract\Executor\UploadContractExecutor;
use Tests\Unit\Stellar\TestNet\Horizon\HorizonUtilTest;

class HelloContractTest extends TestCase
{ 
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    public function testHelloContract()
    {
        $contract_file_path = self:: CONTRACT_PATH . "soroban_hello_world_contract.wasm";
        
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
            
        $contract_code_entry = SorobanServerSingletonFactory::getServerInstance()
            ->loadContractCodeForContractId(
                $created_contract_id
            );
        
        $this
            ->assertNotNull(
                $contract_code_entry
            );
        
        $loaded_source_code = $contract_code_entry
            ->body
            ->code
            ->value;
          
        $this
            ->assertEquals(
                $contract_code,
                $loaded_source_code
            );
            
        $this
            ->assertGreaterThan(
                1,
                $contract_code_entry
                    ->expirationLedgerSeq
            );
            
        $horizon_util_test = new HorizonUtilTest("__construct");
        
        $horizon_util_test
            ->assertHorizonOperationResponseHash(
                $send_response,
                $transacion_envelope_xdr,
                "HostFunctionTypeHostFunctionTypeCreateContract"
            );
        
        $this
            ->assertLedgerEntry(
                $simulated_transaction_response
            );
            
        $contract_function = ContractOperationBuilder::buildContractFunction(
            $created_contract_id,
            "hello",
            ["friend"]
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
            ->assertContractFunctionResult(
                $pool_status_response
            );
            
        $horizon_util_test
            ->assertHorizonOperationResponseHash(
                $send_response,
                $transacion_envelope_xdr,
                "HostFunctionTypeHostFunctionTypeInvokeContract"
            );
            
        $hello_contract_result_by_executor = UploadContractExecutor::uploadAndExecuteContract(
            SorobanServerSingletonFactory::getServerInstance(),
            $account_key_par,
            $account,
            SorobanServerSingletonFactory::getServerNetwork(),
            $contract_file_path,
            "hello",
            ["friend"]
        );
        
        $this
            ->assertEquals(
                $pool_status_response
                    ->getResultValue()
                    ->getVec(),
                $hello_contract_result_by_executor
                    ->getResultValue()
                    ->getVec()
            );
    }
    
    private function assertLedgerEntry(
        $simulated_transaction_response
    )
    {
        $footprint = $simulated_transaction_response
            ->getFootprint();
        
        $contract_code_key = $footprint
            ->getContractCodeLedgerKey();
        
        $this
            ->assertNotNull(
                $contract_code_key
            );
        
        $contract_data_key = $footprint
            ->getContractDataLedgerKey();
        
        $this
            ->assertNotNull(
                $contract_data_key
            );

        $contract_code_entry_response = SorobanServerSingletonFactory::getServerInstance()
            ->getLedgerEntry(
                $contract_code_key
            );
            
        $this
            ->assertNotNull(
                $contract_code_entry_response->ledgerEntryData
            );
            
        $this
            ->assertNotNull(
                $contract_code_entry_response->lastModifiedLedgerSeq
            );
            
        $this
            ->assertNotNull(
                $contract_code_entry_response->latestLedger
            );
            
        $this
            ->assertNotNull(
                $contract_code_entry_response->getLedgerEntryDataXdr()
            );

        $contract_data_entry_response = SorobanServerSingletonFactory::getServerInstance()
            ->getLedgerEntry(
                $contract_data_key
            );
        
        $this
            ->assertNotNull(
                $contract_data_entry_response->ledgerEntryData
            );
            
        $this
            ->assertNotNull(
                $contract_data_entry_response->lastModifiedLedgerSeq
            );
            
        $this
            ->assertNotNull(
                $contract_data_entry_response->latestLedger
            );
            
        $this
            ->assertNotNull(
                $contract_data_entry_response->getLedgerEntryDataXdr()
            );
    }
    
    private function assertSimulateTransactionResponse(
        object $simulated_transaction_response
    )
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
    
    private function assertContractFunctionResult(object $status_response)
    {
        $result_value = $status_response->getResultValue();
        
        $this
            ->assertNotNull(
                $result_value
            );
        
        $res_vec = $result_value->vec;
        
        $this
            ->assertNotNull(
                $res_vec
            );
        
        foreach ($res_vec as $sym_val) {
            print($sym_val->sym . PHP_EOL);
        }

        // user friendly
        $result_value = $status_response->getResultValue();
        
        $vec = $result_value?->getVec();
        
        if ($vec != null && count($vec) > 1) {
            print("[" . $vec[0]->sym . ", " . $vec[1]->sym . "]" . PHP_EOL);
        }
    }
}
