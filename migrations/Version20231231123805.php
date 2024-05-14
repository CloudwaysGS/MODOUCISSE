<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231231123805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dette_chargement DROP FOREIGN KEY FK_77F386A5E11400A1');
        $this->addSql('ALTER TABLE dette_chargement DROP FOREIGN KEY FK_77F386A5B8FBE502');
        $this->addSql('DROP TABLE dette_chargement');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dette_chargement (dette_id INT NOT NULL, chargement_id INT NOT NULL, INDEX IDX_77F386A5E11400A1 (dette_id), INDEX IDX_77F386A5B8FBE502 (chargement_id), PRIMARY KEY(dette_id, chargement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE dette_chargement ADD CONSTRAINT FK_77F386A5E11400A1 FOREIGN KEY (dette_id) REFERENCES dette (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dette_chargement ADD CONSTRAINT FK_77F386A5B8FBE502 FOREIGN KEY (chargement_id) REFERENCES chargement (id) ON DELETE CASCADE');
    }
}
