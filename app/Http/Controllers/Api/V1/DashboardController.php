<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Payment;
use App\Support\ResponseJson;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    /**
     * Get dashboard summary for the outlet.
     */
    public function summary(Outlet $outlet, Request $request): JsonResponse
    {
        try {
            $this->authorize('view', $outlet);            // Get date parameter or default to today in application timezone
            $date = $request->input('date');

            if ($date) {
                $targetDate = Carbon::createFromFormat('Y-m-d', $date, config('app.timezone'));
            } else {
                $targetDate = Carbon::now(config('app.timezone'));
            }

            // Set date range for today
            $startOfDay = $targetDate->copy()->startOfDay();
            $endOfDay = $targetDate->copy()->endOfDay();
            $now = Carbon::now(config('app.timezone'));

            // Calculate masuk: COUNT orders created today (status <> BATAL)
            $masuk = Order::where('outlet_id', $outlet->id)
                ->where('status', '!=', Order::STATUS_BATAL)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count();

            // Calculate harus_selesai: COUNT orders date(eta_at)=today AND status NOT IN (SELESAI,BATAL)
            $harusSelesai = Order::where('outlet_id', $outlet->id)
                ->whereNotIn('status', [Order::STATUS_SELESAI, Order::STATUS_BATAL])
                ->whereDate('eta_at', $targetDate->toDateString())
                ->count();

            // Calculate terlambat: COUNT orders eta_at<now AND status NOT IN (SELESAI,BATAL)
            $terlambat = Order::where('outlet_id', $outlet->id)
                ->whereNotIn('status', [Order::STATUS_SELESAI, Order::STATUS_BATAL])
                ->where('eta_at', '<', $now)
                ->count();

            // Calculate omset: SUM orders.total where created today and status<>BATAL
            $omset = Order::where('outlet_id', $outlet->id)
                ->where('status', '!=', Order::STATUS_BATAL)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->sum('total') ?? 0;

            // Calculate pendapatan: SUM payments.amount where paid_at today and join outlet
            $pendapatan = Payment::join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.outlet_id', $outlet->id)
                ->where('payments.status', Payment::STATUS_SUCCESS)
                ->whereBetween('payments.paid_at', [$startOfDay, $endOfDay])
                ->sum('payments.amount') ?? 0;

            // Calculate pengeluaran: SUM expenses.amount where expense_at today
            // Since there's no Expense model yet, set to 0
            $pengeluaran = 0;

            // Calculate item_diambil: COUNT orders where collected_at today
            $itemDiambil = Order::where('outlet_id', $outlet->id)
                ->whereBetween('collected_at', [$startOfDay, $endOfDay])
                ->count();

            $summary = [
                'date' => $targetDate->toDateString(),
                'masuk' => $masuk,
                'harus_selesai' => $harusSelesai,
                'terlambat' => $terlambat,
                'omset' => (float) $omset,
                'pendapatan' => (float) $pendapatan,
                'pengeluaran' => (float) $pengeluaran,
                'item_diambil' => $itemDiambil,
            ];

            return $this->ok(
                data: $summary,
                message: 'Ringkasan dashboard berhasil diambil'
            );
        } catch (Exception $e) {
            return $this->serverError(
                message: 'Gagal mengambil ringkasan dashboard'
            );
        }
    }
}
