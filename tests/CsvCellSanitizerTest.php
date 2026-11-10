<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(dirname(__DIR__) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\CsvCellSanitizer;

import('lib.pkp.tests.PKPTestCase');

class CsvCellSanitizerTest extends \PKPTestCase
{
    /**
     * @dataProvider dangerousCells
     */
    public function testNeutralizesSpreadsheetFormulaCells(string $value): void
    {
        $this->assertSame("'" . $value, CsvCellSanitizer::sanitize($value));
    }

    public function dangerousCells(): array
    {
        return [
            ['=HYPERLINK("https://example.invalid")'],
            ['+1+1'],
            ['-1+1'],
            ['@SUM(1,1)'],
            ["\t=1+1"],
            ["\r=1+1"],
            ["\n=1+1"],
            ["\0=1+1"],
            ["\x7f=1+1"],
            ["  =1+1"],
        ];
    }

    public function testPreservesOrdinaryValuesAndTypes(): void
    {
        $this->assertSame('ordinary text', CsvCellSanitizer::sanitize('ordinary text'));
        $this->assertSame(42, CsvCellSanitizer::sanitize(42));
        $this->assertSame('', CsvCellSanitizer::sanitize(''));
    }
}
