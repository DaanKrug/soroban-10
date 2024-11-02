<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Contract\Builder;

use PHPUnit\Framework\TestCase;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;

class ContractOperationBuilderTest extends TestCase
{
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    private const CONTRACT_FILES = [
        "soroban_hello_world_contract.wasm",
        "soroban_auth_contract.wasm",
        "soroban_atomic_swap_contract.wasm",
        "soroban_events_contract.wasm",
        "soroban_token_contract.wasm"
    ];
    
    public function testUploadAndBuildContract()
    {
        $contract_file_path = "";
        
        $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
            $contract_file_path
        );
        
        $this
            ->assertNull(
                $contract_operation
            );
            
        $contract_file_path = self::CONTRACT_PATH . "invalid_path";
        
        $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
            $contract_file_path
        );
        
        $this
            ->assertNull(
                $contract_operation
            );
            
        $contract_file_path = self::CONTRACT_PATH . "invalid_file.pdf";
        
        $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
            $contract_file_path
        );
        
        $this
            ->assertNull(
                $contract_operation
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
        }
    }
}