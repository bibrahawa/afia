<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $apiKey;
    private $apiUrl;
    private $defaultSender;

    public function __construct()
    {
        $this->apiKey = config('services.nimba_sms.api_key');
        $this->apiUrl = config('services.nimba_sms.api_url');
        $this->defaultSender = config('services.nimba_sms.default_sender', 'APROSAFE');
    }

    public function sendSms(string $phoneNumber, string $message, string $sender = null): array
    {
        try {
            
            $sender = $this->defaultSender;

            // Préparer les données
            $data = [
                "to"          => [$phoneNumber],
                "sender_name" => $sender,
                "message"     => $message
            ];

            $headers = array(
                $this->apiKey,
                "Content-Type: application/json"
            );

            $options = [
                "http" => [
                    "method"        => "POST",
                    "header"        => implode("\r\n", $headers),
                    "content"       => json_encode($data),
                    "ignore_errors" => true
                ]
            ];

            $context  = stream_context_create($options);
            $response = file_get_contents($this->apiUrl, false, $context);

            // Récupérer le code HTTP de la réponse
            $http_response_header = $http_response_header ?? [];
            $status_line = $http_response_header[0] ?? '';
            preg_match('/HTTP\/\S*\s(\d{3})/', $status_line, $match);
            $status_code = $match[1] ?? 0;

            if ($status_code == 201) {
                $responseData = $status_code;
                
                Log::info("SMS envoyé avec succès", [
                    'phone' => $phoneNumber,
                    'sender' => $sender,
                    'message_id' => $responseData['messageid'] ?? null,
                    'message_length' => strlen($message)
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['messageid'] ?? null,
                    'data' => $responseData
                ];
                
            } else { 

                $error = "Erreur (HTTP $status_code) : " . $response ?? 'Erreur API SMS';
                
                Log::error("Échec envoi SMS", [
                    'phone' => $phoneNumber,
                    'error' => $error,
                    'status_code' => $status_code
                ]);

                return [
                    'success' => false,
                    'error' => $error
                ];
            }

        } catch (\Exception $e) {
            Log::error("Exception lors de l'envoi SMS", [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    private function cleanPhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les caractères non numériques sauf le +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Si le numéro commence par 0, remplacer par +224 (Guinée)
        if (preg_match('/^0(\d{9})$/', $cleaned, $matches)) {
            $cleaned = '+224' . $matches[1];
        }
        
        // Si pas d'indicatif international, ajouter +224
        if (!preg_match('/^\+/', $cleaned) && strlen($cleaned) >= 9) {
            $cleaned = '+224' . $cleaned;
        }

        return $cleaned;
    }

    private function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Vérifier format international (au moins 10 chiffres après le +)
        return preg_match('/^\+\d{10,15}$/', $phoneNumber);
    }

    public function sendBulkSms(array $recipients, string $message, string $sender = null): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $phoneNumber) {
            $result = $this->sendSms($phoneNumber, $message, $sender);
            $results[] = [
                'phone' => $phoneNumber,
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }

            // Délai entre les envois pour éviter le rate limiting
            usleep(200000); // 200ms
        }

        return [
            'total' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }
}