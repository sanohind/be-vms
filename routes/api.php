<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VisitorController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\BusinessPartnerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\DeliveryController;

// List all visitors from today (index)
Route::get('/visitor', [VisitorController::class, 'index']);

// Store a new visitor (check-in)
Route::post('/create', [VisitorController::class, 'store']);

// Update an existing visitor to set checkout time
Route::put('/checkout/{visitor_id}', [VisitorController::class, 'update']);

// Print using Html2pdf.js
Route::get('/print/{visitor_id}', [VisitorController::class, 'printVisitor']);
Route::get('/print-data/{visitor_id}', [VisitorController::class, 'getPrintData']);

// For display all data without orderBy
Route::get('/index', [VisitorController::class, 'display']);

// List all employees
Route::get('/employee', [EmployeeController::class, 'index']);

// Store a new employee
Route::post('/createemployee', [EmployeeController::class, 'store']);

// Show employee data by nik
Route::get('/edit/{nik}', [EmployeeController::class, 'show']);

// Update employee data by nik
Route::put('/update/{nik}', [EmployeeController::class, 'update']);

// Delete employee data by nik
Route::delete('/delete/{nik}', [EmployeeController::class, 'destroy']);

// Supplier routes (simplified for dropdown)
Route::prefix('supplier')->group(function () {
    // Get suppliers for dropdown selection
    Route::get('/', [SupplierController::class, 'getSuppliers']);
    
    // Search suppliers with autocomplete
    Route::get('/search', [SupplierController::class, 'searchSuppliers']);
    
    // Get specific supplier by code
    Route::get('/{bpCode}', [SupplierController::class, 'getSupplierByCode']);
    
    // Test database connection
    Route::get('/test-connection', [SupplierController::class, 'testConnection']);
});

// Business Partner routes (full functionality)
Route::prefix('business-partner')->group(function () {
    // Get all business partners with optional search and pagination
    Route::get('/', [BusinessPartnerController::class, 'index']);
    
    // Search business partners
    Route::get('/search', [BusinessPartnerController::class, 'search']);
    
    // Get business partners by type
    Route::get('/type/{type}', [BusinessPartnerController::class, 'getByType']);
    
    // Get active business partners only
    Route::get('/active', [BusinessPartnerController::class, 'getActive']);
    
    // Test database connection
    Route::get('/test-connection', [BusinessPartnerController::class, 'testConnection']);
    
    // Update parent-child relationships (admin function)
    Route::post('/update-relationships', [BusinessPartnerController::class, 'updateRelationships']);
    
    // Get specific business partner with unified data
    Route::get('/{bpCode}', [BusinessPartnerController::class, 'show']);
    
    // Get visitors for a specific business partner
    Route::get('/{bpCode}/visitors', [BusinessPartnerController::class, 'getVisitors']);
    
    // Get dashboard data for a specific business partner
    Route::get('/{bpCode}/dashboard', [BusinessPartnerController::class, 'getDashboard']);
});

// Delivery routes
Route::prefix('delivery')->group(function () {
    // Get delivery data for today from dn_header table
    Route::get('/today', [DeliveryController::class, 'getTodayDelivery']);
});

