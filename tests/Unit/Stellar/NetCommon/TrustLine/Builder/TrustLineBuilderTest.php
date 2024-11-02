<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Contract\Builder;

use PHPUnit\Framework\TestCase;
use App\Stellar\NetCommon\TrustLine\Builder\TrustLineBuilder;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;
use Soneso\StellarSDK\StellarSDK;

class TrustLineBuilderTest extends TestCase
{
    public function testTrustLineOperations()
    {
        $account_key_par_issuer = AccountUtil::generateAccountKeyPar();
            
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
            
        $account_key_par_trustor = AccountUtil::generateAccountKeyPar();
            
        $account_id_trustor = $account_key_par_trustor->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id_trustor              
            );
    
        $account_trustor = AccountUtil::requestAccount($account_id_trustor);
            
        $this
            ->assertNotNull(
                $account_trustor
            );
        
        $asset_code = "XRP";
        
        //no trustline
        $this
            ->assertCount(
                1,
                $account_trustor->getBalances()
            );
                 
        $counter = 0;
        
        foreach ($account_trustor->getBalances() as $balance) {
            if ($balance->getAssetCode() !== $asset_code) {
                continue;
            }
            
            $counter ++;
        }
        
        $this
            ->assertEquals(
                0,
                $counter
            );
        
        //create trustline
        $trust_asset_limit = 10000;
        
        $created_trust_line = TrustLineBuilder::buildTrustLine(
            $account_key_par_trustor,
            $account_trustor,
            $account_issuer,
            $asset_code,
            $trust_asset_limit,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertTrue(
                $created_trust_line
            );
            
        $account_trustor = AccountUtil::requestAccount($account_id_trustor);
        
        $this
            ->assertCount(
                2,
                $account_trustor->getBalances()
            );
                 
        $counter = 0;
        
        foreach ($account_trustor->getBalances() as $balance) {
            if ($balance->getAssetCode() !== $asset_code) {
                continue;
            }
            
            $counter ++;
            
            $this
                ->assertEquals(
                    (int)$balance->getLimit(),
                    (int)$trust_asset_limit
                );
        }
        
        $this
            ->assertEquals(
                1,
                $counter
            );
            
        //update trustline
        $trust_asset_limit = 40000;
        
        $created_trust_line = TrustLineBuilder::buildTrustLine(
            $account_key_par_trustor,
            $account_trustor,
            $account_issuer,
            $asset_code,
            $trust_asset_limit,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertTrue(
                $created_trust_line
            );
            
        $account_trustor = AccountUtil::requestAccount($account_id_trustor);
        
        $this
            ->assertCount(
                2,
                $account_trustor->getBalances()
            );
                 
        $counter = 0;
        
        foreach ($account_trustor->getBalances() as $balance) {
            if ($balance->getAssetCode() !== $asset_code) {
                continue;
            }
            
            $counter ++;
            
            $this
                ->assertEquals(
                    (int)$balance->getLimit(),
                    (int)$trust_asset_limit
                );
        }
        
        $this
            ->assertEquals(
                1,
                $counter
            );
            
        //delete trustline
        $trust_asset_limit = 0;
        
        $created_trust_line = TrustLineBuilder::buildTrustLine(
            $account_key_par_trustor,
            $account_trustor,
            $account_issuer,
            $asset_code,
            $trust_asset_limit,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertTrue(
                $created_trust_line
            );
            
        $account_trustor = AccountUtil::requestAccount($account_id_trustor);
        
        $this
            ->assertCount(
                1,
                $account_trustor->getBalances()
            );
                 
        $counter = 0;
        
        foreach ($account_trustor->getBalances() as $balance) {
            if ($balance->getAssetCode() !== $asset_code) {
                continue;
            }
            
            $counter ++;
        }
        
        $this
            ->assertEquals(
                0,
                $counter
            );
    }
}