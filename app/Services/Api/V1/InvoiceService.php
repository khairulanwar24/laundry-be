<?php

namespace App\Services\Api\V1;

use App\Models\Order;

class InvoiceService
{
    /**
     * Generate a unique invoice number for an order.
     */
    public function generateInvoiceNumber(int $outletId): string
    {
        $prefix = 'JL';
        $date = now()->format('ymd'); // Use ymd instead of Ymd for 2-digit year

        // Get the last order for today for this outlet to get the sequence number
        $lastOrder = Order::where('outlet_id', $outletId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastOrder && $lastOrder->invoice_no) {
            // Extract sequence from last invoice number (format: JL-YMMDDXXXX)
            if (preg_match('/^JL-\d{6}(\d{4})$/', $lastOrder->invoice_no, $matches)) {
                $sequence = intval($matches[1]) + 1;
            }
        }

        return sprintf('%s-%s%04d', $prefix, $date, $sequence);
    }
}
