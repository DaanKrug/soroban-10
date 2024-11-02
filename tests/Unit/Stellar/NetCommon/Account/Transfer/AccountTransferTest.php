<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Account\Transfer;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\NetCommon\Account\Transfer\AccountTransfer;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;

class AccountTransferTest extends TestCase
{
    public function testTransferNativeAsset()
    {
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
            
        $transfer_amount = 100;
            
        $transfer_result = AccountTransfer::transferNativeAsset(
            $account_key_par_origin,
            $account_id_destination,
            $transfer_amount,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertTrue(
                $transfer_result                
            );
            
        $account_destination = StellarSDK::getFutureNetInstance()
            ->requestAccount(
                $account_id_destination
            );
        
        foreach ($account_destination->getBalances() as $balance) {
            $code = $balance->getAssetCode();
            
            $type = $balance->getAssetType();
            
            $amount = $balance->getBalance();
            
            $this
                ->assertNull(
                    $code
                );
                
            $this
                ->assertEquals(
                    "native",
                    $type
                );
                
            $this
                ->assertEquals(
                    10100.00,
                    (float)$amount
                );
        }
    }
    
    public function testTransferNonNativeAsset()
    {
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
            
        $change_trust_asset_limit = 100000;
        
        $transfer_amount = 545;
            
        $asset_names = [
            "XRP",
            "XLM",
            "XDC"
        ];
        
        foreach($asset_names as $asset_name){
            $transfer_result = AccountTransfer::transferNonNativeAsset(
                $account_key_par_issuer,
                $account_key_par_origin,
                $account_key_par_destination,
                $asset_name,
                $change_trust_asset_limit,
                $transfer_amount,
                SorobanServerSingletonFactory::getServerNetwork(),
                StellarSDK::getFutureNetInstance()
            );
            
            $this
                ->assertTrue(
                    $transfer_result                
                );
                
            $account_destination = StellarSDK::getFutureNetInstance()
                ->requestAccount(
                    $account_key_par_destination
                        ->getAccountId()
                );
            
            foreach ($account_destination->getBalances() as $balance) {
                $code = $balance->getAssetCode();
                
                if(is_null($code)){
                    continue;
                }
            
                $type = $balance->getAssetType();
                
                $amount = $balance->getBalance();
               
                $this
                    ->assertEquals(
                       "credit_alphanum4",
                        $type
                    );
                
                $this
                    ->assertEquals(
                        545.00,
                        (float)$amount
                    );
            }
        }
    }
}