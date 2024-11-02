<?php

declare(strict_types=1);

namespace App\Stellar\TestNet\Account;

use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Util\FuturenetFriendBot;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\TestNet\Interfaces\IAccountUtil;

class AccountUtil implements IAccountUtil
{
    private const MOCK_SLOW_CREATION_TIME = 0;
    
    /**
     * Generates new KeyPair
     */
    public static function generateAccountKeyPar(): ?KeyPair
    {
        try{
            $account_key_pair = KeyPair::random();
        
            return $account_key_pair;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Obtain the Account respective to $account_id from the Testnet.
     */
    public static function requestAccount($account_id): ?AccountResponse
    {
        try{
            if(!self::createAccount($account_id)){
                return null;
            }
            
            return StellarSDK::getFutureNetInstance()
                ->requestAccount(
                    $account_id
                );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Creates a new Testnet account respective to $account_id
     * if this not exist yet.
     */
    private static function createAccount($account_id): bool
    {
        if(
            is_null($account_id)
            || trim($account_id) === ""
        ){
            return false;
        }
        
        $account_id = trim($account_id);
        
        try{
            $already_exists = StellarSDK::getFutureNetInstance()
                ->accountExists(
                    $account_id
                );
        
            if ($already_exists) {
                return true;
            }
            
            FuturenetFriendBot::fundTestAccount($account_id);
            
            sleep(self::MOCK_SLOW_CREATION_TIME);
            
            return true;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return false;
        }
    }
}