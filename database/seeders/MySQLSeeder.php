<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MySQLSeeder extends Seeder
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = DB::connection()->getPdo();
    }

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        collect($this->queries())->each(fn ($query) => $this->pdo->exec($query));
    }

    public function queries(): array
    {
        return [
            "
                DROP FUNCTION IF EXISTS `ExtractDecimal`;
                CREATE FUNCTION `ExtractDecimal`(in_string VARCHAR(255))
                RETURNS decimal(15,2)
                NO SQL
                BEGIN
                    DECLARE ctrNumber VARCHAR(255);
                    DECLARE in_string_parsed VARCHAR(255);
                    DECLARE digitsAndDotsNumber VARCHAR(255) DEFAULT '';
                    DECLARE posAfterDot INT DEFAULT 2;
                    DECLARE finalNumber VARCHAR(255) DEFAULT '';
                    DECLARE sChar VARCHAR(1);
                    DECLARE inti INTEGER DEFAULT 1;
                    DECLARE digitSequenceStarted boolean DEFAULT false;
                    DECLARE negativeNumber boolean DEFAULT false;

                    SET in_string_parsed = REPLACE(REPLACE(in_string, ',','.'), ' ', '');

                    IF LENGTH(in_string_parsed) > 0 THEN
                        WHILE(inti <= LENGTH(in_string_parsed)) DO
                            SET sChar = SUBSTRING(in_string_parsed, inti, 1);
                            SET ctrNumber = FIND_IN_SET(sChar, '0,1,2,3,4,5,6,7,8,9,.');
                            IF ctrNumber > 0 AND (sChar != '.' OR LENGTH(digitsAndDotsNumber) > 0) THEN
                                -- add first minus if needed
                                IF digitSequenceStarted = false AND inti > 1 AND SUBSTRING(in_string_parsed, inti-1, 1) = '-' THEN
                                    SET negativeNumber = true;
                                END IF;

                                SET digitSequenceStarted = true;
                                SET digitsAndDotsNumber = CONCAT(digitsAndDotsNumber, sChar);
                            ELSEIF digitSequenceStarted = true THEN
                                SET inti = LENGTH(in_string_parsed);
                            END IF;
                            SET inti = inti + 1;
                        END WHILE;

                        SET inti = LENGTH(digitsAndDotsNumber);
                        WHILE(inti > 0) DO
                            IF(SUBSTRING(digitsAndDotsNumber, inti, 1) = '.') THEN
                                SET digitsAndDotsNumber = SUBSTRING(digitsAndDotsNumber, 1, inti-1);
                                SET inti = inti - 1;
                            ELSE
                                SET inti = 0;
                            END IF;
                        END WHILE;

                        SET inti = 1;
                        SET posAfterDot = length(substring_index(digitsAndDotsNumber, '.', -1)) + 1;
                        WHILE(inti <= LENGTH(digitsAndDotsNumber) - posAfterDot) DO
                            SET sChar = SUBSTRING(digitsAndDotsNumber, inti, 1);
                            SET ctrNumber = FIND_IN_SET(sChar, '0,1,2,3,4,5,6,7,8,9');
                            IF ctrNumber > 0 THEN
                                SET finalNumber = CONCAT(finalNumber, sChar);
                            END IF;
                            SET inti = inti + 1;
                        END WHILE;

                        SET finalNumber = CONCAT(finalNumber, RIGHT(digitsAndDotsNumber, posAfterDot));
                        IF negativeNumber = true AND LENGTH(finalNumber) > 0 THEN
                            SET finalNumber = CONCAT('-', finalNumber);
                        END IF;

                        IF LENGTH(finalNumber) = 0 THEN
                            RETURN 0;
                        END IF;

                        RETURN CAST(finalNumber AS decimal(15,2));
                    ELSE
                        RETURN 0;
                    END IF;
                END
            ",
        ];
    }
}
