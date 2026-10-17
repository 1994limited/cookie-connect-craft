<?php

namespace nineteenninetyfour\cookieconnect\twig;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * {% consent 'analytics' %}…{% endconsent %}
 * {% consent ['analytics', 'marketing'] %}…{% endconsent %}
 * {% consent 'marketing' with { placeholder: true } %}…{% endconsent %}
 */
class ConsentTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): ConsentNode
    {
        $stream = $this->parser->getStream();
        $categories = $this->parser->parseExpression();
        $options = null;

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $options = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(fn (Token $token) => $token->test('endconsent'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new ConsentNode($body, $categories, $options, $token->getLine());
    }

    public function getTag(): string
    {
        return 'consent';
    }
}
