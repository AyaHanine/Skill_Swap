<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130211645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C66C066AFE');
        $this->addSql('DROP INDEX IDX_794381C66C066AFE ON review');
        $this->addSql('ALTER TABLE review CHANGE target_user_id offer_id INT NOT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C653C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('CREATE INDEX IDX_794381C653C674EE ON review (offer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C653C674EE');
        $this->addSql('DROP INDEX IDX_794381C653C674EE ON review');
        $this->addSql('ALTER TABLE review CHANGE offer_id target_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C66C066AFE FOREIGN KEY (target_user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_794381C66C066AFE ON review (target_user_id)');
    }
}
