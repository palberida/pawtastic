<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index()
    {
        $shipments = Shipment::all(); // Fetch all records
        return view('shipments.index', compact('shipments')); // Pass the records to the view
    }

    public function show($id)
    {
        $shipment = Shipment::findOrFail($id); // Find the record by ID
        return view('shipments.show', compact('shipment')); // Pass the record to the view
    }

    public function edit($id)
    {
        $shipment = Shipment::findOrFail($id); // Find the record by ID
        return view('shipments.edit', compact('shipment')); // Pass the record to the view
    }

    public function update(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id); // Find the record by ID
        $shipment->update($request->all()); // Update the record with request data
        return redirect()->route('shipments.index')->with('success', 'Record updated successfully.');
    }

    public function destroy($id)
    {
        $shipment = Shipment::findOrFail($id); // Find the record by ID
        $shipment->delete(); // Delete the record
        return redirect()->route('shipments.index')->with('success', 'Record deleted successfully.');
    }

    public function create()
    {
        return view('shipments.create'); // Return the view for creating a record
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'nombre_cliente' => 'required|string|max:150',
            'direccion_cliente' => 'required|string|max:350',
            'telefono_cliente' => 'required|string|max:12',
            'mensajero' => 'required|string|max:30',
            'costo_aproximado' => 'nullable|float'
        ]);

        // Create a new record with the validated data
        Shipment::create($validated);

        // Redirect to the records index with a success message
        return redirect()->route('shipments.index')->with('success', 'Record created successfully.');
    }
}
