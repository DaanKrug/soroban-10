<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Poll;

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Transaction;
use App\Stellar\NetCommon\Interfaces\IPollTransactionUtil;
use App\Stellar\NetCommon\Log\LoggerUtil;

class PollTransactionUtil implements IPollTransactionUtil
{
    private const MAX_REQUESTS = 6;
    
    private const MICROSECONDS_TIMEOUT = 350000;
    
    /**
     * Send one $signed_transaction signed transaction request to
     * stellar network.
     * Try SELF::MAX_REQUESTS times or return null.
     * - $server also is used to define the network type: Mainnet or Testnet.
     * - SendTransactionResponse will contain the $send_response_hash hash
     * to be used on "pollRequestTransaction" method bellow.
     */
    public static function pollSendTransaction(
        SorobanServer $server,
        Transaction $signed_transaction
    ): ?SendTransactionResponse
    {
        try{
            $send_response = null;
            
            $counter = 0;
            
            while(
                is_null($send_response)
                && $counter < SELF::MAX_REQUESTS
            ){
                usleep($counter > 0 ? self::MICROSECONDS_TIMEOUT : 0);
                
                $send_response = $server
                    ->sendTransaction(
                        $signed_transaction
                    );
                    
                $counter ++;
            }
            
            return $send_response;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Request a transaction response for stellar network.
     * Another transaction generated a Request with $send_response_hash hash
     * to be used to get the respective response.
     * Try SELF::MAX_REQUESTS times at max to get a response,
     * otherwise returns null.
     * - $stellar_net is used to define the network type: Mainnet or Testnet.
     */
    public static function pollRequestTransaction(
        StellarSDK $stellar_net,
        string $send_response_hash
    ): ?TransactionResponse
    {
        try{
            $transaction_response = null;
            
            $counter = 0;
            
            while(
                is_null($transaction_response)
                && $counter < SELF::MAX_REQUESTS
            ){
                usleep($counter > 0 ? self::MICROSECONDS_TIMEOUT : 0);
                
                $transaction_response = $stellar_net
                    ->requestTransaction(
                        $send_response_hash
                    );
                    
                $counter ++;
            }
            
            return $transaction_response;
        } catch(\Throwable $t){
            if($t instanceof HorizonRequestException){
                usleep(self::MICROSECONDS_TIMEOUT);
            
                return self::pollRequestTransaction(
                    $stellar_net,
                    $send_response_hash
                );
            }
            
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Waits for a transaction be finished/aborted on stellar network
     * and after that returns the response.
     * - $server also is used to define the network type: Mainnet or Testnet.
     */
    public static function pollTransactionResponseStatus(
        SorobanServer $server,
        string $transaction_id
    ): ?GetTransactionResponse
    {
        try{
            $status_response = null;
            
            $status = GetTransactionResponse::STATUS_NOT_FOUND;
            
            while ($status == GetTransactionResponse::STATUS_NOT_FOUND) {
                $status_response = $server
                    ->getTransaction(
                        $transaction_id
                    );
                    
                $status = $status_response->status;
                
                usleep(self::MICROSECONDS_TIMEOUT);
            }
            
            return $status_response;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
}