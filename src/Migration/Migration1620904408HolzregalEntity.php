<?php declare(strict_types=1);

namespace The13thHolzregal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1620904408HolzregalEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1620904408;
    }

    public function update(Connection $connection): void
    {
        $connection->executeUpdate("
            CREATE TABLE IF NOT EXISTS `holzregal_table_konfig` (
                `id` BINARY ( 16 ) NOT NULL,
                `name` VARCHAR ( 255 ) NOT NULL COMMENT 'Name',
                `custom_fields` json DEFAULT NULL,
                `created_at` datetime(3) NOT NULL,
                `updated_at` datetime(3) DEFAULT NULL,
                PRIMARY KEY ( `id` )
            ) ENGINE = INNODB DEFAULT CHARSET = utf8;
        ");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
