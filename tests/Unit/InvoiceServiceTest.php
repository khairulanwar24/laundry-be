<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Api\V1\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = new InvoiceService;
    }

    public function test_generate_invoice_number_first_order_of_day(): void
    {
        $outletId = 1;

        // Mock today's date
        Carbon::setTestNow('2025-08-30 10:00:00');

        $invoiceNo = $this->invoiceService->generateInvoiceNumber($outletId);

        $expected = 'JL-2508300001';
        $this->assertEquals($expected, $invoiceNo);

        Carbon::setTestNow(); // Reset
    }

    public function test_generate_invoice_number_increments_sequence(): void
    {
        $outletId = 1;

        // Mock today's date
        Carbon::setTestNow('2025-08-30 10:00:00');

        // Create existing orders for today
        Order::factory()->create([
            'outlet_id' => $outletId,
            'invoice_no' => 'JL-2508300001',
            'created_at' => now(),
        ]);

        Order::factory()->create([
            'outlet_id' => $outletId,
            'invoice_no' => 'JL-2508300002',
            'created_at' => now(),
        ]);

        $invoiceNo = $this->invoiceService->generateInvoiceNumber($outletId);

        $expected = 'JL-2508300003';
        $this->assertEquals($expected, $invoiceNo);

        Carbon::setTestNow(); // Reset
    }

    public function test_generate_invoice_number_resets_sequence_for_new_day(): void
    {
        $outletId = 1;

        // Create orders for yesterday
        Carbon::setTestNow('2025-08-29 10:00:00');
        Order::factory()->create([
            'outlet_id' => $outletId,
            'invoice_no' => 'JL-2508290005',
            'created_at' => now(),
        ]);

        // Generate invoice for today
        Carbon::setTestNow('2025-08-30 10:00:00');
        $invoiceNo = $this->invoiceService->generateInvoiceNumber($outletId);

        $expected = 'JL-2508300001';
        $this->assertEquals($expected, $invoiceNo);

        Carbon::setTestNow(); // Reset
    }

    public function test_generate_invoice_number_different_outlets_independent(): void
    {
        $outlet1Id = 1;
        $outlet2Id = 2;

        Carbon::setTestNow('2025-08-30 10:00:00');

        // Create order for outlet 1
        Order::factory()->create([
            'outlet_id' => $outlet1Id,
            'invoice_no' => 'JL-2508300001',
            'created_at' => now(),
        ]);

        // Generate invoice for outlet 2 should start from 0001
        $invoiceNo = $this->invoiceService->generateInvoiceNumber($outlet2Id);

        $expected = 'JL-2508300001';
        $this->assertEquals($expected, $invoiceNo);

        Carbon::setTestNow(); // Reset
    }

    public function test_generate_invoice_number_handles_malformed_invoice_numbers(): void
    {
        $outletId = 1;

        Carbon::setTestNow('2025-08-30 10:00:00');

        // Create order with malformed invoice number
        Order::factory()->create([
            'outlet_id' => $outletId,
            'invoice_no' => 'INVALID-FORMAT',
            'created_at' => now(),
        ]);

        $invoiceNo = $this->invoiceService->generateInvoiceNumber($outletId);

        // Should start from 0001 since the existing format is invalid
        $expected = 'JL-2508300001';
        $this->assertEquals($expected, $invoiceNo);

        Carbon::setTestNow(); // Reset
    }

    public function test_generate_invoice_number_handles_empty_invoice_number(): void
    {
        $outletId = 1;

        Carbon::setTestNow('2025-08-30 10:00:00');

        // Create order with empty invoice number
        Order::factory()->create([
            'outlet_id' => $outletId,
            'invoice_no' => '',
            'created_at' => now(),
        ]);

        $invoiceNo = $this->invoiceService->generateInvoiceNumber($outletId);

        // Should start from 0001 since the existing invoice_no is empty
        $expected = 'JL-2508300001';
        $this->assertEquals($expected, $invoiceNo);

        Carbon::setTestNow(); // Reset
    }

    public function test_generate_invoice_number_format_consistency(): void
    {
        $outletId = 1;

        Carbon::setTestNow('2025-08-30 10:00:00');

        // Generate multiple invoice numbers and check format
        for ($i = 1; $i <= 5; $i++) {
            if ($i > 1) {
                // Create previous order
                Order::factory()->create([
                    'outlet_id' => $outletId,
                    'invoice_no' => sprintf('JL-250830%04d', $i - 1),
                    'created_at' => now(),
                ]);
            }

            $invoiceNo = $this->invoiceService->generateInvoiceNumber($outletId);
            $expected = sprintf('JL-250830%04d', $i);

            $this->assertEquals($expected, $invoiceNo);
            $this->assertMatchesRegularExpression('/^JL-\d{6}\d{4}$/', $invoiceNo);
        }

        Carbon::setTestNow(); // Reset
    }
}
