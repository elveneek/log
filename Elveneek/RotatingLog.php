<?php
namespace Elveneek;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\IntrospectionProcessor;

class RotatingLog extends Log{
    
    public function init(){
        
        $formatter = new \Elveneek\MetaLogFormatter();
        
        $streamHandler = new RotatingFileHandler(static::getPath().static::getFileName());
        $streamHandler->setFormatter($formatter);
        
        static::$instances[static::class]->pushProcessor(function ($record) {
            $requestID = static::getRequestID();
            $record['request_id'] = $requestID;
            $record['is_this_first_request'] = static::isThisFirstRequest($requestID);
            return $record;
        });

        static::$instances[static::class]->pushHandler($streamHandler);
		static::$instances[static::class]->pushProcessor(new IntrospectionProcessor(Logger::DEBUG, ['Log'], 0));
    }
}