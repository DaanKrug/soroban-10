<?php

declare(strict_types=1);

namespace App\Stellar\TestNet\Interfaces;

use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

interface IHorizonUtil
{
    public static function requestHorizonTransaction(
        string $send_response_hash
    ): ?TransactionResponse;
    
    public static function responseHorizonOperations(
        string $send_response_hash,
        int $limit = 10,
        string $order = "desc"
    ): array;
}