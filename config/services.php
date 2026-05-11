<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VOXORA – External Service Configuration
    |--------------------------------------------------------------------------
    */

    /* ── EduBraille Perangkat ── */
    'edubraille' => [
        'endpoint'  => env('EDUBRAILLE_ENDPOINT', ''),
        'token'     => env('EDUBRAILLE_TOKEN', ''),
        'device_id' => env('EDUBRAILLE_DEVICE_ID', 'DEFAULT'),
    ],

    /* ── Python AI Service ── */
    'python_api' => [
        'url'   => env('PYTHON_API_URL', 'http://localhost:8001'),
        'token' => env('PYTHON_API_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Layanan bawaan Laravel (jangan dihapus)
    |--------------------------------------------------------------------------
    */
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'openai' => [
        'api_key'           => env('OPENAI_API_KEY'),
        'endpoint'          => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
        'model_remediation' => env('OPENAI_MODEL_REMEDIATION', 'gpt-4o'),
        'model_qa'          => env('OPENAI_MODEL_QA', 'gpt-4o-mini'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
