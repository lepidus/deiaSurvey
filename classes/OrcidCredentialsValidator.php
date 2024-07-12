<?php

namespace APP\plugins\generic\demographicData\classes;

class OrcidCredentialsValidator
{
    public function validateClientId($str): bool
    {
        $valid = false;
        if (preg_match('/^APP-[\da-zA-Z]{16}|(\d{4}-){3,}\d{3}[\dX]/', $str) == 1) {
            $valid = true;
        }
        return $valid;
    }

    public function validateClientSecret($str): bool
    {
        $valid = false;
        if (preg_match('/^(\d|-|[a-f]){36,64}/', $str) == 1) {
            $valid = true;
        }
        return $valid;
    }
}
