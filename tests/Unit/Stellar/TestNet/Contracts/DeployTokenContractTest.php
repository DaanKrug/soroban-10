<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\Contracts;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;
use App\Stellar\NetCommon\Contract\Executor\DeployTokenContractExecutor;
use Tests\Unit\Stellar\TestNet\Horizon\HorizonUtilTest;

class DeployTokenContractTest extends TestCase
{
    public function testDeployTokenContract()
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
            
        $contract_operation = ContractOperationBuilder::buildDeployTokenContractFunction(
            $account_id
        );
        
        $this
            ->assertNotNull(
                $contract_operation
            );
            
        $contract_transaction = ContractTransactionBuilder::createTransaction(
            $contract_operation,
            $account
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
                $pool_status_response
                    ->getCreatedContractId()
            );
            
        $this
            ->assertEquals(
                (int)DeployTokenContractExecutor::TOKEN_CONTRACT_TYPE,
                (int)$pool_status_response
                    ->getResultValue()
                    ->getType()
                    ->getValue()
            );
        
        $horizon_util_test = new HorizonUtilTest("__construct");
        
        $horizon_util_test
            ->assertHorizonOperationResponseHash(
                $send_response,
                $transacion_envelope_xdr,
                "HostFunctionTypeHostFunctionTypeCreateContract"
            );
            
        $account_key_par2 = AccountUtil::generateAccountKeyPar();
            
        $account_id2 = $account_key_par2->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id2      
            );
    
        $account2 = AccountUtil::requestAccount($account_id2);
            
        $this
            ->assertNotNull(
                $account2
            );
            
        $deployed_token_contract_response = DeployTokenContractExecutor::deployTokenContract(
            SorobanServerSingletonFactory::getServerInstance(),
            $account_key_par2,
            $account2,
            SorobanServerSingletonFactory::getServerNetwork()
        );
        
        $this
            ->assertNotNull(
                $deployed_token_contract_response
            );
            
        $this
            ->assertNotNull(
                $deployed_token_contract_response
                    ->getCreatedContractId()
            );
            
        $this
            ->assertEquals(
                (int)DeployTokenContractExecutor::TOKEN_CONTRACT_TYPE,
                (int)$deployed_token_contract_response
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
                (int)$deployed_token_contract_response
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
