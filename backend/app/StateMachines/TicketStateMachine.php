<?php

namespace App\StateMachines;

use Illuminate\Validation\ValidationException;

class TicketStateMachine
{
    // Transisi yang diizinkan
    private static array $transitions = [
        'pending'    => ['surveyed'],
        'surveyed'   => ['unpaid'],
        'unpaid'     => ['processing'],
        'processing' => ['completed'],
        'completed'  => [],
    ];

    public static function validate(string $currentStatus, string $newStatus): void
    {
        $allowed = self::$transitions[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Transisi status dari '{$currentStatus}' ke '{$newStatus}' tidak diizinkan."
                ],
            ]);
        }
    }

    public static function canTransition(string $currentStatus, string $newStatus): bool
    {
        $allowed = self::$transitions[$currentStatus] ?? [];

        return in_array($newStatus, $allowed);
    }
}
