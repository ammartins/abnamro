<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180316103631 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE imported ADD createddAt DATETIME NOT NULL;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
