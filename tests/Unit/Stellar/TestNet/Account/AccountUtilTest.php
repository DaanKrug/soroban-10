<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\Account;

use PHPUnit\Framework\TestCase;
use App\Stellar\TestNet\Account\AccountUtil;

class AccountUtilTest extends TestCase
{    
    public function testCreateFuturenetAccount()
    {
        $account_key_par = AccountUtil::generateAccountKeyPar();
            
        $this
            ->assertNotNull(
                $account_key_par                
            );
            
        $account_id = $account_key_par->getAccountId();
        
        $this
            ->assertNotNull(
                $account_id                
            );
            
        $account = AccountUtil::requestAccount("");
        
        $this
            ->assertNull(
                $account
            );
            
        $account = AccountUtil::requestAccount(" ");
        
        $this
            ->assertNull(
                $account
            );
            
        $account = AccountUtil::requestAccount($account_id);
        
        $this
            ->assertNotNull(
                $account
            );
            
        $this
            ->assertEquals(
                $account_id,
                $account->getAccountId()
            );
            
        $this
            ->assertNotNull(
                $account->getSequenceNumber()
            );
    }
}
