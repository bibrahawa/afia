<?php

namespace App\Http\Controllers;

class SmsController
{
    public function send($phone, $message)
    {
        $data = $this->getSmsHeader();
        $headers = $data['headers'];
        $url     = $data['url'];

        $body = [
            "to"          => ["622099672"], // numéros destinataires
            "sender_name" => "MyCauri",
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

        $url = "https://api.nimbasms.com/v1/messages";

        $headers = [
            "Authorization: Basic ODE2MDM4ZGM3ZTVlNmMzNTAyZDJjNzI5MGQ4NWExOGM6c19TaDVzYnpJOGRCSi1HaVVVNWxWWVQ3aDIyTXdVZTgwNTVQNWtnVm5jMlAtM0g3STVUT2VmM3RUVHVtdHFfWmliXzE0UElWeGZrdXNMUGp5eFVrZmJaQy1zUTZmWEF2U1N5eVhoU3NlWGc=",
            "Content-Type: application/json"
        ];

        return ['headers' => $headers, 'url' => $url];
    }

}