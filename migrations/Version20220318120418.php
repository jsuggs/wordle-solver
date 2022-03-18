<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220318120418 extends AbstractMigration
{
    private const IMPORT_COMMANDS = <<<SH
sqlite3 var/data.db <<END_SQL
.mode csv
.import var/data/frequency.csv frequency
.import var/data/words_expanded.csv words
END_SQL

SH;

    public function getDescription(): string
    {
        return 'Import data';
    }

    public function up(Schema $schema): void
    {
        shell_exec(self::IMPORT_COMMANDS);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
