<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Item;
use App\Models\Variant;
use App\Models\Product;
use App\Models\InventoryHistory;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Exception;
use DateTime;
use Illuminate\Support\Facades\DB;
use PDF; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class ReportInventoryController extends Controller
{
    public function index(Request $request)
    {
       

        $results = DB::table('variants as a')
    ->join('products as b', 'a.id_producto', '=', 'b.id')
    ->select(
        'b.descripcion as product_description',
        'a.descripcion as variant_description',
        'a.stock',
        DB::raw('DATE_SUB(CURDATE(), INTERVAL 1 DAY) as inventario')
    )
    ->orderBy('b.id')
    ->orderBy('a.id')
    ->get();



        logger('INVENTORY');
        //logger($sql = $products->toSql());
        return view('reportinventory.index', compact('results'));
        
    }

    public function export(Request $request)
    {
        $results = DB::table('variants as a')
    ->join('products as b', 'a.id_producto', '=', 'b.id')
    ->select(
        'b.descripcion as product_description',
        'a.descripcion as variant_description',
        'a.stock',
        DB::raw('DATE_SUB(CURDATE(), INTERVAL 1 DAY) as inventario')
    )
    ->orderBy('b.id')
    ->orderBy('a.id')
    ->get();

        logger('INVENTORY CSV');
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_report.csv"',
        ];

        $columns = [
            'product_description',
            'variant_description',
            'stock'
        ];

        $callback = function () use ($results, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns, ';');

            foreach ($results as $row) {
                fputcsv($file, [
                    $row->product_description,
                    $row->variant_description,
                    
                    $row->stock,

                ], ';');
            }

            fclose($file);
        };
            return response()->stream($callback, 200, $headers);
        }

    public function history(Request $request)
    {
        $search_fecha_inicio = $request->input('search_fecha_inicio', Carbon::now()->format('Y-m')); 
        $startOfMonth = Carbon::createFromFormat('Y-m', $search_fecha_inicio)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y-m', $search_fecha_inicio)->endOfMonth();
        $results = InventoryHistory::query()
        ->select('inventory_history.id', 'inventory_history.stock', 'inventory_history.fecha', 'inventory_history.producto as product_name', 'inventory_history.variante as variant_name')
        ->whereBetween('fecha', [$startOfMonth, $endOfMonth])
        ->orderBy('product_name')
        ->orderBy('variant_name')
        ->paginate(100);
        return view('reportinventory.history', compact('results'));
    }



    public function history_export(Request $request)
    {
        $search_fecha_inicio = $request->input('search_fecha_inicio', Carbon::now()->format('Y-m')); 
        $startOfMonth = Carbon::createFromFormat('Y-m', $search_fecha_inicio)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y-m', $search_fecha_inicio)->endOfMonth();

        $results = InventoryHistory::query()
            ->select('inventory_history.id', 'inventory_history.stock', 'inventory_history.fecha', 'inventory_history.producto as product_name', 'inventory_history.variante as variant_name')
            ->whereBetween('fecha', [$startOfMonth, $endOfMonth])
            ->orderBy('product_name')
            ->orderBy('variant_name')
            ->get();

        $filename = "inventory_history_" . $startOfMonth->format('Y_m') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($results) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha','Producto', 'Variante','Stock'  ]);
            foreach ($results as $row) {
                fputcsv($handle, [
                    $row->fecha,
                    $row->product_name,
                    $row->variant_name,
                    $row->stock

                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

   

}
    
