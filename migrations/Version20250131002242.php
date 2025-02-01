<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131002242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review ADD reviwed_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C689C1C933 FOREIGN KEY (reviwed_user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_794381C689C1C933 ON review (reviwed_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C689C1C933');
        $this->addSql('DROP INDEX IDX_794381C689C1C933 ON review');
        $this->addSql('ALTER TABLE review DROP reviwed_user_id');
    }
}
