<?php

namespace App\Http\Middleware;

use App\Models\Outlet;
use App\Models\UserOutlet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class EnsureUserInOutlet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();

        // Check if user is authenticated
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus login terlebih dahulu',
            ], 401);
        }

        // Get outlet parameter from route
        $outletParam = $request->route('outlet');

        if (! $outletParam) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter outlet tidak ditemukan',
            ], 400);
        }

        // Handle both outlet model instance and outlet ID
        if ($outletParam instanceof Outlet) {
            $outlet = $outletParam;
        } else {
            $outlet = Outlet::find($outletParam);
            if (! $outlet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Outlet tidak ditemukan',
                ], 404);
            }
        }

        // Check if user is a member of this outlet and is active
        $userOutlet = UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->first();

        if (! $userOutlet) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di outlet ini',
            ], 403);
        }

        // Add userOutlet to request for use in controllers
        $request->merge(['userOutlet' => $userOutlet]);

        return $next($request);
    }
}
