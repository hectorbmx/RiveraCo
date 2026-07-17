<?php

namespace App\Services\Telephony;

use Illuminate\Support\Arr;

class GrandstreamExtensionMapper
{
    public function map(array|object $item): ?array
    {
        $raw = $this->toArray($item);
        $extension = $this->value($raw, 'extension');

        if (!$extension) {
            return null;
        }

        return [
            'extension' => $extension,
            'account_type' => $this->value($raw, 'account_type'),
            'fullname' => $this->value($raw, 'fullname'),
            'user_name' => $this->value($raw, 'user_name'),
            'email' => $this->value($raw, 'email'),
            'status' => $this->value($raw, 'status'),
            'addr' => $this->value($raw, 'addr'),
            'out_of_service' => $this->boolean($raw, 'out_of_service'),
            'enable_contact' => $this->boolean($raw, 'enable_contact'),
            'email_to_user' => $this->boolean($raw, 'email_to_user'),
            'raw_payload' => $raw,
            'synced_at' => now(),
        ];
    }

    private function toArray(array|object $item): array
    {
        if (is_array($item)) {
            return $item;
        }

        return json_decode(json_encode($item), true) ?: [];
    }

    private function value(array $row, string $key): ?string
    {
        $value = Arr::get($row, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function boolean(array $row, string $key): bool
    {
        $value = Arr::get($row, $key);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}