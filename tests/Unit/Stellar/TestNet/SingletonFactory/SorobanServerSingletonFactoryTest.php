<?php

declare(strict_types=1);

namespace Tests\Unit\Stellar\TestNet\SingletonFactory;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Network;
use App\Stellar\TestNet\SingletonFactory\SorobanServerSingletonFactory;

class SorobanServerSingletonFactoryTest extends TestCase
{
    private const FUTURENET_URL = "https://rpc-futurenet.stellar.org:443";
     
    private const FRIENDBOT_URL = "https://friendbot-futurenet.stellar.org/";
    
    private const FRIENDBOT_PASS = "Test SDF Future Network ; October 2022";

    public function testParameters()
    {
        $this
            ->assertEquals(
                SorobanServerSingletonFactory::FUTURENET_TEST_NET_URL,
                self::FUTURENET_URL
            );
    }
    
    public function testSorobanServerInstatiation()
    {
        $server = SorobanServerSingletonFactory::getServerInstance();
        
        $this
            ->assertNotNull(
                $server                
            );
        
        $this
            ->assertFalse(
                $server->enableLogging
            );
        
        $this
            ->assertTrue(
                $server->acknowledgeExperimental
            );
            
        $health_response = $server->getHealth();
        
        $this
            ->assertEquals(
                GetHealthResponse::HEALTHY,
                $health_response->status
            );   
    }
    
    public function testSorobanNetwork()
    {
        $server = SorobanServerSingletonFactory::getServerInstance();
        
        $this
            ->assertNotNull(
                $server                
            );
            
        $this
            ->assertEquals(
                SorobanServerSingletonFactory::getServerNetwork(),
                Network::futurenet()
            );
            
        $network_response = $server->getNetwork();
        
        $this
            ->assertEquals(
                self::FRIENDBOT_URL,
                $network_response->friendbotUrl
            );
            
        $this
            ->assertEquals(
                self::FRIENDBOT_PASS,
                $network_response->passphrase
            );
            
        $this
            ->assertNotNull(
                $network_response->protocolVersion
            );
    }
}
