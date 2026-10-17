<?php

namespace nineteenninetyfour\cookieconnect\models;

use craft\base\Model;

/**
 * The plugin's own settings (Project Config), and what config/cookie-connect.php
 * can set. Each site's platform and IDs are kept separately (SiteSettings).
 */
class Settings extends Model
{
    /**
     * The environments (CRAFT_ENVIRONMENT) Cookie Connect loads the consent
     * platform in. '*' for all of them.
     *
     * @var list<string>|string
     */
    public array|string $environments = ['production'];

    /** Elsewhere: "block" (keep consent-gated content blocked), "run" (run it as if accepted) or "omit" (leave it out). */
    public string $whenOff = 'block';

    /** Put Cookie Connect into every front-end page by itself. Off: use consentHead() in your layout. */
    public bool $autoInsert = true;

    /** Log each consent reading in the browser console. */
    public bool $debug = false;

    /**
     * Config file only: per-site settings that override the control panel,
     * keyed by site handle, or '*' for every site.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $sites = [];

    /**
     * Config file only: a function returning the CSP nonce for this request,
     * or null.
     *
     * @var (callable(): ?string)|null
     */
    public mixed $nonce = null;

    public function fields(): array
    {
        // Only what can be saved to Project Config.
        return ['environments', 'whenOff', 'autoInsert', 'debug'];
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (isset($values['environments']) && is_string($values['environments']) && $values['environments'] !== '*') {
            $values['environments'] = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $values['environments']) ?: [])));
        }

        parent::setAttributes($values, $safeOnly);
    }

    protected function defineRules(): array
    {
        return [
            [['whenOff'], 'in', 'range' => ['block', 'run', 'omit']],
            [['autoInsert', 'debug'], 'boolean'],
            [['environments', 'sites', 'nonce'], 'safe'],
        ];
    }

    /**
     * Whether the consent platform loads in this environment.
     */
    public function runsIn(?string $environment): bool
    {
        if ($this->environments === '*' || $this->environments === ['*']) {
            return true;
        }

        return in_array($environment ?? 'production', (array) $this->environments, true);
    }
}
