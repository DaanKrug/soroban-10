<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\Horizon;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Operations\InvokeHostFunctionOperationResponse;
use Soneso\StellarSDK\Xdr\XdrTransactionMeta;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Network;
use App\Stellar\TestNet\Horizon\HorizonUtil;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;

class HorizonUtilTest extends TestCase
{
    private const CONTRACT_PATH =  "./tests/wasm/";
    
    private const CONTRACT_FILES = [
        "soroban_hello_world_contract.wasm",
        "soroban_auth_contract.wasm",
        "soroban_atomic_swap_contract.wasm",
        "soroban_events_contract.wasm",
        "soroban_token_contract.wasm"
    ];
    
    public function testHorizonOperationResponseHash()
    {
        $contract_file_path = self:: CONTRACT_PATH . self::CONTRACT_FILES[0];
        
        $contract_operation = ContractOperationBuilder::uploadAndBuildContract(
            $contract_file_path
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
    
        $transacion_envelope_xdr = $signed_transaction->toEnvelopeXdrBase64();
            
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
            ->assertNull(
                $send_response->error
            );
            
        $this
            ->assertNotNull(
                $send_response->hash
            );
            
        $this
            ->assertNotEmpty(
                trim($send_response->hash)
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
            
        $this
            ->assertHorizonOperationResponseHash(
                $send_response,
                $transacion_envelope_xdr,
                "HostFunctionTypeHostFunctionTypeUploadContractWasm"
            );
    }
    
    public function assertHorizonOperationResponseHash(
        SendTransactionResponse $send_response,
        string $transacion_envelope_xdr,
        string $expected_class
    )
    {
        $transaction_horizon_response = HorizonUtil::requestHorizonTransaction(
            $send_response->hash
        );
        
        $this
            ->assertNotNull(
                $transaction_horizon_response
            );
        
        $this
            ->assertEquals(
                1,
                $transaction_horizon_response->getOperationCount()
            );
            
        $this
            ->assertEquals(
                $transacion_envelope_xdr,
                $transaction_horizon_response
                    ->getEnvelopeXdr()
                    ->toBase64Xdr()
            );
            
        $meta = $transaction_horizon_response->getResultMetaXdrBase64();
        
        $this
            ->assertNotNull(
                $meta
            );

        $meta_xdr = XdrTransactionMeta::fromBase64Xdr($meta);
          
        $this
            ->assertNotNull(
                $meta_xdr
            );
            
        $this
            ->assertNotNull(
                $meta_xdr->toBase64Xdr()
            );
        /*   
        $this
            ->assertEquals(
                $meta,
                $meta_xdr->toBase64Xdr()
            );*/
            
        $horizon_operations_response = HorizonUtil::responseHorizonOperations(
            $send_response->hash,
            10,
            "desc"
        );
        
        $this
            ->assertTrue(
                count($horizon_operations_response) == 1
            );
            
        $this
            ->assertHorizonOperationClass(
                $horizon_operations_response,
                $expected_class
            );
    }
    
    private function assertHorizonOperationClass(
        array $horizon_operations_response,
        string $expected_class
    )
    {
        if ($horizon_operations_response[0] instanceof InvokeHostFunctionOperationResponse) {
            $this
                ->assertEquals(
                    $expected_class,
                    $horizon_operations_response[0]->function
                );
                
            if($expected_class !== "HostFunctionTypeHostFunctionTypeInvokeContract"){
                return;
            }
            
            foreach ($horizon_operations_response[0]?->getParameters() as $parameter) {
                $this->assertNotEquals("", trim($parameter->type));
                $this->assertNotNull($parameter->value);
                $this->assertNotEquals("", trim($parameter->value));
                print("Parameter type :" . $parameter->type . " value: " . $parameter->value . PHP_EOL);
            }
                
            return;
        }
        
        $this->fail();
    }
}