<?php
namespace Elveneek;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\IntrospectionProcessor;

class Log {
    public static $instances = [];
    public static $lastUsedRequestID = '';
	
    function __construct(){
    
        if(!isset( static::$instances[static::class])){
            
		   
            $lastPart = explode('\\', static::class);
            static::$instances[static::class] = new Logger($lastPart[count($lastPart)-1]);
            
            $this->init();
            $this->configure();
            
        }
    }
    public function customFilename($record){
		return false;
    }
	
    public function getFileName(){
        return 'default.log';
    }
	
    public function getPath(){
        return ROOT . '/logs/' . static::$instances[static::class]->getName() . '/';
    }
	
    public static function getRequestID(){
         if(!isset($_ENV['REQUEST_ID']) || empty($_ENV['REQUEST_ID'])){
             if(isset($_SERVER['HTTP_X_REQUEST_ID'])){
                 $_ENV['REQUEST_ID'] = $_SERVER['HTTP_X_REQUEST_ID'];
                 return $_ENV['REQUEST_ID'];
             }
             $_ENV['REQUEST_ID'] = bin2hex(random_bytes(16));
         }
         return $_ENV['REQUEST_ID'];
    }
    public static function isThisFirstRequest($requestID){
        if(static::$lastUsedRequestID != $requestID){
            static::$lastUsedRequestID = $requestID;
            return true;
        }
        return false;
    }
    
    public function init(){
        
        $formatter = new \Elveneek\MetaLogFormatter();
        
        $streamHandler = new LogCustomFilesHandler($this->getPath() . $this->getFileName());
		
		
		
        $streamHandler->setFormatter($formatter);
        
        static::$instances[static::class]->pushProcessor(function ($record) {
            $requestID = static::getRequestID();
            $record['request_id'] = $requestID;
            $record['is_this_first_request'] = static::isThisFirstRequest($requestID);
			
			
			foreach (static::$instances[static::class]->getHandlers() as $handler) {
				if ($handler instanceof LogCustomFilesHandler) {
					$handler->setParentRuleset($this);
				}
			}
			
			
            return $record;
        });

        static::$instances[static::class]->pushHandler($streamHandler);
		
        static::$instances[static::class]->pushProcessor(new IntrospectionProcessor(Logger::DEBUG, ['Log'], 0));
    }
    
    public function configure(){
       
    }
    
  
    public static function get(){
        

        if(isset( static::$instances[static::class])){
            return static::$instances[static::class];
        }
        $logger = new static();
        return static::$instances[static::class];
    }
    
    public static function getLeveled(){
        

        if(isset( static::$instances[static::class])){
            return static::$instances[static::class];
        }
        $logger = new static();
        return static::$instances[static::class];
    }
    
    
    
    
    /*********************************************************************/
    /*********************************************************************/
    /*********************************************************************/
    /*********************************************************************/
    //Monolog static facade (not PSR-3 compliant!)
    
    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = array()){
        return static::getLeveled()->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function alert($message, array $context = array()){
        return static::getLeveled()->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical($message, array $context = array()){
        return static::getLeveled()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error($message, array $context = array()){
        return static::getLeveled()->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning($message, array $context = array()){
        return static::getLeveled()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice($message, array $context = array()){
        return static::getLeveled()->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info($message, array $context = array()){
        return static::getLeveled()->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug($message, array $context = array()){
        return static::getLeveled()->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log($level, $message, array $context = array()){
        return static::getLeveled()->log($level,$message, $context);
    }
    
    
}