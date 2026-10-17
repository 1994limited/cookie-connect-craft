<?php

namespace nineteenninetyfour\cookieconnect\services;

use Craft;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\Site;
use NineteenNinetyFour\CookieConnect\Core\Providers\Providers;
use NineteenNinetyFour\CookieConnect\Core\Settings\Settings as CoreSettings;
use NineteenNinetyFour\CookieConnect\Core\Settings\Validator;
use nineteenninetyfour\cookieconnect\Plugin;
use yii\base\Component;

/**
 * Each site's consent platform, its values, the Google tags and the
 * control panel's scripts: what editors saved, with config/cookie-connect.php
 * laid over it.
 */
class SiteSettings extends Component
{
    public const TABLE = '{{%cookieconnect_sites}}';

    /** @var array<int, array<string, mixed>> */
    private array $stored = [];

    /**
     * What the control panel saved for a site.
     *
     * @return array<string, mixed>
     */
    public function stored(Site $site): array
    {
        if (! array_key_exists((int) $site->id, $this->stored)) {
            $json = (new Query)->select('settings')->from(self::TABLE)->where(['siteId' => $site->id])->scalar();
            $this->stored[(int) $site->id] = is_string($json) ? (array) Json::decodeIfJson($json) : [];
        }

        return $this->stored[(int) $site->id];
    }

    /**
     * What config/cookie-connect.php sets for a site ('*', then the site's
     * own handle), with $ENV_VAR values resolved.
     *
     * @return array<string, mixed>
     */
    public function overrides(Site $site): array
    {
        $sites = Plugin::getInstance()->getSettings()->sites;
        $overrides = array_replace_recursive(
            is_array($sites['*'] ?? null) ? $sites['*'] : [],
            is_array($sites[$site->handle] ?? null) ? $sites[$site->handle] : [],
        );

        array_walk_recursive($overrides, function (&$value) {
            if (is_string($value)) {
                $value = App::parseEnv($value);
            }
        });

        return $overrides;
    }

    /**
     * The dotted paths config sets for a site ("provider", "values.cbid",
     * "google.gtmId"), so the control panel can show them as fixed.
     *
     * @return list<string>
     */
    public function overridden(Site $site): array
    {
        return $this->paths($this->overrides($site));
    }

    /**
     * Everything core needs to render a site.
     */
    public function forSite(Site $site): CoreSettings
    {
        $plugin = Plugin::getInstance()->getSettings();
        $data = array_replace_recursive($this->stored($site), $this->overrides($site));

        if (array_key_exists('scripts', $this->overrides($site))) {
            $data['scripts'] = $this->overrides($site)['scripts'];
        }

        return CoreSettings::fromArray([
            ...$data,
            'active' => $data['active'] ?? $plugin->runsIn(Craft::$app->env),
            'whenOff' => $data['whenOff'] ?? $plugin->whenOff,
            'debug' => $data['debug'] ?? $plugin->debug,
        ]);
    }

    /**
     * Cleans up and saves what was posted for a site. Returns the problems
     * (keyed by dotted path), which are saved anyway so nothing typed is
     * lost; the site isn't live until they're fixed.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function save(Site $site, array $data): array
    {
        $validator = new Validator(Providers::default());
        $settings = $validator->normalise(CoreSettings::fromArray($data));
        $saved = array_intersect_key($settings->toArray(), array_flip(['provider', 'values', 'categoryMap', 'google', 'scripts']));

        Db::upsert(self::TABLE, [
            'siteId' => $site->id,
            'settings' => Json::encode($saved),
        ]);

        unset($this->stored[(int) $site->id]);
        Plugin::getInstance()->output->forget();

        return $validator->problems($this->forSite($site));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function paths(array $data, string $prefix = ''): array
    {
        $paths = [];

        foreach ($data as $key => $value) {
            $path = $prefix.$key;
            $paths[] = $path;

            if (is_array($value) && ! array_is_list($value) && in_array($key, ['values', 'google', 'categoryMap'], true)) {
                array_push($paths, ...$this->paths($value, $path.'.'));
            }
        }

        return $paths;
    }
}
