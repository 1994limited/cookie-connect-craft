<?php

namespace nineteenninetyfour\cookieconnect\integrations;

use Craft;
use craft\models\Site;
use Throwable;

/**
 * SEOmatic can add Google Tag Manager and gtag.js itself (Tracking Scripts).
 * Cookie Connect never adds a tag that's already on the page, so the two
 * don't load it twice; this tells editors in the control panel, so they keep
 * Google's tags in one place.
 */
class Seomatic
{
    /** SEOmatic's script handles for Google's tags, with the variable holding each ID. */
    public const SCRIPTS = [
        'googleTagManager' => ['label' => 'Google Tag Manager', 'id' => 'googleTagManagerId'],
        'gtag' => ['label' => 'Google gtag.js', 'id' => 'googleAnalyticsId'],
    ];

    /**
     * The Google tags SEOmatic adds on a site, as label => ID (or '' when
     * the ID isn't known). Empty when SEOmatic isn't installed or adds none.
     *
     * @return array<string, string>
     */
    public static function googleTags(Site $site): array
    {
        if (! Craft::$app->getPlugins()->isPluginEnabled('seomatic') || ! class_exists(\nystudio107\seomatic\Seomatic::class)) {
            return [];
        }

        $found = [];

        try {
            $seomatic = \nystudio107\seomatic\Seomatic::$plugin;
            $seomatic->metaContainers->loadMetaContainers('', $site->id);

            foreach (self::SCRIPTS as $key => $script) {
                $item = $seomatic->script->get($key);

                if ($item !== null && $item->include) {
                    $found[$script['label']] = (string) ($item->vars[$script['id']]['value'] ?? '');
                }
            }
        } catch (Throwable $e) {
            Craft::warning('Couldn’t read SEOmatic’s tracking scripts: '.$e->getMessage(), __METHOD__);
        }

        return $found;
    }
}
