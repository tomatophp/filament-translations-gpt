# V5.0.0

- upgrade to Filament v5 and Livewire 4 (Laravel 12 and 13, PHP 8.2+), requires `tomatophp/filament-translations` ^5.0
- fix the job crashing on API errors (missing `Log` import); it now notifies the user and fails the job
- nothing is sent to the API when `OPENAI_API_KEY` is not set; the user is notified instead
- translations are sent and saved by id, so the same key in two groups no longer gets the same text
- one completion notification per scan instead of one per chunk
- accept answers wrapped in a markdown code block
- configure the client with `OPENAI_BASE_URL`, `OPENAI_API_KEY`, `OPENAI_ORGANIZATION` and `OPENAI_MODEL` (default `gpt-4o-mini`), plus `chunk_size`
- the language select sends the locale key (the label sent by older versions is still accepted)
- the GPT action respects the translation policy (`create`) and is registered once under Laravel Octane
- `filament-translations-gpt:install` runs in-process
- add tests for the job (with a faked API), the action, the policy and the install command

# V1.0.0

First release of the package
