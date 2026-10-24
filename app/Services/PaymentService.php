<?php

namespace App\Services;

class PaymentService
{
    /**
     * Start an M-Pesa STK Push. Must return ['ok' => bool, 'settled' => bool, 'message' => string].
     * Port the logic from includes/PaymentService.php here (request, pending row, callback handling).
     */
    public function startStk(int $orderId, int $userId, string $phone, int $amount, string $staffName): array
    {
        return ['ok' => false, 'settled' => false, 'message' => 'M-Pesa is not connected yet. Take cash for now.'];
    }
}