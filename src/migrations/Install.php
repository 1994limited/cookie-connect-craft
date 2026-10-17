<?php

namespace nineteenninetyfour\cookieconnect\migrations;

use craft\db\Migration;
use nineteenninetyfour\cookieconnect\services\SiteSettings;

/**
 * Each site's consent platform settings, kept in the database so editors can
 * change them on a live site.
 */
class Install extends Migration
{
    public function safeUp(): bool
    {
        if (! $this->db->tableExists(SiteSettings::TABLE)) {
            $this->createTable(SiteSettings::TABLE, [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'settings' => $this->text()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, SiteSettings::TABLE, ['siteId'], true);
            $this->addForeignKey(null, SiteSettings::TABLE, ['siteId'], '{{%sites}}', ['id'], 'CASCADE');
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists(SiteSettings::TABLE);

        return true;
    }
}
