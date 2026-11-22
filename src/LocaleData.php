<?php

declare(strict_types=1);

namespace LaravelLang\LocaleNames;

final readonly class LocaleData
{
    public function __construct(
        public string $code,
        public string $alias,
        public string $native,
        public string $localized,
    ) {}
}
