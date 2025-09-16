<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
                            {url? : Вебхук URL (опционально)}
                            {--d|drop-pending : Удалить pending updates}
                            {--b|bot= : Имя бота из конфига (по умолчанию: default)}';

    protected $description = 'Установка или обновление вебхука Telegram бота';

    public function handle(): int
    {
        $botName = $this->option('bot') ?: config('telegram.default');
        $botConfig = config("telegram.bots.{$botName}");

        if (empty($botConfig)) {
            $this->error("Конфигурация для бота '{$botName}' не найдена!");
            return 1;
        }

        $token = $botConfig['token'] ?? null;
        if (!$token || $token === 'YOUR-BOT-TOKEN') {
            $this->error("Токен бота не настроен для '{$botName}'!");
            return 1;
        }

        $url = $this->argument('url') ?: $botConfig['webhook_url'] ?? null;
        if (!$url || $url === 'YOUR-BOT-WEBHOOK-URL') {
            $this->error("URL вебхука не указан для бота '{$botName}'!");
            return 1;
        }

        $params = [
            'url' => $url,
            'max_connections' => 40,
            'allowed_updates' => $botConfig['allowed_updates'] ?? ['message', 'callback_query']
        ];

        if ($this->option('drop-pending')) {
            $params['drop_pending_updates'] = true;
            $this->info('⚠️ Будут удалены pending updates');
        }

        $this->info("⌛ Устанавливаю вебхук для бота '{$botName}'...");
        $this->line("🔗 URL: {$url}");
        $this->line("🔑 Token: " . substr($token, 0, 5) . '...');

        try {
            $telegram = new Api($token);
            $response = $telegram->setWebhook($params);

            if ($response) {
                $this->newLine();
                $this->info("✅ Вебхук успешно установлен!");
                $this->line("🤖 Бот: {$botName}");
                $this->line("🌐 Endpoint: {$url}");

                Log::info("Telegram webhook updated", [
                    'bot' => $botName,
                    'url' => $url
                ]);
                return 0;
            }

            $this->error('❌ Неизвестная ошибка: API Telegram вернуло false');
            return 1;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Ошибка: " . $e->getMessage());
            $this->line("Проверьте:");
            $this->line("- Доступность URL из интернета");
            $this->line("- Валидность SSL-сертификата");
            $this->line("- Корректность токена бота");

            Log::error('Telegram webhook failed', [
                'bot' => $botName,
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            return 1;
        }
    }
}
