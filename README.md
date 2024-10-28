![Screenshot](https://raw.githubusercontent.com/tomatophp/filament-translations-gpt/master/art/3x1io-tomato-translations-gpt.jpg)

# Filament Translations GPT

[![Latest Stable Version](https://poser.pugx.org/tomatophp/filament-translations-gpt/version.svg)](https://packagist.org/packages/tomatophp/filament-translations-gpt)
[![License](https://poser.pugx.org/tomatophp/filament-translations-gpt/license.svg)](https://packagist.org/packages/tomatophp/filament-translations-gpt)
[![Downloads](https://poser.pugx.org/tomatophp/filament-translations-gpt/d/total.svg)](https://packagist.org/packages/tomatophp/filament-translations-gpt)

Translations Manager extension to use ChatGPT openAI to auto translate your __(), trans() fn

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
```

now you need to clear you cache

```bash
php artisan config:clear
```

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
