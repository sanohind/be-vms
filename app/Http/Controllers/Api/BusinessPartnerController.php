<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessPartnerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BusinessPartnerController extends Controller
{
    protected $businessPartnerService;

    public function __construct(BusinessPartnerService $businessPartnerService)
    {
        $this->businessPartnerService = $businessPartnerService;
    }

    /**
     * Get all business partners with optional search
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search');
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            if ($search) {
                $partners = $this->businessPartnerService->searchPartners($search, $perPage);
            } else {
                $partners = $this->businessPartnerService->getAllPartners($perPage, $page);
            }

            return response()->json([
                'success' => true,
                'message' => 'Business partners retrieved successfully',
                'data' => $partners
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve business partners: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get business partner by code with unified data
     */
    public function show(Request $request, $bpCode): JsonResponse
    {
        try {
            $bpCode = $this->businessPartnerService->normalizeBpCode($bpCode);
            
            // Get the specific partner
            $partner = $this->businessPartnerService->getPartnerByCode($bpCode);
            
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business partner not found'
                ], 404);
            }

            // Get unified partner data (parent + children)
            $unifiedPartners = $this->businessPartnerService->getUnifiedPartners($bpCode);

            return response()->json([
                'success' => true,
                'message' => 'Business partner retrieved successfully',
                'data' => [
                    'partner' => $partner,
                    'unified_partners' => $unifiedPartners
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve business partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unified visitors for a business partner
     */
    public function getVisitors(Request $request, $bpCode): JsonResponse
    {
        try {
            $bpCode = $this->businessPartnerService->normalizeBpCode($bpCode);
            
            // Validate partner exists
            $partner = $this->businessPartnerService->getPartnerByCode($bpCode);
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business partner not found'
                ], 404);
            }

            // Get filters from request
            $filters = $request->only([
                'visitor_date_from',
                'visitor_date_to',
                'visitor_needs',
                'status'
            ]);

            $visitors = $this->businessPartnerService->getUnifiedVisitors($bpCode, $filters);

            return response()->json([
                'success' => true,
                'message' => 'Visitors retrieved successfully',
                'data' => [
                    'partner' => $partner,
                    'visitors' => $visitors,
                    'total_count' => $visitors->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve visitors: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard data for a business partner
     */
    public function getDashboard(Request $request, $bpCode): JsonResponse
    {
        try {
            $bpCode = $this->businessPartnerService->normalizeBpCode($bpCode);
            
            // Validate partner exists
            $partner = $this->businessPartnerService->getPartnerByCode($bpCode);
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business partner not found'
                ], 404);
            }

            $dashboardData = $this->businessPartnerService->getUnifiedDashboardData($bpCode);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'partner' => $partner,
                    'dashboard' => $dashboardData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search business partners
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->get('q', '');
            $limit = $request->get('limit', 50);

            $partners = $this->businessPartnerService->searchPartners($searchTerm, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Search completed successfully',
                'data' => $partners,
                'total_count' => $partners->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get business partners by type
     */
    public function getByType(Request $request, $type): JsonResponse
    {
        try {
            $partners = $this->businessPartnerService->getPartnersByType($type);

            return response()->json([
                'success' => true,
                'message' => 'Business partners retrieved successfully',
                'data' => $partners,
                'total_count' => $partners->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve business partners: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active business partners only
     */
    public function getActive(): JsonResponse
    {
        try {
            $partners = $this->businessPartnerService->getActivePartners();

            return response()->json([
                'success' => true,
                'message' => 'Active business partners retrieved successfully',
                'data' => $partners,
                'total_count' => $partners->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active business partners: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $isConnected = $this->businessPartnerService->testConnection();

            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected ? 'Database connection successful' : 'Database connection failed',
                'connected' => $isConnected
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'connected' => false
            ], 500);
        }
    }

    /**
     * Update parent-child relationships (admin function)
     */
    public function updateRelationships(): JsonResponse
    {
        try {
            $updatedCount = $this->businessPartnerService->updateParentChildRelation();

            return response()->json([
                'success' => true,
                'message' => 'Parent-child relationships updated successfully',
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update relationships: ' . $e->getMessage()
            ], 500);
        }
    }
}
