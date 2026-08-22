<?php

namespace App\StateMachines;

use Illuminate\Validation\ValidationException;

class TicketStateMachine
{
    private static array $transitions = [
        'draft' => ['pending', 'terminated'],
        'pending' => ['surveyed', 'unpaid', 'processing', 'terminated'],
        'surveyed' => ['unpaid', 'processing', 'pending', 'terminated'],
        'unpaid' => ['processing', 'pending', 'terminated'],
        'processing' => ['completed', 'terminated'],
        'completed' => ['suspended', 'terminated'],
        'suspended' => ['completed', 'terminated'],
        'terminated' => [],
    ];

    public static function validate(string $currentStatus, string $newStatus): void
    {
        $allowed = self::$transitions[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Transisi status dari '{$currentStatus}' ke '{$newStatus}' tidak diizinkan.",
                ],
            ]);
        }
    }

    public static function canTransition(string $currentStatus, string $newStatus): bool
    {
        $allowed = self::$transitions[$currentStatus] ?? [];

        return in_array($newStatus, $allowed);
    }

    public static function allowedFrom(string $currentStatus): array
    {
        return self::$transitions[$currentStatus] ?? [];
    }
}
