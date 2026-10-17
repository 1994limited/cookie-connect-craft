<?php

namespace nineteenninetyfour\cookieconnect;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use nineteenninetyfour\cookieconnect\models\Settings;
use nineteenninetyfour\cookieconnect\services\Output;
use nineteenninetyfour\cookieconnect\services\SiteSettings;
use nineteenninetyfour\cookieconnect\twig\Extension;
use nineteenninetyfour\cookieconnect\variables\CookieConnectVariable;
use yii\base\Event;

/**
 * Cookie Connect for Craft CMS: connects the site to its cookie consent
 * platform. The platform, its ID and the Google tags are set per site in
 * Cookie Connect's own section (kept in the database, so they can be
 * changed on a live site); where it runs and how it's put into pages are
 * the plugin's settings (Project Config).
 *
 * @property-read SiteSettings $siteSettings
 * @property-read Output $output
 *
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public const HANDLE = 'cookie-connect';

    /** Changing the platform, its ID and the scripts. */
    public const PERMISSION = 'cookie-connect:manage';

    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    public bool $hasCpSection = false;

    public static function config(): array
    {
        return [
            'components' => [
                'siteSettings' => SiteSettings::class,
                'output' => Output::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::$app->getView()->registerTwigExtension(new Extension);

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            $event->sender->set('cookieConnect', CookieConnectVariable::class);
        });

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function (RegisterUserPermissionsEvent $event) {
            $event->permissions[] = [
                'heading' => 'Cookie Connect',
                'permissions' => [
                    self::PERMISSION => ['label' => Craft::t(self::HANDLE, 'Choose the consent platform and edit Cookie Connect’s settings for each site')],
                ],
            ];
        });

        // Into every front-end page, after its templates have rendered. Blitz
        // saves the page later (when the response is prepared), so cached
        // pages have it too.
        Event::on(View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
            if ($event->templateMode === View::TEMPLATE_MODE_SITE) {
                $event->output = $this->output->injectInto($event->output);
            }
        });

        Craft::$app->onInit(function () {
            if (Craft::$app->getRequest()->getIsCpRequest()) {
                $this->registerCp();
            }
        });
    }

    public function getCpNavItem(): ?array
    {
        if (! Craft::$app->getUser()->checkPermission(self::PERMISSION)) {
            return null;
        }

        return [
            'url' => self::HANDLE,
            'label' => Craft::t(self::HANDLE, 'Cookie Connect'),
            'icon' => __DIR__.'/icon-mask.svg',
        ];
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings;
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('cookie-connect/_cp/plugin-settings', [
            'settings' => $this->getSettings(),
            'overridden' => array_keys(Craft::$app->getConfig()->getConfigFromFile(self::HANDLE)),
            'environment' => Craft::$app->env,
        ]);
    }

    private function registerCp(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules[self::HANDLE] = 'cookie-connect/sites/edit';
            $event->rules[self::HANDLE.'/<site:[a-zA-Z0-9_-]+>'] = 'cookie-connect/sites/edit';
        });

        // Nav items are shown to whoever has the permission, so add ours here.
        Event::on(\craft\web\twig\variables\Cp::class, \craft\web\twig\variables\Cp::EVENT_REGISTER_CP_NAV_ITEMS, function (\craft\events\RegisterCpNavItemsEvent $event) {
            if ($item = $this->getCpNavItem()) {
                $event->navItems[] = $item;
            }
        });
    }

    /**
     * The URL of a site's settings in the control panel.
     */
    public static function cpUrl(?string $site = null): string
    {
        return UrlHelper::cpUrl($site === null ? self::HANDLE : self::HANDLE.'/'.$site);
    }
}
