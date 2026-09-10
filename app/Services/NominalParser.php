<?php

namespace App\Services;

class NominalParser
{
    /**
     * Parse flexible nominal string to numeric float/int.
     * Examples: '50rb' -> 50000, '50k' -> 50000, '1.5jt' -> 1500000, '1,5jt' -> 1500000, '1.000.000' -> 1000000
     */
    public static function parse(string $input): ?float
    {
        $input = trim(strtolower($input));

        if (empty($input)) {
            return null;
        }

        // Handle millions: '1.5jt', '1,5jt', '2jt', '500jt'
        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta)$/i', $input, $matches)) {
            $val = (float) str_replace(',', '.', $matches[1]);

            return $val * 1000000;
        }

        // Handle thousands: '50rb', '50k', '1.5rb', '1,5k', '500ribu'
        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\s*(?:rb|k|ribu)$/i', $input, $matches)) {
            $val = (float) str_replace(',', '.', $matches[1]);

            return $val * 1000;
        }

        // Handle standard numeric with dots or commas as thousand separator: '1.000.000', '1,000,000'
        $cleaned = preg_replace('/[^\d,\.]/', '', $input);

        // Remove dots/commas if they represent thousand separators
        if (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/', $cleaned)) {
            $cleanNumeric = preg_replace('/[.,]/', '', $cleaned);

            return (float) $cleanNumeric;
        }

        // Direct numeric or simple decimal
        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        // Try comma as decimal separator
        $decimalConverted = str_replace(',', '.', $cleaned);
        if (is_numeric($decimalConverted)) {
            return (float) $decimalConverted;
        }

        return null;
    }
}
