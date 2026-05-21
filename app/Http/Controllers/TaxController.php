<?php

namespace App\Http\Controllers;


use App\Models\Tax;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TaxController extends Controller
{

    public function index(Request $request)
    {
        $taxes = DB::table('taxes')
        ->select(
            'taxes.*'
        )
        ->orderBy('fecha')
        ->get(); 
        
        //logger($sql = $products->toSql());
        return view('taxes.index', compact('taxes'));
    }

    public function create()
    {
        return view('taxes.create');
    }

    function upload(Request $request)
    {
        logger('IMPORT TAX Statements');
        $request->validate([
            'file' => 'required|mimes:csv,txt,xls,xlsx|max:2048',
        ]);
        $file = $request->file('file');
        DB::beginTransaction();
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $expectedHeaders = ['NÚMERO FORMULARIO', 'NÚMERO DE ACCESO', 'BANCO', 'ORIGEN', 'FECHA PRESENTACIÓN', 'MONTO'];
            $start_importing = false;
            $processed = 0;
            foreach ($sheet->toArray() as $index => $data) {
                //while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                    $cleanRow = array_filter($data, fn($value) => !is_null($value) && trim($value) !== '');
                    $lastIndex = array_key_last($cleanRow);
                    
                    if (!$start_importing && $lastIndex >= count($expectedHeaders) ) {
                        $start_importing = true;
                        continue;
                    }
                    
                    if($start_importing){
                        
                        //logger(count($expectedHeaders));
                        $total = isset($data[6]) ? floatval(str_replace(',', '', $data[6])) : 0;
                        $fecha = trim($data[5]);
                        //$fecha = preg_replace('/[^\d\/]/', '', $fecha);
                        
                        
                        Tax::updateOrCreate(
                            ['numero_formulario' => trim($data[1])],
                            [
                                'numero_acceso' => trim($data[2] ?? ''),
                                'banco' => trim($data[3] ?? ''),
                                'origen' => trim($data[4]  ?? ''),
                                'fecha_transaccion' => \Carbon\Carbon::createFromFormat('d/m/Y', trim($fecha)),
                                'total' => $total
                            ]
                        );
                        $processed = $processed + $total;
                        //logger('BANK transaction added ');
                    }               
            }

            DB::commit();
            return redirect()->route('taxes.create')->with('success', 'Q.' . $processed . ' en impuestos actualizados exitosamente!');
        } catch (Exception $e) {
            // Rollback if there's an error
            DB::rollBack();
            echo "Failed to import data: " . $e->getMessage();
        }

        
        return redirect()->route('taxes.create')->with('error', 'Ocurrió un error al actualizar las transacciones.');

    }

}
    
