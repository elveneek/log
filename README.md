Фасад для логирования при помощи monolog.

## Установка

	composer require "elveneek/log"

## Использование

```php
Elveneek\Log::get()->debug('Debug message',['user'=>'username', 'order_id'=> 12]);
Elveneek\Log::debug('test',['user'=>'username', 'order_id'=> 12]);
Elveneek\RotatingLog::info('test',['user'=>'username2', 'order_id'=> 12]);

//Размещение разных логов в зависимости от контекста или даты

class OrderLog extends \Elveneek\Log 
{
	 public function customFilename($record){
	 
		return $record["context"]['order_id'] .  '.log';
    }
	
}


OrderLog::info('Order created', ['order_id'=> $order->id, 'data'=>$import_data]);
```