<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Visitor;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use App\Http\Resources\VisitorResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use charlieuki\ReceiptPrinter\ReceiptPrinter as ReceiptPrinter;

class VisitorController
{
    // View List Data Visitor
    public function index()
    {
        // Use visitor connection which is specifically configured for visitor database
        $data_visitor = Visitor::whereDate('visitor_date', Carbon::today())
            ->orderby('visitor_checkin', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Display List Visitor Successfully',
            'data' => VisitorResource::collection($data_visitor)
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'visitor_name'    => 'required|string|max:255',
            'visitor_date'    => 'required|date',
            'visitor_from'    => 'nullable|string|max:255',
            'visitor_host'    => 'required|string|max:255',
            'visitor_needs'   => 'nullable|string|max:255',
            'visitor_amount'  => 'nullable|integer',
            'visitor_vehicle' => 'nullable|string|max:10',
            'plan_delivery_time' => 'nullable|string|max:8',
        ]);

        // Determine the prefix based on visitor needs
        $prefix = '';
        switch ($request->visitor_needs) {
            case 'Meeting':
                $prefix = 'MT';
                break;
            case 'Delivery':
                $prefix = 'DL';
                break;
            case 'Contractor':
                $prefix = 'CT';
                break;
            case 'Sortir':
                $prefix = 'ST';
                break;
            default:
                $prefix = 'VG';
        }

        // Get the current year in two-digit format
        $currentYearShort = Carbon::now()->format('y'); // e.g., '24' for 2024

        // Construct the visitorPrefix including the year
        $visitorPrefix = $prefix . $currentYearShort; // e.g., 'MT24'

        // Retrieve the latest visitor ID with the same prefix
        // Use 'visitor' connection which is specifically configured for visitor database
        $latestVisitorData = DB::connection('visitor')
            ->table('visitor')
            ->where('visitor_id', 'like', "$visitorPrefix%")
            ->orderBy('visitor_id', 'desc')
            ->first();

        // Calculate the new visitor number
        $newNumber = $latestVisitorData
            ? ((int)substr($latestVisitorData->visitor_id, strlen($visitorPrefix))) + 1
            : 1;

        // Create the new visitor ID
        $visitorId = $visitorPrefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // Handle bp_code and visitor_from based on visitor_needs
        $bpCode = null;
        $visitorFrom = $request->visitor_from;
        
        if ($request->visitor_needs === 'Delivery' && !empty($request->visitor_from)) {
            // For Delivery visitors, visitor_from contains bp_code
            $bpCode = $request->visitor_from;
            
            // Get the actual company name from business_partner table
            $supplier = BusinessPartner::select('bp_name')
                ->where('bp_code', $request->visitor_from)
                ->where('bp_status_desc', 'Active')
                ->first();
            
            if ($supplier) {
                $visitorFrom = $supplier->bp_name;
            }
        }

        // Create the visitor record in the database using 'visitor' connection
        DB::connection('visitor')->table('visitor')->insert([
            'visitor_id'       => $visitorId,
            'visitor_name'     => $request->visitor_name,
            'visitor_from'     => $visitorFrom,
            'bp_code'          => $bpCode,
            'visitor_host'     => $request->visitor_host,
            'visitor_needs'    => $request->visitor_needs,
            'visitor_amount'   => $request->visitor_amount,
            'visitor_vehicle'  => $request->visitor_vehicle,
            'plan_delivery_time' => $request->plan_delivery_time,
            'department'       => $request->department ?? '',
            'visitor_date'     => Carbon::today(),
            'visitor_checkin'  => Carbon::now(),
        ]);
        
        // Fetch the created visitor (model already uses 'visitor' connection)
        $visitor = Visitor::find($visitorId);

        // Return a JSON response without QR code or view rendering
        return response()->json([
            'success' => true,
            'message' => "\"{$visitor->visitor_name}\" Check In",
            'data'    => VisitorResource::make($visitor)
        ]);
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'visitor_name'    => 'required|string|max:255',
    //         'visitor_date'    => 'required|date',
    //         'visitor_from'    => 'nullable|string|max:255',
    //         'visitor_host'    => 'required|string|max:255',
    //         'visitor_needs'   => 'nullable|string|max:255',
    //         'visitor_amount'  => 'nullable|integer',
    //         'visitor_vehicle' => 'nullable|string|max:10',
    //     ]);

    //     $prefix = '';
    //     switch ($request->visitor_needs) {
    //         case 'Meeting':
    //             $prefix = 'MT';
    //             break;
    //         case 'Delivery':
    //             $prefix = 'DL';
    //             break;
    //         case 'Contractor':
    //             $prefix = 'CT';
    //             break;
    //         default:
    //             $prefix = 'VG';
    //     }

    //     $latestVisitor = Visitor::where('visitor_id', 'like', "$prefix%")
    //         ->orderBy('visitor_id', 'desc')
    //         ->first();

    //     $newNumber = $latestVisitor
    //         ? ((int)substr($latestVisitor->visitor_id, 2)) + 1
    //         : 1;

    //     $visitorId = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

    //     $visitor = Visitor::create([
    //         'visitor_id'       => $visitorId,
    //         'visitor_name'     => $request->visitor_name,
    //         'visitor_from'     => $request->visitor_from,
    //         'visitor_host'     => $request->visitor_host,
    //         'visitor_needs'    => $request->visitor_needs,
    //         'visitor_amount'   => $request->visitor_amount,
    //         'visitor_vehicle'  => $request->visitor_vehicle,
    //         'department'       => $request->department,
    //         'visitor_date'     => Carbon::today(),
    //         'visitor_checkin'  => Carbon::now(),
    //     ]);

    //     // Generate QR code data URL
    //     $qrCode = new QrCode($visitor->visitor_id);
    //     $writer = new PngWriter();
    //     $qrCodeData = $writer->write($qrCode)->getString();
    //     $qrCodeDataUrl = 'data:image/png;base64,' . base64_encode($qrCodeData);

    //     // Return the Blade view that includes the JavaScript for printing
    //     return view('print_receipt', [
    //         'visitor'       => $visitor,
    //         'qrCodeDataUrl' => $qrCodeDataUrl
    //     ]);
    // }

    public function update($visitor_id)
    {
        // Use visitor connection which is specifically configured for visitor database
        $visitor = Visitor::where('visitor_id', $visitor_id)->firstOrFail();

        $visitor->update([
            'visitor_checkout' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => '"' . $visitor->visitor_name . '" Check Out',
            'data' => new VisitorResource($visitor)
        ]);
    }

    public function printVisitor($visitor_id)
    {
        // Fetch visitor data based on the visitor ID
        // Use visitor connection which is specifically configured for visitor database
        $visitor = Visitor::find($visitor_id);

        if (!$visitor) {
            return response()->json(['error' => 'Visitor not found'], 404);
        }

        // Generate QR code data URL
        $qrCode = new QrCode($visitor->visitor_id);
        $writer = new PngWriter();
        $qrCodeData = $writer->write($qrCode)->getString();
        $qrCodeDataUrl = 'data:image/png;base64,' . base64_encode($qrCodeData);

        // Return the Blade view that includes the JavaScript for printing
        return view('print_receipt', [
            'visitor'       => $visitor,
            'qrCodeDataUrl' => $qrCodeDataUrl
        ]);
    }

    public function getPrintData($visitor_id)
    {
        // Fetch visitor data based on the visitor ID
        // Use visitor connection which is specifically configured for visitor database
        $visitor = Visitor::find($visitor_id);

        if (!$visitor) {
            return response()->json(['error' => 'Visitor not found'], 404);
        }

        // Return JSON data for frontend
        return response()->json([
            'success' => true,
            'data' => [
                'visitor_id' => $visitor->visitor_id,
                'visitor_date' => $visitor->visitor_date,
                'visitor_name' => $visitor->visitor_name,
                'visitor_from' => $visitor->visitor_from,
                'visitor_host' => $visitor->visitor_host,
                'visitor_needs' => $visitor->visitor_needs,
                'visitor_amount' => $visitor->visitor_amount,
                'visitor_vehicle' => $visitor->visitor_vehicle,
                'plan_delivery_time' => $visitor->plan_delivery_time,
                'department' => $visitor->department,
                'visitor_checkin' => $visitor->visitor_checkin,
                'visitor_checkout' => $visitor->visitor_checkout,
                'bp_code' => $visitor->bp_code
            ]
        ]);
    }

    public function display()
    {
        // Use visitor connection which is specifically configured for visitor database
        $data_visitor = Visitor::with('visitor')->get();

        return response()->json([
            'success' => true,
            'message' => 'Display List Visitor Successfully',
            'data' => VisitorResource::collection($data_visitor)
        ]);
    }
}
