<?php

namespace App\Logging;

use Monolog\Logger;

class OpenObserveLogger
{
    public function __invoke(array $config): Logger {
        return new Logger("OpenObserve", [new OpenObserveLoggerHandler()]);
    }
}
