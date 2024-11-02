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
use Tests\Unit\Stellar\TestNet\Horizon\HorizonUtilTest;

class SdkBasicExamplesContractTest extends TestCase
{
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    private const CONTRACT_FILES = [
        "soroban_hello_world_contract.wasm",
        "soroban_auth_contract.wasm",
        "soroban_atomic_swap_contract.wasm",
        "soroban_events_contract.wasm",
        "soroban_token_contract.wasm"
    ];
    
    public function testTransactionForContracts()
    {
        foreach(self::CONTRACT_FILES as $contract_file){
            $this
                ->assertContractTransaction(
                    self::CONTRACT_PATH . $contract_file
                );
        }
    }
    
    private function assertContractTransaction(
        string $contract_file_path
    )
    {
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
            
        $this
            ->assertContractTransactionSendResponse(
                $account_key_par,
                $account,
                $contract_operation,
                $send_response,
                $contract_file_path
            );
            
        $horizon_util_test = new HorizonUtilTest("__construct");
        
        $horizon_util_test
            ->assertHorizonOperationResponseHash(
                $send_response,
                $transacion_envelope_xdr,
                "HostFunctionTypeHostFunctionTypeUploadContractWasm"
            );
    }
    
    private function assertContractTransactionSendResponse(
        KeyPair $account_key_par,
        AccountResponse $account,
        InvokeHostFunctionOperation $contract_operation,
        SendTransactionResponse $send_response,
        string $contract_file_path
    )
    {
        $this
            ->assertNull(
                $send_response->error
            );
            
        $this
            ->assertNotNull(
                $send_response->hash
            );
            
        $this
            ->assertNotNull(
                $send_response->status
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
            
        $pool_status_response_by_contract_processor = ContractProcessor::processContractOperation(
            SorobanServerSingletonFactory::getServerInstance(),
            $account_key_par,
            $account,
            $contract_operation,
            SorobanServerSingletonFactory::getServerNetwork()
        );
            
        $contract_wasm_id = $pool_status_response
            ->getWasmId();
        
        $contract_wasm_id_by_contract_processor = $pool_status_response_by_contract_processor
            ->getWasmId();
        
        $this
            ->assertNotNull(
                $contract_wasm_id
            );
            
        $this
            ->assertNotNull(
                $contract_wasm_id_by_contract_processor
            );
            
        $this
            ->assertEquals(
                $contract_wasm_id,
                $contract_wasm_id_by_contract_processor
            );
            
        $contract_code_entry = SorobanServerSingletonFactory::getServerInstance()
            ->loadContractCodeForWasmId(
                $contract_wasm_id
            );
        
        $this
            ->assertNotNull(
                $contract_code_entry
            );
        
        $loaded_source_code = $contract_code_entry
            ->body
            ->code
            ->value;
            
        $contract_code = file_get_contents(
            $contract_file_path,
            false
        );
        
        $this
            ->assertEquals(
                $contract_code,
                $loaded_source_code
            );
        
        $this
            ->assertGreaterThan(
                1,
                $contract_code_entry->expirationLedgerSeq
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
