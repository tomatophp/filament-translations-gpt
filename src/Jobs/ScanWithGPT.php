<?php

namespace TomatoPHP\FilamentTranslationsGpt\Jobs;

use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use TomatoPHP\FilamentTranslations\Models\Translation;

class ScanWithGPT implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $language  a locale key of `filament-translations.locals` (its label is accepted too)
     */
    public function __construct(
        public string $language,
        public int | string $userId,
        public string $userType
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->userType::find($this->userId);

        $apiKey = config('filament-translations-gpt.openai_client.api_key');

        // Nothing is sent to the API without a key.
        if (blank($apiKey)) {
            $this->notifyFailure($user, 'OPENAI_API_KEY is not set.');

            return;
        }

        [$locale, $languageName] = $this->resolveLocale();

        Translation::query()->chunkById((int) config('filament-translations-gpt.chunk_size', 50), function (Collection $translations) use ($apiKey, $locale, $languageName, $user): void {
            // Keyed by id: the same key can exist in more than one group.
            $texts = $translations->mapWithKeys(fn (Translation $translation): array => [
                $translation->id => $translation->text['en'] ?? $translation->key,
            ])->all();

            $translated = $this->translate($texts, $languageName, $apiKey, $user);

            foreach ($translations as $translation) {
                $value = $translated[$translation->id] ?? null;

                if (is_string($value) && filled($value)) {
                    $translation->setTranslation($locale, $value);
                    $translation->save();
                }
            }
        });

        Notification::make()
            ->title(trans('filament-translations::translation.gpt_scan_notifications_done'))
            ->success()
            ->sendToDatabase($user);
    }

    /**
     * @param  array<int|string, string>  $texts
     * @return array<int|string, mixed>
     */
    protected function translate(array $texts, string $languageName, string $apiKey, ?Model $user): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if (filled(config('filament-translations-gpt.openai_client.organization'))) {
            $headers['OpenAI-Organization'] = config('filament-translations-gpt.openai_client.organization');
        }

        $response = Http::withToken($apiKey)
            ->withHeaders($headers)
            ->baseUrl(config('filament-translations-gpt.openai_client.base_url') ?: 'https://api.openai.com/v1')
            ->post('/chat/completions', [
                'model' => config('filament-translations-gpt.openai_client.model') ?: 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a translator. Translate the values of the JSON object you receive and return only the JSON object, with the same keys.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Translate the values of the following JSON object from English to ' . $languageName . ". Keep the keys unchanged and return only the JSON object, without added quotes or any other extraneous details. Importantly, any word prefixed with the symbol ':' should remain unchanged",
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($texts, JSON_UNESCAPED_UNICODE),
                    ],
                ],
                'temperature' => 0.4,
                'n' => 1,
            ]);

        $content = $response->json('choices.0.message.content');

        if (! $response->successful() || ! is_string($content)) {
            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->notifyFailure($user, 'HTTP ' . $response->status());

            throw new RuntimeException('Failed to get translation from OpenAI: ' . $response->body());
        }

        // Some models wrap the JSON in a markdown code block.
        $content = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($content)));

        return json_decode($content, true) ?: [];
    }

    /**
     * @return array{0: string, 1: string} the locale key and the language name used in the prompt
     */
    protected function resolveLocale(): array
    {
        $locales = config('filament-translations.locals', []);

        $locale = array_key_exists($this->language, $locales)
            ? $this->language
            : collect($locales)->search(fn (array $item): bool => ($item['label'] ?? null) === $this->language);

        $locale = $locale === false ? $this->language : $locale;

        return [$locale, $locales[$locale]['label'] ?? $locale];
    }

    protected function notifyFailure(?Model $user, string $reason): void
    {
        if (! $user) {
            return;
        }

        Notification::make()
            ->title(trans('filament-translations::translation.gpt_scan_notification_error'))
            ->body($reason)
            ->danger()
            ->sendToDatabase($user);
    }
}
