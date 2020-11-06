<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171011214043 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up((Schema $schema) : void
    {
        $this->addSql(
            "ALTER TABLE transaction_type
            ADD company_logo VARCHAR(255) DEFAULT NULL"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down((Schema $schema) : void
    {
    }
}
