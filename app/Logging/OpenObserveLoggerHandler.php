<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class OpenObserveLoggerHandler extends AbstractProcessingHandler
{

    protected function write(LogRecord $record): void
    {
        $logData = json_decode($record->formatted, true);
        if (!$logData) {
            // If parsing fails, create a simple object with the message
            $logData = [
                'message' => $record->message,
                'level' => $record->level->name,
                'channel' => $record->channel,
                'datetime' => $record->datetime->format('c'),
                'extra' => $record->extra,
                'context' => $record->context
            ];
        }

        try {
            $this->store($logData);
        } catch (\Exception $e) {
            // Write to error log if sending to OpenObserve fails
            error_log('Failed to send log to OpenObserve: ' . $e->getMessage());
        }
    }

    private function store(array $logs): void
    {
        Http::withHeaders(
            $this->getHeaders()
        )->withBody(json_encode($logs))->post($this->getUrl());
    }
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $this->getAuth(),
        ];
    }
    private function getUrl(): string
    {
        return env('OPENOBSERVE_URL')."/api/"
            .env('OPENOBSERVE_ORGANIZATION')."/"
            .env('OPENOBSERVE_STREAM')."/_json";
    }
    private function getAuth(): string
    {
        return base64_encode(env('OPENOBSERVE_USERNAME').':'.env('OPENOBSERVE_PASSWORD'));
    }
}
