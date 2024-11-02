<?php

declare(strict_types=1);

namespace App\Stellar\TestNet\SingletonFactory;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Network;
use App\Stellar\NetCommon\Log\LoggerUtil;
use App\Stellar\TestNet\Interfaces\ISorobanServerSingletonFactory;

class SorobanServerSingletonFactory implements ISorobanServerSingletonFactory
{
    private static $soroban_server_instance = null;
    
    public const FUTURENET_TEST_NET_URL = "https://rpc-futurenet.stellar.org:443";
    
    private function __construct(){}
    
    /**
     * Initializes the singleton SorobanServer instance
     */
    private static function initSorobanServerInstance(): void
    {
        $soroban_server = new SorobanServer(
            self::FUTURENET_TEST_NET_URL
        );
        
        $soroban_server->enableLogging = false;
        
        $soroban_server->acknowledgeExperimental = true;
        
        self::$soroban_server_instance = $soroban_server;
    }
    
    /**
     * Return the SorobanServer singleton instance
     */
    public static function getServerInstance(): SorobanServer
    {
        try{
            if(is_null(self::$soroban_server_instance)){
                 self::initSorobanServerInstance();
            }
            
            return self::$soroban_server_instance;
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }
    
    /**
     * Return the SorobanServer singleton instance respective stellar network
     */
    public static function getServerNetwork(): Network
    {
        return Network::futurenet();
    }
}