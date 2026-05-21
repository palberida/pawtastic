<?php

namespace App\Http\Controllers;


use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{

    public function index(Request $request)
    {
        $expenses = DB::table('expenses')
        ->select(
            'expenses.*'
        )
        ->orderBy('dia')
        ->get(); 
        logger('REPORT EXPENSES');
        //logger($sql = $products->toSql());
        return view('expenses.index', compact('expenses'));
        
    }

    public function edit(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, $id)
    {

        // Validate the input
        $request->validate([
            'valor' => 'required|numeric',
            'moneda' => 'required|string|max:10',
            'dia' => 'required|date',
            'tipo' => 'required|string|max:50',
            'tipo_pago' => 'required|string|max:50',
            'proveedor' => 'nullable|string|max:250',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $date = Carbon::parse($request->input('dia'));
        $start_date = $date;
        $end_date = null;
        if($request->input('tipo_pago') == "unico"){
            if ($request->input('tipo') == "mensual"){
                $start_date = $date->startOfMonth()->format('Y-m-d');
                $end_date = $date->endOfMonth()->format('Y-m-d');
            }

            if ($request->input('tipo') == "anual"){
                $start_date = $date->startOfYear()->format('Y-m-d');
                $end_date =  $date->endOfYear()->format('Y-m-d');
            }
        }
        if($request->input('tipo_pago') == "recurrente"){
            $dateInput = $request->input('fin');
            $end_date = $dateInput ? Carbon::parse($dateInput) : null;
        }
        $expense = Expense::findOrFail($id); 
        //echo $start_date; die;
        $expense->update([
            'valor' => $request->input('valor'),
            'moneda' => $request->input('moneda'),
            'dia' => Carbon::parse($request->input('dia'))->format('Y-m-d'),
            'tipo' => $request->input('tipo'),
            'tipo_pago' => $request->input('tipo_pago'),
            'proveedor' => $request->input('proveedor'),
            'descripcion' => $request->input('descripcion'),
            'inicio' => $start_date,
            'fin' => $end_date,
        ]);
        
        //$expenses->update($request->all());   
        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully');
    }


    // Show the form to add a new expense
    public function new()
    {
        return view('expenses.new');
    }

    // Store the new expense in the database
    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            'valor' => 'required|numeric',
            'moneda' => 'required|string|max:10',
            'dia' => 'required|date',
            'tipo' => 'required|string|max:50',
            'tipo_pago' => 'required|string|max:50',
            'proveedor' => 'nullable|string|max:250',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $date = Carbon::parse($request->input('dia'));
        $start_date = $date;
        $end_date = null;
        if($request->input('tipo_pago') == "unico"){
            if ($request->input('tipo') == "mensual"){
                $start_date = $date->startOfMonth()->format('Y-m-d');
                $end_date = $date->endOfMonth()->format('Y-m-d');
            }

            if ($request->input('tipo') == "anual"){
                $start_date = $date->startOfYear()->format('Y-m-d');
                $end_date =  $date->endOfYear()->format('Y-m-d');
            }
        }

        // Create a new expense
        Expense::create([
            'valor' => $request->input('valor'),
            'moneda' => $request->input('moneda'),
            'dia' => Carbon::parse($request->input('dia'))->format('Y-m-d'),
            'tipo' => $request->input('tipo'),
            'tipo_pago' => $request->input('tipo_pago'),
            'proveedor' => $request->input('proveedor'),
            'descripcion' => $request->input('descripcion'),
            'inicio' => $start_date,
            'fin' => $end_date,
        ]);

        // Redirect back to the form with a success message
        return redirect()->route('expenses.index')->with('success', 'Expense added successfully!');
    }

    


}
    
