<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Transaction;

use PHPUnit\Framework\TestCase;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;

class TransactionUtilTest extends TestCase
{
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    private const CONTRACT_FILES = [
        "soroban_hello_world_contract.wasm",
        "soroban_auth_contract.wasm",
        "soroban_atomic_swap_contract.wasm",
        "soroban_events_contract.wasm",
        "soroban_token_contract.wasm"
    ];
    
    public function testPrepareAndSignFromSimulatedResponse()
    {
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
            
        foreach(self::CONTRACT_FILES as $contract_file){
            $contract_file_path = self::CONTRACT_PATH . $contract_file;
            
            $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
                $contract_file_path
            );
            
            $this
                ->assertNotNull(
                    $contract_operation
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
                
            $this
                ->assertNotNull(
                    $signed_transaction->getSorobanTransactionData()
                );
                
            $this
                ->assertNotNull(
                    $signed_transaction->getFee()
                );
                
            $this
                ->assertEquals(
                    $signed_transaction->getSorobanTransactionData(),
                    $simulated_transaction_response->transactionData 
                );
                
            $simulated_fee = (float)$contract_transaction->getFee() + (float)$simulated_transaction_response->minResourceFee;
            
            $signed_fee = (float)$signed_transaction->getFee();
            
            $this
                ->assertTrue(
                    $simulated_fee > $signed_fee
                );
        }
    }
}
