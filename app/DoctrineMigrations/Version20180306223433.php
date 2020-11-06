<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180306223433 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE budget
            ADD account_id int(11) NOT NULL"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
