<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240524144718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dette_fournisseur ADD commentaire LONGTEXT DEFAULT NULL, DROP montant_avance');
        $this->addSql('ALTER TABLE fournisseur DROP commentaire');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dette_fournisseur ADD montant_avance VARCHAR(255) DEFAULT NULL, DROP commentaire');
        $this->addSql('ALTER TABLE fournisseur ADD commentaire LONGTEXT DEFAULT NULL');
    }
}
