<?php

namespace nineteenninetyfour\cookieconnect\twig;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

#[YieldReady]
class ConsentNode extends Node implements NodeOutputInterface
{
    public function __construct(Node $body, AbstractExpression $categories, ?AbstractExpression $options, int $line)
    {
        $capture = new CaptureNode($body, $line);
        $capture->setAttribute('raw', true);

        $nodes = ['body' => $capture, 'categories' => $categories];

        if ($options !== null) {
            $nodes['options'] = $options;
        }

        parent::__construct($nodes, [], $line);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$__cookie_connect = ')
            ->subcompile($this->getNode('body'))
            ->raw("\n")
            ->write('yield \\nineteenninetyfour\\cookieconnect\\twig\\Extension::gate((string) $__cookie_connect, ')
            ->subcompile($this->getNode('categories'))
            ->raw(', ');

        $this->hasNode('options') ? $compiler->subcompile($this->getNode('options')) : $compiler->raw('[]');

        $compiler->raw(");\n");
    }
}
