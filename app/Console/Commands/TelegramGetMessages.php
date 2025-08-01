<?php

namespace App\Console\Commands;

use App\Jobs\MangadexSave;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class TelegramGetMessages extends Command
{
    private Api $telegram;
    private int $owner;
    public function __construct()
    {
        $this->owner = env('TELEGRAM_BOT_OWNER');
        $this->telegram = Telegram::bot('manga');
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updates = $this->telegram->getUpdates(['allowed_updates' => ['message']]);
        foreach ($updates as $update) {
            $this->processUpdate($update);
        }
        empty($updates) ?: $this->confirmUpdate($updates[count($updates) - 1]);
    }
    private function processUpdate(Update $update): void
    {
        if ($update->message->from->id == $this->owner) {
            $this->processMessage($update);
        }
    }
    private function processMessage(Update $update): void
    {
        $text = $update->message->text;
        if ($this->hasUrl($text)) {
            $elements = explode('/', $this->getUrl($text));
            if ($elements[2] == 'mangadex.org') {
                MangadexSave::dispatch($elements[4]);
            }
        }
    }
    private function confirmUpdate(Update $update): void
    {
        $this->telegram->getUpdates(['offset' => $update->updateId + 1]);
    }

    private function getUrl(string $string): ?string
    {
        $urls = $this->extractUrls($string);
        if (!empty($urls)) {
            return $urls[0][0];
        }
        return null;
    }

    private function hasUrl(string $string): bool
    {
        return !empty($this->extractUrls($string));
    }
    private function extractUrls(string $string)
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $string, $match);

        return $match;
    }
}
