<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    /**
     * Get suppliers for dropdown selection
     */
    public function getSuppliers(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 100);

            $query = BusinessPartner::select('bp_code', 'bp_name', 'adr_line_1', 'adr_line_2', 'adr_line_3', 'adr_line_4', 'bp_phone', 'bp_status_desc')
                ->where('bp_status_desc', 'Active')
                ->whereRaw("bp_code NOT REGEXP '-[0-9]+$'") // Exclude bp_code with suffix (e.g., SLSICHWAN-1, SLSICHWAN-2)
                ->orderBy('bp_name', 'asc');

            // Add search functionality
            if (!empty($search)) {
                $searchTerm = trim($search);
                $query->where(function($q) use ($searchTerm) {
                    $q->where('bp_code', 'like', '%' . $searchTerm . '%')
                      ->orWhere('bp_name', 'like', '%' . $searchTerm . '%');
                });
            }

            // Remove limit to show all suppliers
            $suppliers = $query->get();

            // Format data for dropdown
            $formattedSuppliers = $suppliers->map(function($supplier) {
                // Gabungkan alamat dari beberapa kolom
                $addressParts = array_filter([
                    $supplier->adr_line_1,
                    $supplier->adr_line_2,
                    $supplier->adr_line_3,
                    $supplier->adr_line_4
                ]);
                $fullAddress = implode(', ', $addressParts);

                return [
                    'value' => $supplier->bp_code,
                    'label' => $supplier->bp_name,
                    'code' => $supplier->bp_code,
                    'name' => $supplier->bp_name,
                    'address' => $fullAddress,
                    'phone' => $supplier->bp_phone,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Suppliers retrieved successfully',
                'data' => $formattedSuppliers,
                'total' => $formattedSuppliers->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suppliers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supplier by code
     */
    public function getSupplierByCode($bpCode): JsonResponse
    {
        try {
            $supplier = BusinessPartner::select('bp_code', 'bp_name', 'adr_line_1', 'adr_line_2', 'adr_line_3', 'adr_line_4', 'bp_phone', 'bp_status_desc')
                ->where('bp_code', $bpCode)
                ->where('bp_status_desc', 'Active')
                ->whereRaw("bp_code NOT REGEXP '-[0-9]+$'") // Exclude bp_code with suffix
                ->first();

            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier not found'
                ], 404);
            }

            // Gabungkan alamat dari beberapa kolom
            $addressParts = array_filter([
                $supplier->adr_line_1,
                $supplier->adr_line_2,
                $supplier->adr_line_3,
                $supplier->adr_line_4
            ]);
            $fullAddress = implode(', ', $addressParts);

            return response()->json([
                'success' => true,
                'message' => 'Supplier retrieved successfully',
                'data' => [
                    'value' => $supplier->bp_code,
                    'label' => $supplier->bp_name,
                    'code' => $supplier->bp_code,
                    'name' => $supplier->bp_name,
                    'address' => $fullAddress,
                    'phone' => $supplier->bp_phone,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search suppliers with autocomplete functionality
     */
    public function searchSuppliers(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->get('q', '');
            $limit = $request->get('limit', 50);

            if (empty($searchTerm)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Please provide search term',
                    'data' => [],
                    'total' => 0
                ]);
            }

            $suppliers = BusinessPartner::select('bp_code', 'bp_name', 'adr_line_1', 'adr_line_2', 'adr_line_3', 'adr_line_4', 'bp_phone', 'bp_status_desc')
                ->where('bp_status_desc', 'Active')
                ->whereRaw("bp_code NOT REGEXP '-[0-9]+$'") // Exclude bp_code with suffix
                ->where(function($query) use ($searchTerm) {
                    $query->where('bp_code', 'like', '%' . $searchTerm . '%')
                          ->orWhere('bp_name', 'like', '%' . $searchTerm . '%');
                })
                ->orderBy('bp_name', 'asc')
                ->limit($limit)
                ->get();

            // Format data for dropdown
            $formattedSuppliers = $suppliers->map(function($supplier) {
                $addressParts = array_filter([
                    $supplier->adr_line_1,
                    $supplier->adr_line_2,
                    $supplier->adr_line_3,
                    $supplier->adr_line_4
                ]);
                $fullAddress = implode(', ', $addressParts);

                return [
                    'value' => $supplier->bp_code,
                    'label' => $supplier->bp_name,
                    'code' => $supplier->bp_code,
                    'name' => $supplier->bp_name,
                    'address' => $fullAddress,
                    'phone' => $supplier->bp_phone,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Search completed successfully',
                'data' => $formattedSuppliers,
                'total' => $formattedSuppliers->count(),
                'search_term' => $searchTerm
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $count = BusinessPartner::count();
            return response()->json([
                'success' => true,
                'message' => 'Database connection successful',
                'total_suppliers' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
