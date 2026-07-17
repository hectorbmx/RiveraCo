<?php

namespace App\Rules;

use App\Services\Telephony\PhoneNumberNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMexicanPhone implements ValidationRule
{
    public function __construct(private readonly bool $allowEmpty = true)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            if (!$this->allowEmpty) {
                $fail('El :attribute es obligatorio.');
            }

            return;
        }

        $normalized = app(PhoneNumberNormalizer::class)->normalize($value);

        if (!$normalized || !preg_match('/^52\d{10}$/', $normalized)) {
            $fail('El :attribute debe tener un numero telefonico valido de 10 digitos. Puedes capturarlo como 3331234567 o +52 333 123 4567.');
        }
    }
}