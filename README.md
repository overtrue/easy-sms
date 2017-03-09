# Easy-sms

The easiest way to send short message.

# Usage

```php
use Overtrue\EasySms\EasySms;

$config = [...];
$easySms = new EasySms($config);
$easySms->gateway('Log')->send(1888888888, 'hello world!');
```

# License

MIT
