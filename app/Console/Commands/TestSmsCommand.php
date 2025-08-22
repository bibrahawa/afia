<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    protected $signature = 'sms:test {phone} {message}';
    protected $description = 'Test SMS sending';

    public function handle(SmsService $smsService)
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');

        $this->info("📱 Test d'envoi SMS vers {$phone}");
        $this->info("💬 Message: {$message}");

        $result = $smsService->sendSms($phone, $message, 'TEST');

        if ($result['success']) {
            $this->info("✅ SMS envoyé avec succès");
            $this->info("🆔 Message ID: " . ($result['message_id'] ?? 'N/A'));
        } else {
            $this->error("❌ Échec de l'envoi");
            $this->error("🔥 Erreur: " . $result['error']);
        }
    }
}