<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\NetCommon\Account\Creator;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\TestNet\Account\AccountUtil;
use App\Stellar\NetCommon\Account\Creator\AccountCreator;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;

class AccountCreatorTest extends TestCase
{
    public function testCreateAccountFromParentAccount()
    {
        $parent_account_key_par = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $parent_account_key_par                
            );
            
        $parent_account_id = $parent_account_key_par->getAccountId();
        
        $this
            ->assertNotNull(
                $parent_account_id                
            );
            
        $parent_account = AccountUtil::requestAccount($parent_account_id);
        
        $this
            ->assertNotNull(
                $parent_account                
            );
        
        $initial_amount = 10;
            
        $new_account = AccountCreator::createAccountFromParentAccount(
            $parent_account,
            $parent_account_key_par,
            $initial_amount,
            SorobanServerSingletonFactory::getServerNetwork(),
            StellarSDK::getFutureNetInstance()
        );
        
        $this
            ->assertNotNull(
                $new_account                
            );
            
        $this
            ->assertNotNull(
                $new_account
                    ->getAccountId()
            );
    }
}