<?php

namespace App\Http\Controllers;


use App\Models\BankAccount;
use App\Models\BankStatement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankAccountsController extends Controller
{

    public function index(Request $request)
    {
        $expenses = DB::table('bank_accounts')
        ->select(
            'bank_accounts.*'
        )
        ->orderBy('mes')
        ->get(); 
        
        //logger($sql = $products->toSql());
        return view('bankaccounts.index', compact('expenses'));
        
    }

    public function edit(Request $request, $id)
    {
        $expense = BankAccount::findOrFail($id);
        return view('bankaccounts.edit', compact('expense'));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'ingresos' => 'required|numeric',
            'egresos' => 'required|numeric',
            'mes' => 'required|date',
        ]);

        $date = Carbon::parse($request->input('mes'));
        
        $expense = BankAccount::findOrFail($id); 
        //echo $start_date; die;
        $expense->update([
            'ingresos' => $request->input('ingresos'),
            'egresos' => $request->input('egresos'),
            'mes' => $date,
            
        ]);

        
        //$expenses->update($request->all());   
        return redirect()->route('bank-accounts.index')->with('success', ' Totales mensuales actualizados exitosamente.');
    }


    // Show the form to add a new expense
    public function new()
    {
        return view('bankaccounts.new');
    }

    // Store the new expense in the database
    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            'ingresos' => 'required|numeric',
            'egresos' => 'required|numeric',
            'mes' => 'required|date',
        ]);

        $date = Carbon::parse($request->input('mes'));
       

        // Create a new expense
        BankAccount::create([
            'ingresos' => $request->input('ingresos'),
            'egresos' => $request->input('egresos'),
            'mes' => $date,
            
        ]);

        // Redirect back to the form with a success message
        return redirect()->route('bank-accounts.index')->with('success', 'Totales guardados exitosamente');
    }

    public function create()
    {
        return view('bankaccounts.create');
    }

    function upload(Request $request)
    {
        
        logger('IMPORT Bank Statements');
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);
        $file = $request->file('file');
        DB::beginTransaction();
        try {
            
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                $expectedHeaders = ['Fecha', 'TT', 'Descripción', 'No. Doc', 'Debe (GTQ)', 'Haber (GTQ)', 'Saldo (GTQ)'];
                $start_importing = 0;
                while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                    $cleanRow = array_filter($data, fn($value) => !is_null($value) && trim($value) !== '');
                    
                    if (count($cleanRow) === count($expectedHeaders)) {
                        $start_importing = true;
                        continue;
                    }
                    
                    if($start_importing && isset($data[6])){
                        logger(print_r(count($data),true));
                        logger(count($expectedHeaders));
                        if(trim($data[1]) == 'NC')
                            $total = isset($data[5]) ? floatval(str_replace(',', '', $data[5])) : 0;
                        else
                            $total = isset($data[4]) ? floatval(str_replace(',', '', $data[4])) : 0;
                        BankStatement::updateOrCreate(
                            ['numero_documento' => trim($data[3])],
                            [
                                'fecha_transaccion' => \Carbon\Carbon::createFromFormat('d-m-Y', trim($data[0])),
                                'tipo_transaccion' => trim($data[1]),
                                'descripcion' => trim($data[2]),
                                'total' => $total
                            ]
                        );
                        //logger('BANK transaction added ');
                    }
                }
                fclose($handle);
            }

            //calculate invoices
            $orderController = new OrderController();
            $orderController->calculate_invoices();

            // Commit the transaction
            DB::commit();
            return redirect()->route('bank-accounts.create')->with('success', 'Transacciones banacarios actualizados exitosamente!');
        } catch (Exception $e) {
            // Rollback if there's an error
            DB::rollBack();
            echo "Failed to import data: " . $e->getMessage();
        }

        
        return redirect()->route('bank-accounts.create')->with('error', 'Ocurrió un error al actualizar las transacciones.');

    }

}
    
