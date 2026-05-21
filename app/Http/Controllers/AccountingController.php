<?php

namespace App\Http\Controllers;


use App\Models\OrderProblem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{

    public function index(Request $request)
    {
        $searchFecha = $request->input('search_fecha');
        $searchFechaIncio = $request->input('search_fecha_inicio');
        $searchFechaFin = $request->input('search_fecha_fin');
        if($searchFechaIncio || $searchFechaFin ){
            $searchFecha = null;
        }elseif(!$searchFecha ){
            $searchFecha = "today";
        }elseif($request->input('search_fecha') == "lifetime"){
            $searchFecha = null;
        }

        $filters = '1 = 1 ';
        if($searchFechaIncio){
            $filters .= "and DATE(a.fecha_transaccion) >= '$searchFechaIncio'";
        }
        if($searchFechaFin){
            $filters .= "and DATE(a.fecha_transaccion) <= '$searchFechaFin'";
        }
        if(!$searchFechaIncio && !$searchFechaFin){
            switch ($searchFecha) {
                case 'today':
                    $filters .= "AND DATE(a.fecha_transaccion) = '" . now()->format('Y-m-d') . "'";
                    break;

                case 'yesterday':
                    $filters .= "AND DATE(a.fecha_transaccion) = '" . now()->subDay()->format('Y-m-d') . "'";
                    break;

                case 'this_week':
                    $startOfWeek = now()->startOfWeek()->format('Y-m-d');
                    $endOfWeek = now()->endOfWeek()->format('Y-m-d');
                    $filters .= "AND DATE(a.fecha_transaccion) BETWEEN '$startOfWeek' AND '$endOfWeek'";
                    break;

                case 'last_week':
                    $startOfLastWeek = now()->startOfWeek()->subWeek()->format('Y-m-d');
                    $endOfLastWeek = now()->endOfWeek()->subWeek()->format('Y-m-d');
                    $filters .= "AND DATE(a.fecha_transaccion) BETWEEN '$startOfLastWeek' AND '$endOfLastWeek'";
                    break;

                case 'this_month':
                    $year = now()->year;
                    $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
                    $filters .= "AND YEAR(a.fecha_transaccion) = $year AND MONTH(a.fecha_transaccion) = $month";
                    break;

                case 'last_month':
                    $year = now()->copy()->startOfMonth()->subMonth()->year;
                    $month = str_pad(now()->copy()->startOfMonth()->subMonth()->month, 2, '0', STR_PAD_LEFT);
                    $filters .= "AND YEAR(a.fecha_transaccion) = $year AND MONTH(a.fecha_transaccion) = $month";
                    break;

                case 'this_year':
                    $filters .= "AND YEAR(a.fecha_transaccion) = " . now()->year;
                    break;

                case 'last_year':
                    $filters .= "AND YEAR(a.fecha_transaccion) = " . now()->subYear()->year;
                    break;
            }
        }

        /*$orders = DB::table('bank_statements as a')
            ->select([
                'a.fecha_transaccion',
                'a.tipo_transaccion',
                'a.descripcion',
                'a.numero_documento',
                'a.total',
                DB::raw("GROUP_CONCAT(DISTINCT d.autorizacion ORDER BY d.autorizacion SEPARATOR ', ') as autorizaciones"),
            ])
            ->leftJoin(DB::raw("(
                SELECT 
                    b.fecha_transaccion, 
                    b.numero_operacion, 
                    ROUND(SUM(total), 2) AS total
                FROM 
                    shipments_cod b 
                GROUP BY 
                    b.fecha_transaccion, b.numero_operacion
            ) as x"), function ($join) {
                $join->on(DB::raw("DATE(a.fecha_transaccion)"), '=', DB::raw("DATE(x.fecha_transaccion)"))
                    ->whereRaw("ABS(a.total - x.total) < 0.1");
            })
            ->leftJoin('shipments_cod as c', 'x.numero_operacion', '=', 'c.numero_operacion')
            ->leftJoin('orders as d', 'd.guia', '=', 'c.guia')
            ->whereRaw($filters)
            ->groupBy(
                'a.fecha_transaccion',
                'a.tipo_transaccion',
                'a.descripcion',
                'a.numero_documento',
                'a.total'
            )
            ->get();*/

            $orders = DB::table('bank_statements as a')
            ->select([
                'a.id',
                'a.fecha_transaccion',
                'a.tipo_transaccion',
                'a.descripcion',
                'a.numero_documento',
                'a.total',
                DB::raw("GROUP_CONCAT(DISTINCT d.autorizacion ORDER BY d.autorizacion SEPARATOR ', ') as autorizaciones"),
            ])
            ->leftJoin('orders as d', 'd.bank_statement_id', '=', 'a.id')
            ->whereRaw($filters)
            ->groupBy(
                'a.fecha_transaccion',
                'a.tipo_transaccion',
                'a.descripcion',
                'a.numero_documento',
                'a.total'
            )
            ->get();
            logger('Accounting');
            return view('accounting.index', compact('orders'));
                
    }

    public function invoices_date(Request $request)
    {
        return view('accounting.setdate');
    }

    public function invoices_date_save(Request $request)
    {
        logger('SET DATE AUTO ');

        $request->validate([
            'fecha' => 'required|date',
        ]);

      
        $fecha = date('Y-m-d H:i:s', strtotime($request->input('fecha')));

        try {
            DB::beginTransaction();


            DB::table('auto_invoice_date')->updateOrInsert([], ['fecha' => $fecha]);

            DB::commit();

            return redirect()->back()->with('success', 'Fecha actualizada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('errors', 'Error al actualizar la fecha.');
        }
        
    }
}
    
