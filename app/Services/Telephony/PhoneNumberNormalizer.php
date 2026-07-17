<?php

namespace App\Services\Telephony;

class PhoneNumberNormalizer
{
    private const INVALID_VALUES = [
        '0',
        '00',
        '0000000',
        '00000000',
        '0000000000',
        '1111111111',
        '1234567',
        '12345678',
        '1234567890',
        '2147483647',
        '9999999999',
    ];

    public function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);
        if (str_contains($lower, 'privado') || str_contains($lower, 'anonymous') || str_contains($lower, 'desconocido')) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if ($digits === '' || strlen($digits) < 7 || $this->isInvalidPlaceholder($digits)) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($this->isInvalidPlaceholder($digits)) {
            return null;
        }

        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '52' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return $digits;
        }

        if (strlen($digits) > 12 && str_starts_with($digits, '521')) {
            return '52' . substr($digits, 3);
        }

        return $digits;
    }

    private function isInvalidPlaceholder(string $digits): bool
    {
        if (in_array($digits, self::INVALID_VALUES, true)) {
            return true;
        }

        return (bool) preg_match('/^(\d)\1{6,}$/', $digits);
    }
}