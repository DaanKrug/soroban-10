<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Contract\Builder;

use PHPUnit\Framework\TestCase;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;

class ContractTransactionBuilderTest extends TestCase
{
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    private const CONTRACT_FILES = [
        "soroban_hello_world_contract.wasm",
        "soroban_auth_contract.wasm",
        "soroban_atomic_swap_contract.wasm",
        "soroban_events_contract.wasm",
        "soroban_token_contract.wasm"
    ];
    
    public function testCreateTransaction()
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
        }
    }
}