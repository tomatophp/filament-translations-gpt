<?php

namespace TomatoPHP\FilamentTranslationsGpt\Filament\Actions;

use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use TomatoPHP\FilamentTranslations\Filament\Resources\Translations\TranslationResource;
use TomatoPHP\FilamentTranslationsGpt\Jobs\ScanWithGPT;

class GPTTranslationAction
{
    public static function make(): Actions\Action
    {
        return Actions\Action::make('gpt')
            ->requiresConfirmation()
            ->authorize(fn (): bool => (config('filament-translations.translation_resource') ?: TranslationResource::class)::canCreate())
            ->icon('heroicon-o-light-bulb')
            ->hiddenLabel()
            ->tooltip(trans('filament-translations::translation.gpt_scan'))
            ->schema([
                Select::make('language')
                    ->searchable()
                    ->options(
                        collect(config('filament-translations.locals'))->mapWithKeys(function (array $item, string $key): array {
                            return [$key => $item['label']];
                        })->toArray()
                    )
                    ->label(trans('filament-translations::translation.gpt_scan_language'))
                    ->required(),
            ])
            ->action(function (array $data) {
                dispatch(new ScanWithGPT($data['language'], auth()->id(), auth()->user()::class));

                Notification::make()
                    ->title(trans('filament-translations::translation.gpt_scan_notification_start'))
                    ->success()
                    ->send();
            })
            ->color('warning')
            ->label(trans('filament-translations::translation.gpt_scan'));
    }
}
