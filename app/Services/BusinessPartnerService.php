<?php

namespace App\Services;

use App\Models\BusinessPartner;
use App\Models\Visitor;
use Illuminate\Support\Collection;

class BusinessPartnerService
{
    /**
     * Get all related bp_codes (parent & child) for unified search.
     */
    public function getUnifiedBpCodes($bpCode): Collection
    {
        return BusinessPartner::getUnifiedBpCodes($bpCode);
    }

    /**
     * Get all related BusinessPartner models (parent & child).
     */
    public function getUnifiedPartners($bpCode): Collection
    {
        return BusinessPartner::getUnifiedPartnerData($bpCode);
    }

    /**
     * Get unified visitor data for a business partner
     */
    public function getUnifiedVisitors($bpCode, array $filters = []): Collection
    {
        $bpCodes = $this->getUnifiedBpCodes($bpCode);
        
        if ($bpCodes->isEmpty()) {
            return collect();
        }

        $query = Visitor::whereIn('visitor_from', $bpCodes);

        // Apply filters
        if (!empty($filters['visitor_date_from'])) {
            $query->whereDate('visitor_date', '>=', $filters['visitor_date_from']);
        }

        if (!empty($filters['visitor_date_to'])) {
            $query->whereDate('visitor_date', '<=', $filters['visitor_date_to']);
        }

        if (!empty($filters['visitor_needs'])) {
            $query->where('visitor_needs', $filters['visitor_needs']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'checked_in') {
                $query->whereNotNull('visitor_checkin')->whereNull('visitor_checkout');
            } elseif ($filters['status'] === 'checked_out') {
                $query->whereNotNull('visitor_checkout');
            }
        }

        return $query->orderBy('visitor_date', 'desc')->get();
    }

    /**
     * Get unified dashboard data for a business partner
     */
    public function getUnifiedDashboardData($bpCode): array
    {
        $bpCodes = $this->getUnifiedBpCodes($bpCode);
        
        if ($bpCodes->isEmpty()) {
            return [
                'total_visitors' => 0,
                'today_visitors' => 0,
                'checked_in_visitors' => 0,
                'checked_out_visitors' => 0,
                'meeting_visitors' => 0,
                'delivery_visitors' => 0,
                'contractor_visitors' => 0,
            ];
        }

        $today = now()->toDateString();

        return [
            'total_visitors' => Visitor::whereIn('visitor_from', $bpCodes)->count(),
            'today_visitors' => Visitor::whereIn('visitor_from', $bpCodes)->whereDate('visitor_date', $today)->count(),
            'checked_in_visitors' => Visitor::whereIn('visitor_from', $bpCodes)
                ->whereNotNull('visitor_checkin')
                ->whereNull('visitor_checkout')
                ->count(),
            'checked_out_visitors' => Visitor::whereIn('visitor_from', $bpCodes)
                ->whereNotNull('visitor_checkout')
                ->count(),
            'meeting_visitors' => Visitor::whereIn('visitor_from', $bpCodes)
                ->where('visitor_needs', 'Meeting')
                ->count(),
            'delivery_visitors' => Visitor::whereIn('visitor_from', $bpCodes)
                ->where('visitor_needs', 'Delivery')
                ->count(),
            'contractor_visitors' => Visitor::whereIn('visitor_from', $bpCodes)
                ->where('visitor_needs', 'Contractor')
                ->count(),
        ];
    }

    /**
     * Search business partners by name or code
     */
    public function searchPartners($searchTerm, $limit = 50): Collection
    {
        if (empty($searchTerm)) {
            return BusinessPartner::limit($limit)->get();
        }

        $searchTerm = trim(strtoupper($searchTerm));

        return BusinessPartner::where(function($query) use ($searchTerm) {
            $query->where('bp_code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('bp_name', 'like', '%' . $searchTerm . '%');
        })->limit($limit)->get();
    }

    /**
     * Get business partner by code with unified data
     */
    public function getPartnerByCode($bpCode): ?BusinessPartner
    {
        $bpCode = $this->normalizeBpCode($bpCode);
        return BusinessPartner::where('bp_code', $bpCode)->first();
    }

    /**
     * Get all business partners with pagination
     */
    public function getAllPartners($perPage = 15, $page = 1)
    {
        return BusinessPartner::paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get business partners by type
     */
    public function getPartnersByType($type): Collection
    {
        return BusinessPartner::where('bp_type', $type)->get();
    }

    /**
     * Get active business partners only
     */
    public function getActivePartners(): Collection
    {
        return BusinessPartner::where('bp_status_desc', 'Active')->get();
    }

    /**
     * Update parent-child relationship for existing data (migration helper).
     */
    public function updateParentChildRelation(): int
    {
        $updatedCount = 0;
        $partners = BusinessPartner::all();
        
        foreach ($partners as $partner) {
            if (preg_match('/-\d+$/', $partner->bp_code)) {
                $base = preg_replace('/-\d+$/', '', $partner->bp_code);
                
                // Check if parent exists
                $parent = BusinessPartner::where('bp_code', $base)->first();
                if ($parent && $partner->parent_bp_code !== $base) {
                    $partner->parent_bp_code = $base;
                    $partner->save();
                    $updatedCount++;
                }
            }
        }
        
        return $updatedCount;
    }

    /**
     * Validate and normalize bp_code input
     */
    public function normalizeBpCode($bpCode): string
    {
        return trim(strtoupper($bpCode));
    }

    /**
     * Check if bp_code is from old system (has suffix)
     */
    public function isOldSystemBpCode($bpCode): bool
    {
        return preg_match('/-\d+$/', $bpCode);
    }

    /**
     * Check if bp_code is from new system (no suffix)
     */
    public function isNewSystemBpCode($bpCode): bool
    {
        return !preg_match('/-\d+$/', $bpCode);
    }

    /**
     * Get base bp_code (remove suffix if exists)
     */
    public function getBaseBpCode($bpCode): string
    {
        return preg_replace('/-\d+$/', '', $bpCode);
    }

    /**
     * Test database connection to sanoh-scm
     */
    public function testConnection(): bool
    {
        try {
            BusinessPartner::count();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
