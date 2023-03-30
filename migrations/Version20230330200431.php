<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230330200431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add content, message and category.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(100) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_64C19C1DE12AB56 (created_by), INDEX IDX_64C19C116FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE content (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', updated_by BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, blocks LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', type VARCHAR(255) NOT NULL, published TINYINT(1) NOT NULL, visibility VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_FEC530A9989D9B62 (slug), INDEX IDX_FEC530A9DE12AB56 (created_by), INDEX IDX_FEC530A916FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE content_category (content_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', category_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_54FBF32E84A0A3ED (content_id), INDEX IDX_54FBF32E12469DE2 (category_id), PRIMARY KEY(content_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', content_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', profile_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', viewed TINYINT(1) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_B6BD307F84A0A3ED (content_id), INDEX IDX_B6BD307FCCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profile_category (profile_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', category_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_FF267870CCFA12B8 (profile_id), INDEX IDX_FF26787012469DE2 (category_id), PRIMARY KEY(profile_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C116FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A9DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A916FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE content_category ADD CONSTRAINT FK_54FBF32E84A0A3ED FOREIGN KEY (content_id) REFERENCES content (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_category ADD CONSTRAINT FK_54FBF32E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F84A0A3ED FOREIGN KEY (content_id) REFERENCES content (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('ALTER TABLE profile_category ADD CONSTRAINT FK_FF267870CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile_category ADD CONSTRAINT FK_FF26787012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1DE12AB56');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C116FE72E1');
        $this->addSql('ALTER TABLE content DROP FOREIGN KEY FK_FEC530A9DE12AB56');
        $this->addSql('ALTER TABLE content DROP FOREIGN KEY FK_FEC530A916FE72E1');
        $this->addSql('ALTER TABLE content_category DROP FOREIGN KEY FK_54FBF32E84A0A3ED');
        $this->addSql('ALTER TABLE content_category DROP FOREIGN KEY FK_54FBF32E12469DE2');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F84A0A3ED');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCCFA12B8');
        $this->addSql('ALTER TABLE profile_category DROP FOREIGN KEY FK_FF267870CCFA12B8');
        $this->addSql('ALTER TABLE profile_category DROP FOREIGN KEY FK_FF26787012469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE content');
        $this->addSql('DROP TABLE content_category');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE profile_category');
    }
}
