<?php

namespace APP\plugins\generic\deiaSurvey\classes;

class CsvCellSanitizer
{
    public static function sanitize($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $startsWithControl = preg_match('/^[\x00-\x1F\x7F]/', $value) === 1;
        $startsWithFormula = preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1;
        if ($startsWithControl || $startsWithFormula) {
            return "'" . $value;
        }

        return $value;
    }

    public static function sanitizeRow(array $row): array
    {
        return array_map([self::class, 'sanitize'], $row);
    }
}
