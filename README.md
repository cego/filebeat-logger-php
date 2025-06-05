# FilebeatLogger
## Install via composer
```
composer require cego/filebeat-logger
```

## Helper for creating ECS compliant log context
```php
Log::error('Error message', ECS::create()
    ->withThrowable($exception)
    ->withEvent(
        action: 'create-user',
        category: [],
        dataset: 'user-service.registration',
        outcome: 'success',
        id: '12345',
    );
```
