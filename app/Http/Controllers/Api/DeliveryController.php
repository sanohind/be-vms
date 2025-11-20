<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeliveryController
{
    /**
     * Get delivery data from dn_header table for today
     * Filter by plan_delivery_date = today
     * Return: driver_name, plat_number, plan_delivery_time, supplier_name
     * Ordered by plan_delivery_time
     * Deduplicate entries with same plan_delivery_date, plan_delivery_time, driver_name, plat_number, and supplier_name
     */
    public function getTodayDelivery(): JsonResponse
    {
        try {
            $today = Carbon::today()->format('Y-m-d');
            
            // Query from dn_header table using mysql2 connection (SCM database)
            $deliveries = DB::connection('mysql2')
                ->table('dn_header')
                ->select(
                    'no_dn',
                    'driver_name',
                    'plat_number',
                    'plan_delivery_time',
                    'supplier_name',
                    'supplier_code',
                    'plan_delivery_date'
                )
                ->whereDate('plan_delivery_date', $today)
                ->whereNotNull('driver_name')
                ->whereNotNull('plat_number')
                ->orderBy('plan_delivery_time', 'asc')
                ->get();

            // Remove duplicates by comparing complete delivery signature
            $uniqueDeliveries = $deliveries->unique(function ($item) {
                return implode('|', [
                    $item->plan_delivery_date,
                    $item->plan_delivery_time,
                    trim(strtolower($item->driver_name ?? '')),
                    trim(strtolower($item->plat_number ?? '')),
                    trim(strtolower($item->supplier_name ?? '')),
                ]);
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Delivery data retrieved successfully',
                'data' => $uniqueDeliveries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve delivery data: ' . $e->getMessage()
            ], 500);
        }
    }
}

