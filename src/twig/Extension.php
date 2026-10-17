<?php

namespace nineteenninetyfour\cookieconnect\twig;

use nineteenninetyfour\cookieconnect\Plugin;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The consent tag, functions and filters. Every one works the same whichever
 * consent platform the site uses.
 */
class Extension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [new ConsentTokenParser];
    }

    public function getFunctions(): array
    {
        $safe = ['is_safe' => ['html']];

        return [
            new TwigFunction('consent', fn (string|array $categories, mixed $html, array $options = []) => self::gate((string) $html, $categories, $options), $safe),
            new TwigFunction('consentHead', fn () => self::markup(self::output()->renderer()->head()), $safe),
            new TwigFunction('consentBodyStart', fn () => self::markup(self::output()->renderer()->bodyStart()), $safe),
            new TwigFunction('consentBodyEnd', fn () => self::markup(self::output()->renderer()->bodyEnd()), $safe),
            new TwigFunction('consentDeclaration', fn () => self::markup(self::output()->renderer()->declaration()), $safe),
            new TwigFunction('consentSettingsLink', fn (?string $text = null, array $attributes = [], string $tag = 'button') => self::markup(
                self::output()->renderer()->settingsLink($text ?? self::output()->settingsText(), $attributes, $tag),
            ), $safe),
            new TwigFunction('consentPlaceholder', fn (string|array $categories, ?string $text = null, ?string $button = null) => self::markup(
                self::output()->renderer()->placeholder($categories, $text ?? self::output()->placeholderText($categories), $button ?? self::output()->settingsText()),
            ), $safe),
            new TwigFunction('consentCspHashes', fn () => self::output()->renderer()->cspHashes()),
        ];
    }

    public function getFilters(): array
    {
        $safe = ['is_safe' => ['html']];

        return [
            new TwigFilter('consent', fn (mixed $html, string|array $categories, array $options = []) => self::gate((string) $html, $categories, $options), $safe),
            new TwigFilter('consentTokens', fn (mixed $html) => self::markup(self::output()->renderer()->replaceTokens((string) $html, self::output()->settingsText())), $safe),
        ];
    }

    /**
     * What the consent tag calls.
     *
     * @param  string|list<string>  $categories
     * @param  array<string, mixed>|null  $options
     */
    public static function gate(string $html, string|array $categories, ?array $options = []): Markup
    {
        return self::markup(self::output()->gate($html, $categories, $options ?? []));
    }

    private static function output(): \nineteenninetyfour\cookieconnect\services\Output
    {
        return Plugin::getInstance()->output;
    }

    private static function markup(string $html): Markup
    {
        return new Markup($html, 'UTF-8');
    }
}
