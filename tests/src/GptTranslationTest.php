<?php

use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use TomatoPHP\FilamentTranslations\Facade\FilamentTranslations;
use TomatoPHP\FilamentTranslations\Filament\Resources\Translations\Pages\ManageTranslations;
use TomatoPHP\FilamentTranslations\FilamentTranslationsServiceProvider;
use TomatoPHP\FilamentTranslationsGpt\Jobs\ScanWithGPT;
use TomatoPHP\FilamentTranslationsGpt\Tests\Models\Translation;
use TomatoPHP\FilamentTranslationsGpt\Tests\Models\User;
use TomatoPHP\FilamentTranslationsGpt\Tests\Policies\ReadOnlyTranslationPolicy;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);

    NotificationFacade::fake();
});

/**
 * Answers like the chat completions API: the same JSON object, every value prefixed with the locale,
 * wrapped in a markdown code block the way some models do.
 */
function fakeChatCompletions(string $prefix): void
{
    Http::fake([
        '*/chat/completions' => function (Request $request) use ($prefix) {
            $texts = json_decode($request['messages'][2]['content'], true);
            $translated = array_map(fn ($text) => "[{$prefix}] {$text}", $texts);

            return Http::response([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => "```json\n" . json_encode($translated) . "\n```"]],
                ],
            ]);
        },
    ]);
}

it('translates each translation by id, even when two groups share a key', function () {
    fakeChatCompletions('ar');

    $first = Translation::factory()->create(['group' => 'auth', 'key' => 'title', 'text' => ['en' => 'Sign in']]);
    $second = Translation::factory()->create(['group' => 'shop', 'key' => 'title', 'text' => ['en' => 'Products']]);

    (new ScanWithGPT('ar', $this->user->id, $this->user::class))->handle();

    expect($first->refresh()->text)->toMatchArray(['en' => 'Sign in', 'ar' => '[ar] Sign in'])
        ->and($second->refresh()->text)->toMatchArray(['en' => 'Products', 'ar' => '[ar] Products']);

    NotificationFacade::assertSentTo($this->user, DatabaseNotification::class);
});

it('calls the configured API with the key, model and target language', function () {
    config()->set('filament-translations-gpt.openai_client.base_url', 'https://api.groq.com/openai/v1');
    config()->set('filament-translations-gpt.openai_client.model', 'llama-3.1-8b-instant');
    fakeChatCompletions('fr');

    Translation::factory()->create(['text' => ['en' => 'Hello :name']]);

    (new ScanWithGPT('fr', $this->user->id, $this->user::class))->handle();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'llama-3.1-8b-instant'
        && str_contains($request['messages'][1]['content'], 'French'));
});

it('accepts the language label that older versions sent', function () {
    fakeChatCompletions('ar');

    $line = Translation::factory()->create(['text' => ['en' => 'Hello']]);

    (new ScanWithGPT('Arabic', $this->user->id, $this->user::class))->handle();

    expect($line->refresh()->text['ar'])->toBe('[ar] Hello');
});

it('sends nothing to the API when no key is set', function () {
    config()->set('filament-translations-gpt.openai_client.api_key', null);
    Http::fake();

    $line = Translation::factory()->create(['text' => ['en' => 'Hello']]);

    (new ScanWithGPT('ar', $this->user->id, $this->user::class))->handle();

    Http::assertNothingSent();
    expect($line->refresh()->text)->not->toHaveKey('ar');
    NotificationFacade::assertSentTo($this->user, DatabaseNotification::class);
});

it('notifies the user and fails the job when the API returns an error', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'Invalid API key']], 401)]);

    $line = Translation::factory()->create(['text' => ['en' => 'Hello']]);

    expect(fn () => (new ScanWithGPT('ar', $this->user->id, $this->user::class))->handle())
        ->toThrow(RuntimeException::class);

    expect($line->refresh()->text)->not->toHaveKey('ar');
    NotificationFacade::assertSentTo($this->user, DatabaseNotification::class);
});

it('queues the GPT scan with the chosen locale from the translations page', function () {
    Queue::fake();

    livewire(ManageTranslations::class)
        ->callAction('gpt', data: ['language' => 'ar'])
        ->assertHasNoActionErrors();

    Queue::assertPushed(
        ScanWithGPT::class,
        fn (ScanWithGPT $job) => $job->language === 'ar' && $job->userId === $this->user->id && $job->userType === User::class,
    );
});

it('hides the GPT action when the policy does not allow creating translations', function () {
    config()->set('filament-translations.policy', ReadOnlyTranslationPolicy::class);
    app()->getProvider(FilamentTranslationsServiceProvider::class)->boot();

    livewire(ManageTranslations::class)->assertActionHidden('gpt');
});

it('shows the GPT action without a policy', function () {
    livewire(ManageTranslations::class)->assertActionVisible('gpt');
});

it('registers the GPT action once when the plugin boots on every request (Octane)', function () {
    $panel = Filament::getPanel('admin');

    foreach (range(1, 3) as $ignored) {
        $panel->getPlugin('filament-translations-gpt')->boot($panel);
    }

    $names = collect(FilamentTranslations::getActions(ManageTranslations::class))->map(fn ($action) => $action->getName());

    expect($names->filter(fn ($name) => $name === 'gpt'))->toHaveCount(1);
});

it('runs the install command', function () {
    artisan('filament-translations-gpt:install')->assertSuccessful();
});
