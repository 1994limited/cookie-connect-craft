<?php

namespace nineteenninetyfour\cookieconnect\controllers;

use Craft;
use craft\models\Site;
use craft\web\Controller;
use NineteenNinetyFour\CookieConnect\Core\Category;
use NineteenNinetyFour\CookieConnect\Core\Output\Runtime;
use NineteenNinetyFour\CookieConnect\Core\Providers\Providers;
use NineteenNinetyFour\CookieConnect\Core\Settings\Script;
use NineteenNinetyFour\CookieConnect\Core\Settings\Validator;
use nineteenninetyfour\cookieconnect\integrations\Seomatic;
use nineteenninetyfour\cookieconnect\Plugin;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Cookie Connect's section: one page per site.
 */
class SitesController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requirePermission(Plugin::PERMISSION);

        return parent::beforeAction($action);
    }

    public function actionEdit(?string $site = null): Response
    {
        $site = $this->site($site);
        $plugin = Plugin::getInstance();
        $settings = $plugin->siteSettings->forSite($site);
        $renderer = $plugin->output->renderer($site);
        $providers = Providers::default();

        return $this->renderTemplate('cookie-connect/_cp/site', [
            'title' => Craft::t(Plugin::HANDLE, 'Cookie Connect'),
            'site' => $site,
            'sites' => Craft::$app->getSites()->getAllSites(),
            'settings' => $settings,
            'renderer' => $renderer,
            'state' => $renderer->state(),
            'problems' => (new Validator($providers))->problems($settings),
            'providers' => $providers->all(),
            'provider' => $renderer->provider(),
            'overridden' => $plugin->siteSettings->overridden($site),
            'categories' => Category::cases(),
            'placements' => Script::PLACEMENTS,
            'preview' => str_replace(Runtime::source(), '/* '.Craft::t(Plugin::HANDLE, 'Cookie Connect’s script').' */', $renderer->head()),
            'environment' => Craft::$app->env ?? 'production',
            'seomaticTags' => Seomatic::googleTags($site),
            'pluginSettings' => $plugin->getSettings(),
            'canChangePluginSettings' => Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $site = Craft::$app->getSites()->getSiteById((int) $request->getRequiredBodyParam('siteId'));

        if ($site === null) {
            throw new NotFoundHttpException('No such site.');
        }

        $posted = (array) $request->getBodyParam('settings', []);
        $provider = (string) ($posted['provider'] ?? '');

        $data = [
            'provider' => $provider,
            'values' => (array) ($posted['providerValues'][$provider] ?? []),
            'categoryMap' => (array) ($posted['categoryMap'] ?? []),
            'google' => (array) ($posted['google'] ?? []),
            'scripts' => array_values(array_map(fn ($row) => [
                'name' => $row['name'] ?? '',
                'categories' => [$row['category'] ?? 'marketing'],
                'placement' => $row['placement'] ?? 'head',
                'code' => $row['code'] ?? '',
                'enabled' => $row['enabled'] ?? true,
            ], array_filter((array) ($posted['scripts'] ?? []), 'is_array'))),
        ];

        $problems = Plugin::getInstance()->siteSettings->save($site, $data);

        if ($problems === []) {
            $this->setSuccessFlash(Craft::t(Plugin::HANDLE, 'Cookie Connect saved.'));
        } else {
            $this->setFailFlash(Craft::t(Plugin::HANDLE, 'Saved, but something needs fixing before Cookie Connect goes live.'));
        }

        return $this->redirect(Plugin::cpUrl($site->handle));
    }

    private function site(?string $handle): Site
    {
        $sites = Craft::$app->getSites();
        // Cookie Connect's permission is enough: these aren't content edits.
        $site = $handle === null ? $sites->getPrimarySite() : $sites->getSiteByHandle($handle);

        if ($site === null) {
            throw new NotFoundHttpException('No such site.');
        }

        return $site;
    }
}
