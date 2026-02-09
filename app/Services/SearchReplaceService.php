<?php

namespace App\Services;

class SearchReplaceService
{
    public function apply(array $rules, string $subject): string
    {
        return str_ireplace(
            array_column($rules, 'find'),
            array_column($rules, 'replace'),
            $subject
        );
    }
}
