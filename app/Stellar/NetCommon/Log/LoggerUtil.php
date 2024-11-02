<?php

declare(strict_types=1);

namespace App\Stellar\NetCommon\Log;

use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use App\Stellar\NetCommon\Interfaces\ILoggerUtil;

class LoggerUtil implements ILoggerUtil
{
    private static $logger_instance = null;
    
    private const LOGGER_NAME = 'stellar_app';
    
    private const LOGGER_PATH = './storage/logs/stellar_app.log';
    
    /**
     * Initializes the singleton instance, with the defined parameters.
     */
    private static function initializeLogger()
    {
        if(!is_null(self::$logger_instance)){
            return;
        }
        
        self::$logger_instance = new Logger(
            self::LOGGER_NAME
        );
        
        $rotating_file_handler = new RotatingFileHandler(
            self::LOGGER_PATH,
            0,
            Level::Info,
            true,
            0664
        );
        
        $rotating_file_handler
            ->setFilenameFormat(
                '{date}-{filename}',
                'Y-m-d'
            );

        self::$logger_instance
            ->pushHandler(
                $rotating_file_handler
            );
    }

    /**
     * Helps to improve error detection/correction when running
     * the unit tests.
     */
    public static function logThrowable(\Throwable $t): void
    {
        if(env('APP_ENV') !== 'testing'){
            return;
        }
        
        self::initializeLogger();
        
        self::$logger_instance
            ->error(
                'File: ' . $t->getFile()
            );
            
        self::$logger_instance
            ->error(
                '    Line: ' . $t->getLine()
            );
        
        self::$logger_instance
            ->error(
                '    Message: ' . $t->getMessage()
            );
            
        if(
            !($t instanceof HorizonRequestException)
            || is_null($t->getHorizonErrorResponse())
            || is_null(
                $t
                    ->getHorizonErrorResponse()
                    ->getExtras()
            )
            || is_null(
                $t
                    ->getHorizonErrorResponse()
                    ->getExtras()
                    ->getResultCodesOperation()
            )
        ){
            return;
        }
        
        $extra_messages_array = $t
            ->getHorizonErrorResponse()
            ->getExtras()
            ->getResultCodesOperation();
            
        $extra_messages = implode(
            "\n\t\t",
            $extra_messages_array                          
        );
        
        self::$logger_instance
            ->error(
                '    Extras Message Codes: ' . $extra_messages
            );
    }
    
    public static function info(string $message): void
    {
        self::initializeLogger();
        
        self::$logger_instance
            ->info(
                $message
            );
    }
}