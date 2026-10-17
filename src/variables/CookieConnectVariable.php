<?php

namespace nineteenninetyfour\cookieconnect\variables;

use NineteenNinetyFour\CookieConnect\Core\Output\Renderer;
use NineteenNinetyFour\CookieConnect\Core\Providers\Provider;
use nineteenninetyfour\cookieconnect\Plugin;

/**
 * craft.cookieConnect, for templates that want to ask rather than print:
 *
 *     {% if craft.cookieConnect.live %}…{% endif %}
 *     {{ craft.cookieConnect.provider.name }}
 */
class CookieConnectVariable
{
    public function getRenderer(): Renderer
    {
        return Plugin::getInstance()->output->renderer();
    }

    /** "live", or why not: "off", "none", "unknown" or "incomplete". */
    public function getState(): string
    {
        return $this->getRenderer()->state();
    }

    public function getLive(): bool
    {
        return $this->getRenderer()->isLive();
    }

    public function getProvider(): ?Provider
    {
        return $this->getRenderer()->provider();
    }
}
