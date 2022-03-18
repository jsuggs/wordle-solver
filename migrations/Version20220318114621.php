<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220318114621 extends AbstractMigration
{
    private const WORDS = <<<SQL
CREATE TABLE words (
    word CHARACTER(5) PRIMARY KEY,
    c1 CHARACTER(1),
    c2 CHARACTER(1),
    c3 CHARACTER(1),
    c4 CHARACTER(1),
    c5 CHARACTER(1)
);
SQL;

private const FREQUENCY =<<<SQL
CREATE TABLE frequency (
    word CHARACTER(5) PRIMARY KEY,
    frequency UNSIGNED TINY INT
);
SQL;

    public function getDescription(): string
    {
        return 'Create the initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(self::WORDS);
        $this->addSql(self::FREQUENCY);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE FREQUENCY');
        $this->addSql('DROP TABLE WORDS');
    }
}
