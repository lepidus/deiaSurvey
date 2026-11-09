<?php

namespace APP\plugins\generic\deiaSurvey\classes;

class CsvRowWriter
{
    /**
     * @param resource $stream
     * @return int|false
     */
    public static function write($stream, array $row)
    {
        $fields = [];
        foreach (CsvCellSanitizer::sanitizeRow($row) as $value) {
            // Double every quote. Backslashes must never escape CSV delimiters.
            // Unlike fputcsv(..., ''), this also works on PHP 7.3.
            $fields[] = '"' . str_replace('"', '""', (string) $value) . '"';
        }

        return fwrite($stream, implode(',', $fields) . "\n");
    }
}
