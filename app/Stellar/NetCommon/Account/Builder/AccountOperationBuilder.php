<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Account\Builder;

use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperation;
use App\Stellar\NetCommon\Interfaces\IAccountOperationBuilder;
use App\Stellar\NetCommon\Log\LoggerUtil;

class AccountOperationBuilder implements IAccountOperationBuilder
{
    /**
     * Creates a CreateAccountOperation
     * for account with id $account_id
     * and initial amount of $initial_amount native tokens.
     */
    public static function buildAccountForParentAccountOperation(
        string $account_id,
        float $initial_amount
    ): ?CreateAccountOperation
    {
        try{
            $builder = new CreateAccountOperationBuilder(
                $account_id,
                (string)$initial_amount
            );
            
            return $builder
                ->build();
        } catch(\Throwable $t){
            LoggerUtil::logThrowable($t);
            
            return null;
        }
    }        
}