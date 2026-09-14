![Screenshot](https://raw.githubusercontent.com/tomatophp/filament-translations-gpt/master/arts/fadymondy-tomato-translations-gpt.jpg)

# Filament Translations GPT

[![Dependabot Updates](https://github.com/tomatophp/filament-translations-gpt/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/tomatophp/filament-translations-gpt/actions/workflows/dependabot/dependabot-updates)
[![PHP Code Styling](https://github.com/tomatophp/filament-translations-gpt/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/tomatophp/filament-translations-gpt/actions/workflows/fix-php-code-styling.yml)
[![Tests](https://github.com/tomatophp/filament-translations-gpt/actions/workflows/tests.yml/badge.svg)](https://github.com/tomatophp/filament-translations-gpt/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/tomatophp/filament-translations-gpt/version.svg)](https://packagist.org/packages/tomatophp/filament-translations-gpt)
[![License](https://poser.pugx.org/tomatophp/filament-translations-gpt/license.svg)](https://packagist.org/packages/tomatophp/filament-translations-gpt)
[![Downloads](https://poser.pugx.org/tomatophp/filament-translations-gpt/d/total.svg)](https://packagist.org/packages/tomatophp/filament-translations-gpt)

Translations Manager extension to use ChatGPT openAI to auto translate your __(), trans() fn

## Version Compatibility

| Plugin | Filament | Laravel | PHP |
|--------|----------|---------|-----|
| 1.x | 3.x | 10.x / 11.x | 8.1+ |
| 4.x ([`v4` branch](https://github.com/tomatophp/filament-translations-gpt/tree/v4)) | 4.x | 11.x / 12.x | 8.2+ |
| 5.x | 5.x | 12.x / 13.x | 8.2+ |

## Screenshots

![GPT Action](https://raw.githubusercontent.com/tomatophp/filament-translations-gpt/master/arts/gpt-action.png)
![GPT Modal](https://raw.githubusercontent.com/tomatophp/filament-translations-gpt/master/arts/gpt-modal.png)

## Installation

before install this package you need to have [Translation Manager](https://www.github.com/tomatophp/filament-translations) installed and configured

```bash
composer require tomatophp/filament-translations-gpt
```
after install your package please run this command

```bash
php artisan filament-translations-gpt:install
```

finally register the plugin on `/app/Providers/Filament/AdminPanelProvider.php`

```php
->plugin(\TomatoPHP\FilamentTranslationsGpt\FilamentTranslationsGptPlugin::make())
```

## Usage

now you need to add the following to your `.env` file:

```bash
OPENAI_API_KEY=
OPENAI_ORGANIZATION=
# optional
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

Any OpenAI compatible chat completions API works, for example Groq:

```bash
OPENAI_BASE_URL=https://api.groq.com/openai/v1
OPENAI_MODEL=llama-3.1-8b-instant
```

now you need to clear you cache

```bash
php artisan config:clear
```

Click the GPT button on the translations page and pick a language. A queued job sends the English text of your translations to the API in chunks
(`chunk_size` in the config, 50 by default), saves the answers and notifies you when it is done. Run a queue worker (`php artisan queue:work`) for the job to run.
Words prefixed with `:` are kept as placeholders.

Without `OPENAI_API_KEY` nothing is sent: the job only notifies you that the key is missing. If the API answers with an error, you are notified and the job fails.

The action follows the translation policy of the Translation Manager: it needs the `create` ability.

## Publish Assets

you can publish config file by use this command

```bash
php artisan vendor:publish --tag="filament-translations-gpt-config"
```

you can publish languages file by use this command

```bash
php artisan vendor:publish --tag="filament-translations-gpt-lang"
```

## Testing

if you like to run `PEST` testing just use this command

```bash
composer test
```

## Code Style

if you like to fix the code style just use this command

```bash
composer format
```

## PHPStan

if you like to check the code by `PHPStan` just use this command

```bash
composer analyse
```

## Other Filament Packages

Checkout our [Awesome TomatoPHP](https://github.com/tomatophp/awesome)
