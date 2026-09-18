<?php

namespace App\Http\Controllers;

class SmsController
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

    public function send()
    {
        $data = $this->getSmsHeader();
        $headers = $data['headers'];
        $url     = $data['url'];

        $body = [
            "to"          => ["622099672"],
            "sender_name" => $this->defaultSender,
            "message"     => "Hello, Comment vas-tu ?"
        ];

        $options = [
            "http" => [
                "method"        => "POST",
                "header"        => implode("\r\n", $headers),
                "content"       => json_encode($body),
                "ignore_errors" => true
            ]
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        // Récupérer le code HTTP de la réponse
        $http_response_header = $http_response_header ?? [];
        $status_line = $http_response_header[0] ?? '';
        preg_match('/HTTP\/\S*\s(\d{3})/', $status_line, $match);
        $status_code = $match[1] ?? 0;

        if ($status_code != 201) {
            return "Erreur (HTTP $status_code) : " . $response;
        }

        return $response;
    }

    public function newSms(){
        return view('notification.sendSms');
    }

    public function smsLists(){

        $data = $this->getSmsHeader();
        
        $headers = $data['headers'];
        $url     = $data['url'];

        $options = array(
            "http" => array(
                "method" => "GET",
                "header" => implode("\r\n",$headers)
            )
        );

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        $messages = json_decode($response);


        return view('notification.messageLists', compact('messages'));

    }

    private function getSmsHeader(){

        $headers = [
            $this->apiKey,
            "Content-Type: application/json"
        ];

        return ['headers' => $headers, 'url' => $this->apiUrl];
    }

}