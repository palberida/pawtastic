<?php

namespace App\Http\Controllers;


use App\Models\OrderProblem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderProblemController extends Controller
{

    public function index(Request $request)
    {
        $problems = DB::table('order_problems')
        ->select(
            'order_problems.*'
        )
        ->orderBy('dia','desc')
        ->get(); 
        //logger('REPORT EXPENSES');
        //logger($sql = $products->toSql());
        return view('orderproblems.index', compact('problems'));
        
    }

    public function edit(Request $request, $id)
    {
        $problem = OrderProblem::findOrFail($id);
        return view('orderproblems.edit', compact('problem'));
    }

    public function update(Request $request, $id)
    {

        // Validate the input
        $request->validate([
            
            'id_orden' => 'required|string|max:10',
            'dia' => 'required|date',
            'tipo' => 'required|string|max:50',
            'notas' => 'nullable|string|max:250',
        ]);

        $date = Carbon::parse($request->input('dia'));
        
        $problem = OrderProblem::findOrFail($id); 
        $problem->update([
            'id_orden' => $request->input('id_orden'),
            'dia' => Carbon::parse($request->input('dia'))->format('Y-m-d'),
            'tipo' => $request->input('tipo'),
            'notas' => $request->input('notas')
        ]);
        
        
        return redirect()->route('order-problems.index')->with('success', 'Expense updated successfully');
    }


    // Show the form to add a new expense
    public function new()
    {
        return view('orderproblems.new');
    }

    // Store the new expense in the database
    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            
            'id_orden' => 'required|string|max:10',
            'dia' => 'required|date',
            'tipo' => 'required|string|max:50',
            'notas' => 'nullable|string|max:250',
        ]);

        $date = Carbon::parse($request->input('dia'));
        
        // Create a new expense
        OrderProblem::create([
            'id_orden' => $request->input('id_orden'),
            'dia' => Carbon::parse($request->input('dia'))->format('Y-m-d'),
            'tipo' => $request->input('tipo'),
            'notas' => $request->input('notas')
        ]);

        // Redirect back to the form with a success message
        return redirect()->route('order-problems.index')->with('success', 'Order Problem added successfully!');
    }

    


}
    
