<?php

declare(strict_types=1);

namespace App\Stellar\TestNet\Interfaces;

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Network;

interface ISorobanServerSingletonFactory
{
    public static function getServerInstance(): SorobanServer;
    
    public static function getServerNetwork(): Network;
}