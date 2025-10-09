<?php

/**
 * Script untuk memperbaiki data visitor yang bermasalah
 * Jalankan dengan: php fix_visitor_data.php
 */

require_once 'vendor/autoload.php';

use App\Models\Visitor;
use App\Models\BusinessPartner;

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fix Visitor Data Script ===\n\n";

try {
    // Get all Delivery visitors
    echo "1. Getting all Delivery visitors...\n";
    $deliveryVisitors = Visitor::where('visitor_needs', 'Delivery')
        ->whereNotNull('visitor_from')
        ->get();
    
    echo "✓ Found " . $deliveryVisitors->count() . " Delivery visitors\n\n";
    
    $fixedCount = 0;
    $skippedCount = 0;
    
    foreach ($deliveryVisitors as $visitor) {
        echo "Processing visitor: {$visitor->visitor_id}\n";
        echo "  - Current visitor_from: {$visitor->visitor_from}\n";
        echo "  - Current bp_code: " . ($visitor->bp_code ?? 'NULL') . "\n";
        
        // Check if visitor_from looks like a bp_code
        $looksLikeBpCode = strlen($visitor->visitor_from) <= 10 && 
                          strtoupper($visitor->visitor_from) === $visitor->visitor_from && 
                          !str_contains($visitor->visitor_from, ' ');
        
        if ($looksLikeBpCode && $visitor->bp_code === null) {
            // This looks like a bp_code, try to find the supplier
            $supplier = BusinessPartner::where('bp_code', $visitor->visitor_from)
                ->where('bp_status_desc', 'Active')
                ->first();
            
            if ($supplier) {
                // Update the visitor data
                $visitor->update([
                    'visitor_from' => $supplier->bp_name,
                    'bp_code' => $visitor->visitor_from
                ]);
                
                echo "  ✓ Fixed: visitor_from = {$supplier->bp_name}, bp_code = {$visitor->visitor_from}\n";
                $fixedCount++;
            } else {
                echo "  ✗ Supplier not found for bp_code: {$visitor->visitor_from}\n";
                $skippedCount++;
            }
        } else {
            echo "  - No fix needed (already correct or not a bp_code)\n";
            $skippedCount++;
        }
        echo "\n";
    }
    
    echo "=== Fix completed! ===\n";
    echo "✓ Fixed: {$fixedCount} visitors\n";
    echo "✗ Skipped: {$skippedCount} visitors\n\n";
    
    // Test the fixed data
    echo "2. Testing fixed data...\n";
    $testVisitor = Visitor::where('visitor_id', 'DL250054')->first();
    if ($testVisitor) {
        echo "✓ Test visitor DL250054:\n";
        echo "  - visitor_from: {$testVisitor->visitor_from}\n";
        echo "  - bp_code: " . ($testVisitor->bp_code ?? 'NULL') . "\n";
        echo "  - Print will show: {$testVisitor->visitor_from}\n";
    }
    
    echo "\n=== All done! ===\n";
    echo "Now print receipt should display company names correctly.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
