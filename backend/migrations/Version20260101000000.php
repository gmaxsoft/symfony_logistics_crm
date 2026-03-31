<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create parcels table with UUID primary key and workflow status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE parcels (
            id UUID NOT NULL,
            tracking_number VARCHAR(32) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'draft\',
            sender_address VARCHAR(500) NOT NULL,
            receiver_address VARCHAR(500) NOT NULL,
            weight NUMERIC(10, 3) NOT NULL,
            sender_latitude NUMERIC(10, 7) DEFAULT NULL,
            sender_longitude NUMERIC(10, 7) DEFAULT NULL,
            receiver_latitude NUMERIC(10, 7) DEFAULT NULL,
            receiver_longitude NUMERIC(10, 7) DEFAULT NULL,
            courier_name VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_parcels_tracking_number ON parcels (tracking_number)');
        $this->addSql('CREATE INDEX IDX_parcels_status ON parcels (status)');
        $this->addSql('CREATE INDEX IDX_parcels_courier ON parcels (courier_name)');
        $this->addSql('CREATE INDEX IDX_parcels_created_at ON parcels (created_at)');

        $this->addSql('COMMENT ON COLUMN parcels.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN parcels.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN parcels.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN parcels.delivered_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE parcels');
    }
}
