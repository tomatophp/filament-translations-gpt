<?php

namespace TomatoPHP\FilamentTranslationsGpt\Jobs;

use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use TomatoPHP\FilamentTranslations\Models\Translation;

class ScanWithGPT implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $language,
        public int $userId,
        public string $userType
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->userType::find($this->userId);
        $getAllTranslations = Translation::all();
        $chunks = array_chunk($getAllTranslations->toArray(), 50);

        $baseUrl = config('filament-translations-gpt.openai_client.base_url', 'https://api.openai.com/v1');
        $apiKey = config('filament-translations-gpt.openai_client.api_key') ?? getenv('OPENAI_API_KEY');
        $model = config('filament-translations-gpt.openai_client.model', 'gpt-3.5-turbo');

        foreach ($chunks as $chunk) {
            $makeJsonArray = [];
            foreach ($chunk as $translation) {
                $makeJsonArray[$translation['key']] = $translation['text']['en'] ?? $translation['key'];
            }
            $json = json_encode($makeJsonArray);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->baseUrl($baseUrl)->post('/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a translator. Your job is to translate the following json object to the language specified in the prompt.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Translate the following json object from English to ' . $this->language . ", ensuring you return only the translated content without added quotes or any other extraneous details. Importantly, any word prefixed with the symbol ':' should remain unchanged",
                    ],
                    [
                        'role' => 'user',
                        'content' => $json,
                    ],
                ],
                'temperature' => 0.4,
                'n' => 1,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to get translation from OpenAI: ' . $response->body());
            }

            $result = json_decode($response->body(), true);

            if (!isset($result['choices'][0]['message']['content'])) {
                Log::error('OpenAI API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                Notification::make()
                    ->title(trans('filament-translations::translation.gpt_scan_notification_error'))
                    ->danger()
                    ->sendToDatabase($user);
            }

            if ($result['choices'] && count($result['choices']) > 0 && $result['choices'][0]['message']) {
                $translationArray = json_decode($result['choices'][0]['message']['content']) ?? [];
            }

            $getLocal = config('filament-translations.locals');
            $local = 'en';
            foreach ($getLocal as $key => $item) {
                if ($item['label'] == $this->language) {
                    $local = $key;
                }
            }

            for ($i = 0; $i < count($chunk); $i++) {
                $translationModel = Translation::query()->where('key', $chunk[$i]['key'])->first();
                if ($translationModel) {
                    $text = $translationModel->text;
                    $text[$local] = $translationArray->{$chunk[$i]['key']} ?? $chunk[$i]['key'];

                    $translationModel->text = $text;
                    $translationModel->save();
                }
            }

            Notification::make()
                ->title(trans('filament-translations::translation.gpt_scan_notifications_done'))
                ->success()
                ->sendToDatabase($user);
        }
    }
}
