<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Account\Transfer;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use App\Stellar\NetCommon\Asset\Creator\AssetCreator;
use App\Stellar\NetCommon\Contract\Builder\ContractOperationBuilder;
use App\Stellar\NetCommon\Contract\Builder\ContractTransactionBuilder;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\NetCommon\Account\Transfer\PathAccountTransfer;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;

class PathAccountTransferTest extends TestCase
{
    public function testTransferPathAccountAssets()
    {
        //issuer
        $account_key_par_issuer = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_issuer                
            );
            
        $account_id_issuer = $account_key_par_issuer->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_issuer                
            );
            
        $account_issuer = AccountUtil::requestAccount($account_id_issuer);
        
        $this
            ->assertNotNull(
                $account_issuer                
            );
            
        //origin
        $account_key_par_origin = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_origin                
            );
            
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
            
        //destination
        $account_key_par_destination = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_destination                
            );
            
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
            
        // middleman1
        $account_key_par_middleman1 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_middleman1               
            );
            
        $account_id_middleman1 = $account_key_par_middleman1->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_middleman1                
            );
            
        $account_middleman1 = AccountUtil::requestAccount($account_id_middleman1);
        
        $this
            ->assertNotNull(
                $account_middleman1                
            );
        
        // middleman2
        $account_key_par_middleman2 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_middleman2               
            );
            
        $account_id_middleman2 = $account_key_par_middleman2->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_middleman2                
            );
            
        $account_middleman2 = AccountUtil::requestAccount($account_id_middleman2);
        
        $this
            ->assertNotNull(
                $account_middleman2
            );
            
        $transfer_amount = 100;
        
        $trust_amount = 200199;
            
        $iom_asset = AssetCreator::createAsset(
            "IOM",
            $account_id_issuer
        );
        
        $this
            ->assertNotNull(
                $iom_asset
            );
        
        $moon_asset = AssetCreator::createAsset(
            "MOON",
            $account_id_issuer
        );
        
        $this
            ->assertNotNull(
                $moon_asset
            );
        
        $eco_asset = AssetCreator::createAsset(
            "ECO",
            $account_id_issuer
        );
        
        $this
            ->assertNotNull(
                $eco_asset
            );
          
        $iom_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
            $iom_asset,
            $trust_amount
        );
        
        $this
            ->assertNotNull(
                $iom_trust_operation
            );
        
        $moon_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
            $moon_asset,
            $trust_amount
        );
        
        $this
            ->assertNotNull(
                $moon_trust_operation
            );
        
        $eco_trust_operation = ContractOperationBuilder::buildChangeTrustContractOperation(
            $eco_asset,
            $trust_amount
        );
        
        $this
            ->assertNotNull(
                $eco_trust_operation
            );
        
        $iom_trust_transaction_result = TransactionUtil::createAndSendMultiOperationTransaction(
            [
                $account_key_par_origin
            ],
            $account_origin,
            [
                $iom_trust_operation
            ],
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $iom_trust_transaction_result
            );
            
        $iom_trust_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($iom_trust_transaction_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $iom_trust_transaction_status
            );
        
        $moon_trust_transaction_result = TransactionUtil::createAndSendMultiOperationTransaction(
            [
                $account_key_par_middleman1
            ],
            $account_middleman1,
            [
                $iom_trust_operation,
                $moon_trust_operation
            ],
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $moon_trust_transaction_result
            );
            
        $moon_trust_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($moon_trust_transaction_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $moon_trust_transaction_status
            );
        
        $eco_trust_transaction_result = TransactionUtil::createAndSendMultiOperationTransaction(
            [
                $account_key_par_middleman2
            ],
            $account_middleman2,
            [
                $moon_trust_operation,
                $eco_trust_operation
            ],
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $eco_trust_transaction_result
            );
            
        $eco_trust_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($eco_trust_transaction_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $eco_trust_transaction_status
            );
            
        $eco_trust_transaction_destination_result = TransactionUtil::createAndSendMultiOperationTransaction(
            [
                $account_key_par_destination
            ],
            $account_destination,
            [
                $eco_trust_operation
            ],
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $eco_trust_transaction_destination_result
            );
            
        $eco_trust_transaction_destination_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($eco_trust_transaction_destination_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $eco_trust_transaction_destination_status
            );
            
        $iom_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
            $iom_asset,
            $transfer_amount,
            $account_key_par_origin
                ->getAccountId()
        );
        
        $this
            ->assertNotNull(
                $iom_payment_operation
            );
        
        $moon_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
            $moon_asset,
            $transfer_amount,
            $account_key_par_middleman1
                ->getAccountId()
        );
        
        $this
            ->assertNotNull(
                $moon_payment_operation
            );
        
        $eco_payment_operation = ContractOperationBuilder::buildPaymentContractOperation(
            $eco_asset,
            $transfer_amount,
            $account_key_par_middleman2
                ->getAccountId()
        );
        
        $this
            ->assertNotNull(
                $eco_payment_operation
            );
        
        $payment_transactions_result = TransactionUtil::createAndSendMultiOperationTransaction(
            [
                $account_key_par_issuer 
            ],
            $account_issuer,
            [
                $iom_payment_operation,
                $moon_payment_operation,
                $eco_payment_operation
            ],
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $payment_transactions_result
            );
            
        $payment_transactions_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($payment_transactions_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $payment_transactions_status
            );
            
        $sell_offer_middleman1 = ContractOperationBuilder::buildManageSellOfferOperation(
            $moon_asset,
            $iom_asset,
            $transfer_amount,
            0.5
        );
        
        $this
            ->assertNotNull(
                $sell_offer_middleman1
            );
        
        $sell_offer_middleman2 = ContractOperationBuilder::buildManageSellOfferOperation(
            $eco_asset,
            $moon_asset,
            $transfer_amount,
            0.5
        );
        
        $this
            ->assertNotNull(
                $sell_offer_middleman2
            );
            
        $sell_offer_middleman1_transaction_result = TransactionUtil::createAndSendTransaction(
            [
                $account_key_par_middleman1
            ],
            $account_middleman1,
            $sell_offer_middleman1,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $sell_offer_middleman1_transaction_result
            );
            
        $sell_offer_middleman1_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($sell_offer_middleman1_transaction_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $sell_offer_middleman1_transaction_status
            );
        
        $sell_offer_middleman2_transaction_result = TransactionUtil::createAndSendTransaction(
            [
                $account_key_par_middleman2
            ],
            $account_middleman2,
            $sell_offer_middleman2,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $sell_offer_middleman2_transaction_result
            );
            
        $sell_offer_middleman2_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($sell_offer_middleman2_transaction_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $sell_offer_middleman2_transaction_status
            );
            
        $payment_strict_send_operation = ContractOperationBuilder::buildPathPaymentStrictSendOperation(
            $iom_asset,
            $eco_asset,
            $account_id_destination,
            $transfer_amount,
            380,
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $payment_strict_send_operation
            );
        
        $payment_strict_send_transaction_result = TransactionUtil::createAndSendTransaction(
            [
                $account_key_par_origin
            ],
            $account_origin,
            $payment_strict_send_operation,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $payment_strict_send_transaction_result
            );
            
        $payment_strict_send_transaction_status = PollTransactionUtil::pollTransactionResponseStatus(
            SorobanServerSingletonFactory::getServerInstance(),
            trim($payment_strict_send_transaction_result->getHash())
        );
        
        $this
            ->assertNotNull(
                $payment_strict_send_transaction_status
            );
            
        $counter = 0;
        
        $total_balance = 0;
        
        $account_destination = AccountUtil::requestAccount($account_id_destination);
        
        $this
            ->assertNotNull(
                $account_destination                
            );
            
        foreach ($account_destination->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE
                && $balance->getAssetCode() == "ECO") {
                $total_balance += $balance->getBalance();
                
                $counter ++;
            }
        }
        
        $this
            ->assertEquals(
                $counter,
                1
            );
            
        $this
            ->assertEquals(
                $total_balance,
                400
            );
            
        // ===================================
        //issuer2
        $account_key_par_issuer2 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_issuer2                
            );
            
        $account_id_issuer2 = $account_key_par_issuer2->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_issuer2                
            );
            
        $account_issuer2 = AccountUtil::requestAccount($account_id_issuer2);
        
        $this
            ->assertNotNull(
                $account_issuer2               
            );
            
        //origin2
        $account_key_par_origin2 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_origin2                
            );
            
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
            
        //destination
        $account_key_par_destination2 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_destination2              
            );
            
        $account_id_destination2 = $account_key_par_destination2->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_destination2               
            );
            
        $account_destination2 = AccountUtil::requestAccount($account_id_destination2);
        
        $this
            ->assertNotNull(
                $account_destination2          
            );
            
        // middleman3
        $account_key_par_middleman3 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_middleman3              
            );
            
        $account_id_middleman3 = $account_key_par_middleman3->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_middleman3               
            );
            
        $account_middleman3 = AccountUtil::requestAccount($account_id_middleman3);
        
        $this
            ->assertNotNull(
                $account_middleman3               
            );
        
        // middleman4
        $account_key_par_middleman4 = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par_middleman4               
            );
            
        $account_id_middleman4 = $account_key_par_middleman4->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_middleman4               
            );
            
        $account_middleman4 = AccountUtil::requestAccount($account_id_middleman4);
        
        $this
            ->assertNotNull(
                $account_middleman4
            );
        
        $asset_codes2 = [
            "IOM2",
            "MOON2",
            "ECO2"
        ];
        
        $account_key_par_middlemans2 = [
            $account_key_par_middleman3,
            $account_key_par_middleman4
        ];
        
        $change_trust_asset_limits2 = [
            200999,
            200999,
            200999
        ];
        
        $transfer_amount_middlemans2 = [
            10,
            10
        ];
        
        $transfer_price_middlemans2 = [
            0.5,
            0.5
        ];
        
        $transfer_amount2 = 10;
        
        $final_destination_amount2 = 38;
        
        $final_destination_amount_exact2 = false;
            
        $transfer_path_account_assets_success2 = PathAccountTransfer::transferPathAccountAssets(
            $account_key_par_issuer2,
            $account_key_par_origin2,
            $account_key_par_destination2,
            $account_key_par_middlemans2,
            $asset_codes2,
            $change_trust_asset_limits2,
            $transfer_amount_middlemans2,
            $transfer_price_middlemans2,
            $transfer_amount2,
            $final_destination_amount2,
            $final_destination_amount_exact2,
            SorobanServerSingletonFactory::getServerInstance(),
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertTrue(
                $transfer_path_account_assets_success2
            );
            
        $counter2 = 0;
        
        $total_balance2 = 0;
        
        $account_destination2 = AccountUtil::requestAccount($account_id_destination2);
        
        $this
            ->assertNotNull(
                $account_destination2                
            );
            
        foreach ($account_destination2->getBalances() as $balance) {
            if ($balance->getAssetType() != Asset::TYPE_NATIVE
                && $balance->getAssetCode() == "ECO2") {
                $total_balance += $balance->getBalance();
                
                $counter ++;
            }
        }
        
        $this
            ->assertEquals(
                $counter2,
                1
            );
            
        $this
            ->assertEquals(
                $total_balance2,
                40
            );
    }   
}