<?php

use Google\Client;
use Illuminate\Support\Facades\Http;

if (!function_exists('sendPushNotification')) {
    function sendPushNotification($user, $title, $body) {
        $client = new Client();
        $client->setAuthConfig(config('services.firebase.credentials'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $accessToken = $client->fetchAccessTokenWithAssertion();
        
        $data = [
            "message" => [
                "token" => $user->fcm_token,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                ],
                "data" => [  // Optional, but useful for background notifications
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                    "id" => "1",
                    "status" => "done"
                ],
                "android" => [
                    "priority" => "high",
                    "notification" => [
                        "channel_id" => "high_importance_channel"
                    ]
                ]
            ]
        ];
        $response = Http::withToken($accessToken['access_token'])
            ->post('https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send', $data);

        return $response->json();
    }
}
