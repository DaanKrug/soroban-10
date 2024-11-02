<?php

declare(strict_types=1);

namespace App\Stellar\TestNet\Horizon;

use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\StellarSDK;
use App\Stellar\NetCommon\Poll\PollTransactionUtil;
use App\Stellar\NetCommon\Transaction\TransactionUtil;
use App\Stellar\TestNet\Interfaces\IHorizonUtil;
use App\Stellar\NetCommon\Log\LoggerUtil;

class HorizonUtil implements IHorizonUtil
{
    /**
     * Obtain the transaction response from stellar testnet network.
     */
    public static function requestHorizonTransaction(
        string $send_response_hash
    ): ?TransactionResponse
    {
        if(
            is_null($send_response_hash)
            || trim($send_response_hash) === ""
        ){
            return null;
        }
        
        try{
            return PollTransactionUtil::pollRequestTransaction(
                StellarSDK::getFutureNetInstance(),
                $send_response_hash
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Obtain the operations response from
     * transaction response from stellar testnet network.
     */
    public static function responseHorizonOperations(
        string $send_response_hash,
        int $limit = 10,
        string $order = "desc"
    ): array
    {
        if(
            is_null($send_response_hash)
            || trim($send_response_hash) === ""
        ){
            return [];
        }
        
        try{
            return TransactionUtil::getResponseOperations(
                StellarSDK::getFutureNetInstance(),
                $send_response_hash,
                $limit,
                $order
            );
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return [];
        }
    }

}