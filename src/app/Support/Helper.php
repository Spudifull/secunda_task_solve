<?php
declare(strict_types=1);

namespace App\Support;

final class Helper
{
    public static function boolOrNull(mixed $v): ?bool
    {
        if ($v === null) return null;
        if (is_bool($v)) return $v;

        if (is_string($v)) {
            $v = strtolower(trim($v));
        }

        return match ($v) {
            1, '1', 'true', 'yes', 'on'   => true,
            0, '0', 'false', 'no',  'off' => false,
            default => null,
        };
    }
}
