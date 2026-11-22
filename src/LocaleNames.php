<?php

declare(strict_types=1);

namespace LaravelLang\LocaleNames;

use Illuminate\Support\Collection;
use LaravelLang\Config\Services\AliasLangConfig;
use LaravelLang\Exceptions\InvalidNativeNameDataException;
use LaravelLang\Filesystem\Filesystem;
use LaravelLang\LocaleList\Locale;
use SortDirection;

use function is_array;
use function is_string;

class LocaleNames
{
    protected string $default = '_native';

    public function __construct(
        protected AliasLangConfig $config,
        protected Filesystem $filesystem,
    ) {}

    /** @return Collection<string, LocaleData> */
    public function all(Locale|string|null $forLocale, ?SortDirection $direction = null): Collection
    {
        $items = [];

        foreach ($this->read($forLocale) as $locale => $item) {
            if (! is_string($locale)) {
                throw InvalidNativeNameDataException::invalidLocaleCode($locale, 'locale');
            }

            $items[$locale] = $this->mapRow($locale, $item);
        }

        return (new Collection($items))
            ->when($direction !== null, fn (Collection $items) => $items->sortBy(
                callback  : 'localized',
                descending: $direction === SortDirection::Descending
            ));
    }

    public function one(Locale|string|null $forLocale, Locale|string $locale): LocaleData
    {
        $normalized = $this->normalize($locale);

        $items = $this->read($forLocale);

        return $this->mapRow($normalized, $items[$normalized]);
    }

    /** @param array{native: string, localized: string} $item */
    protected function map(string $locale, array $item): LocaleData
    {
        return new LocaleData(
            code     : $locale,
            alias    : $this->config->to($locale) ?? $locale,
            native   : $item['native'],
            localized: $item['localized'],
        );
    }

    /** @return array<array-key, mixed> */
    protected function read(Locale|string|null $locale): array
    {
        $normalized = $this->normalize($locale);

        return $this->filesystem->fromJson(__DIR__ . "/../data/$normalized.json");
    }

    protected function normalize(Locale|string|null $locale): string
    {
        return $locale === $this->default
            ? $locale
            : $this->config->from($locale) ?? $this->default;
    }

    protected function mapRow(string $locale, mixed $item): LocaleData
    {
        if (
            ! is_array($item)
            || ! is_string($item['native'] ?? null)
            || ! is_string($item['localized'] ?? null)
        ) {
            throw InvalidNativeNameDataException::invalidRow($locale, 'locale');
        }

        return $this->map($locale, $item);
    }
}
