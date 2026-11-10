<?php

namespace APP\plugins\generic\deiaSurvey\tests;

require_once(dirname(__DIR__) . '/autoload.php');

use APP\plugins\generic\deiaSurvey\classes\CsvRowWriter;

import('lib.pkp.tests.PKPTestCase');

class CsvRowWriterTest extends \PKPTestCase
{
    /**
     * @dataProvider csvRows
     */
    public function testWritesDelimitedCells(array $row, string $expectedCsv): void
    {
        $stream = fopen('php://memory', 'w+');
        try {
            $this->assertSame(strlen($expectedCsv), CsvRowWriter::write($stream, $row));
            rewind($stream);
            // Golden CSV bytes avoid sharing the writer's escape assumptions.
            $this->assertSame($expectedCsv, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function csvRows(): array
    {
        return [
            'empty-row' => [[], "\n"],
            'ordinary-values' => [
                ['ordinary text', 42, 0, -3, 1.5, true, false, null, ''],
                "\"ordinary text\",\"42\",\"0\",\"-3\",\"1.5\",\"1\",\"\",\"\",\"\"\n",
            ],
            'formula-prefixes' => [
                ['=1+1', '+1+1', '-1+1', '@SUM(1,1)', '  =1+1'],
                "\"'=1+1\",\"'+1+1\",\"'-1+1\",\"'@SUM(1,1)\",\"'  =1+1\"\n",
            ],
            'backslash-quote-breakout' => [
                ['a\\",=1+1,', 'tail\\', 'last'],
                "\"a\\\"\",=1+1,\",\"tail\\\",\"last\"\n",
            ],
            'quote-and-line-breaks' => [
                ["a\",\r\n=1+1,", 'last'],
                "\"a\"\",\r\n=1+1,\",\"last\"\n",
            ],
            'controls' => [
                ["\t=1+1", "\r=1+1", "\n=1+1", "\0=1+1", "\x7f=1+1"],
                "\"'\t=1+1\",\"'\r=1+1\",\"'\n=1+1\",\"'\0=1+1\",\"'\x7f=1+1\"\n",
            ],
            'unicode-and-delimiters' => [
                ['ação, gênero', 'a"b', "a\0\"b"],
                "\"ação, gênero\",\"a\"\"b\",\"a\0\"\"b\"\n",
            ],
        ];
    }
}
