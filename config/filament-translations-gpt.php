<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI compatible client
    |--------------------------------------------------------------------------
    |
    | Any OpenAI compatible chat completions API works, for example Groq:
    | OPENAI_BASE_URL=https://api.groq.com/openai/v1 and OPENAI_MODEL=llama-3.1-8b-instant
    |
    | Without an API key the GPT scan sends nothing and notifies the user.
    |
    */
    'openai_client' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunk size
    |--------------------------------------------------------------------------
    |
    | How many translations are sent to the API in one request.
    |
    */
    'chunk_size' => 50,
];
