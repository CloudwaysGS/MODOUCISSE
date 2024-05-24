<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240524144145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC278510D4DE');
        $this->addSql('ALTER TABLE entree_depot DROP FOREIGN KEY FK_D59AA5E6F347EFB');
        $this->addSql('ALTER TABLE entree_depot DROP FOREIGN KEY FK_D59AA5E68510D4DE');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2A76ED395');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2F347EFB');
        $this->addSql('ALTER TABLE sortie_depot DROP FOREIGN KEY FK_DE9A2EB88510D4DE');
        $this->addSql('ALTER TABLE sortie_depot DROP FOREIGN KEY FK_DE9A2EB8F347EFB');
        $this->addSql('DROP TABLE depot');
        $this->addSql('DROP TABLE entree_depot');
        $this->addSql('DROP TABLE sortie');
        $this->addSql('DROP TABLE sortie_depot');
        $this->addSql('ALTER TABLE fournisseur ADD commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_29A5EC278510D4DE ON produit');
        $this->addSql('ALTER TABLE produit DROP depot_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE depot (id INT AUTO_INCREMENT NOT NULL, stock DOUBLE PRECISION NOT NULL, date DATETIME NOT NULL, libelle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE entree_depot (id INT AUTO_INCREMENT NOT NULL, produit_id INT DEFAULT NULL, depot_id INT DEFAULT NULL, qt_entree NUMERIC(10, 0) NOT NULL, libelle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, release_date DATE DEFAULT NULL, INDEX IDX_D59AA5E6F347EFB (produit_id), INDEX IDX_D59AA5E68510D4DE (depot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sortie (id INT AUTO_INCREMENT NOT NULL, produit_id INT DEFAULT NULL, user_id INT DEFAULT NULL, date_sortie DATE NOT NULL, qt_sortie NUMERIC(10, 0) NOT NULL, prix_unit NUMERIC(10, 0) NOT NULL, total NUMERIC(10, 0) NOT NULL, INDEX IDX_3C3FD3F2A76ED395 (user_id), INDEX IDX_3C3FD3F2F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sortie_depot (id INT AUTO_INCREMENT NOT NULL, depot_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, qt_sortie VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, libelle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, release_date DATE DEFAULT NULL, INDEX IDX_DE9A2EB88510D4DE (depot_id), INDEX IDX_DE9A2EB8F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE entree_depot ADD CONSTRAINT FK_D59AA5E6F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE entree_depot ADD CONSTRAINT FK_D59AA5E68510D4DE FOREIGN KEY (depot_id) REFERENCES depot (id)');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE sortie_depot ADD CONSTRAINT FK_DE9A2EB88510D4DE FOREIGN KEY (depot_id) REFERENCES depot (id)');
        $this->addSql('ALTER TABLE sortie_depot ADD CONSTRAINT FK_DE9A2EB8F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE fournisseur DROP commentaire');
        $this->addSql('ALTER TABLE produit ADD depot_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC278510D4DE FOREIGN KEY (depot_id) REFERENCES depot (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC278510D4DE ON produit (depot_id)');
    }
}
