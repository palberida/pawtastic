<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Item;
use App\Models\Variant;
use App\Models\Product;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Exception;
use DateTime;
use Illuminate\Support\Facades\DB;
use PDF; 
use Carbon\Carbon;

class ReportProfitController extends Controller
{
    public function index(Request $request)
    {
        $searchFecha = $request->input('search_fecha');
        $searchFechaIncio = $request->input('search_fecha_inicio');
        $searchFechaFin = $request->input('search_fecha_fin');
        $orders = Order::query();
        if($searchFechaIncio || $searchFechaFin ){
            $searchFecha = null;
        }elseif(!$searchFecha ){
            $searchFecha = "today";
            //$searchFecha = null;
        }elseif($request->input('search_fecha') == "lifetime"){
            $searchFecha = null;
        }


        $last_update = DB::table('ad_costs')
        ->select(
            DB::raw("max(
                created_at
            ) as ultimo")
        )
        ->first();
        $lastUpdateDate = $last_update->ultimo ? Carbon::parse($last_update->ultimo) : null;
        $daysDifference = $lastUpdateDate ? $lastUpdateDate->diffInDays(date('Y-m-d')) + 1 : 9999;
        $scriptPath = base_path('scripts/fetch_ads.php');
        $output = shell_exec("php $scriptPath $daysDifference");
        
        $total_revenue = DB::table('orders')
        
        ->select(
            DB::raw("sum(
                CASE
                    WHEN forma_pago = 'Cash on Delivery (COD)' and (mensajero = 'CAEX' || mensajero = 'FORZA') THEN total - (total * 0.04)
                    WHEN forma_pago = 'cyber_source' THEN  total - (total * 0.045)
                    WHEN forma_pago = 'Link de Pago con VisaNet' THEN  total - (total * 0.065)
                    ELSE total
                END
            
            
            ) as total"), 
            DB::raw('count(*) as conteo'), 
        )
        ->where('orders.estado','!=','cancelado')
        ->when($searchFecha, function ($query, $search_fecha) {
            if($search_fecha === 'today'){
                $query->whereDate('orders.created_at', now()->format('Y-m-d'));
            }
            if($search_fecha === 'yesterday'){
                $query->whereDate('orders.created_at', now()->subDay()->format('Y-m-d'));
            }
            if($search_fecha === 'this_week'){
                $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if($search_fecha === 'last_week'){
                $startOfLastWeek = now()->startOfWeek()->subWeek();
                $endOfLastWeek = now()->endOfWeek()->subWeek();
                $query->whereBetween('orders.created_at', [$startOfLastWeek, $endOfLastWeek]);
            }
            if($search_fecha === 'this_month'){
                $query->whereMonth('orders.created_at', now()->month)
                ->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_month'){
                $query->whereMonth('orders.created_at', now()->copy()->startOfMonth()->subMonth()->month)
                ->whereYear('orders.created_at', now()->copy()->startOfMonth()->subMonth()->year);
            }
            if($search_fecha === 'this_year'){
                $query->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_year'){
                $query->whereYear('orders.created_at', now()->subYear()->year);
            }
        })->when($request->input('search_nombre'), function ($query, $search_nombre) {
            $query->where(function ($query) use ($search_nombre) {
                $query->where('products.descripcion', 'like', '%' . $search_nombre . '%');
            });
        })->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            $query->whereDate('orders.created_at', '>=', $search_fecha_inicio);
        })->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            $query->whereDate('orders.created_at', '<=', $search_fecha_fin);
        })
        ->first();
                
        $totalads = DB::table('ad_costs')
        ->join('ads', 'ad_costs.id_ad', '=', 'ads.id')
        ->join('ad_products', 'ads.id', '=', 'ad_products.id_ad')
        ->join('products', 'products.id', '=', 'ad_products.id_producto')
        ->join(DB::raw('(SELECT id_ad, COUNT(*) as total FROM ad_products GROUP BY id_ad) as x'), function ($join) {
            $join->on('x.id_ad', '=', 'ads.id');
        })
        ->select(
            DB::raw('SUM(ad_costs.costo / x.total)  as total')
        )
        ->when($searchFecha, function ($query, $search_fecha) {
            if($search_fecha === 'today'){
                $query->whereDate('ad_costs.dia', now()->format('Y-m-d'));
            }
            if($search_fecha === 'yesterday'){
                $query->whereDate('ad_costs.dia', now()->subDay()->format('Y-m-d'));
            }
            if($search_fecha === 'this_week'){
                $query->whereBetween('ad_costs.dia', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if($search_fecha === 'last_week'){
                $startOfLastWeek = now()->startOfWeek()->subWeek();
                $endOfLastWeek = now()->endOfWeek()->subWeek();
                $query->whereBetween('ad_costs.dia', [$startOfLastWeek, $endOfLastWeek]);
            }
            if($search_fecha === 'this_month'){
                $query->whereMonth('ad_costs.dia', now()->month)
                ->whereYear('ad_costs.dia', now()->year);
            }
            if($search_fecha === 'last_month'){
                $query->whereMonth('ad_costs.dia', now()->copy()->startOfMonth()->subMonth()->month)
                ->whereYear('ad_costs.dia', now()->copy()->startOfMonth()->subMonth()->year);
            }
            if($search_fecha === 'this_year'){
                $query->whereYear('ad_costs.dia', now()->year);
            }
            if($search_fecha === 'last_year'){
                $query->whereYear('ad_costs.dia', now()->subYear()->year);
            }
        })->when($request->input('search_nombre'), function ($query, $search_nombre) {
            $query->where(function ($query) use ($search_nombre) {
                $query->where('products.descripcion', 'like', '%' . $search_nombre . '%');
            });
        })->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            $query->whereDate('ad_costs.dia', '>=', $search_fecha_inicio);
        })->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            $query->whereDate('ad_costs.dia', '<=', $search_fecha_fin);
        })
        ->first();

        $totals = DB::table('orders')
        ->join('items', 'orders.id', '=', 'items.id_orden')
        ->join('variants', 'variants.id', '=', 'items.id_variante')
        ->join('products', 'products.id', '=', 'variants.id_producto')
        ->select(
            DB::raw('sum(cantidad) as total'), 
            DB::raw('sum(items.precio * items.cantidad) as total_precio'), 
            DB::raw('sum(
            (
            CASE
                WHEN variants.costo <= 1 THEN 25
                ELSE variants.costo 
            END
            )
            * items.cantidad) as total_costo'),
            DB::raw('SUM(DISTINCT orders.descuento) as total_descuento')  
        )
        ->where('orders.estado','!=','cancelado')
        ->when($searchFecha, function ($query, $search_fecha) {
            if($search_fecha === 'today'){
                $query->whereDate('orders.created_at', now()->format('Y-m-d'));
            }
            if($search_fecha === 'yesterday'){
                $query->whereDate('orders.created_at', now()->subDay()->format('Y-m-d'));
            }
            if($search_fecha === 'this_week'){
                $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if($search_fecha === 'last_week'){
                $startOfLastWeek = now()->startOfWeek()->subWeek();
                $endOfLastWeek = now()->endOfWeek()->subWeek();
                $query->whereBetween('orders.created_at', [$startOfLastWeek, $endOfLastWeek]);
            }
            if($search_fecha === 'this_month'){
                $query->whereMonth('orders.created_at', now()->month)
                ->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_month'){
                $query->whereMonth('orders.created_at', now()->copy()->startOfMonth()->subMonth()->month)
                ->whereYear('orders.created_at', now()->copy()->startOfMonth()->subMonth()->year);
            }
            if($search_fecha === 'this_year'){
                $query->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_year'){
                $query->whereYear('orders.created_at', now()->subYear()->year);
            }
        })->when($request->input('search_nombre'), function ($query, $search_nombre) {
            $query->where(function ($query) use ($search_nombre) {
                $query->where('products.descripcion', 'like', '%' . $search_nombre . '%');
            });
        })->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            $query->whereDate('orders.created_at', '>=', $search_fecha_inicio);
        })->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            $query->whereDate('orders.created_at', '<=', $search_fecha_fin);
        })
        ->first();



        $start_date = '2020-01-01';
        $end_date = '2099-12-31';
        if($searchFecha) {
            if($searchFecha === 'today'){
                $start_date = now()->format('Y-m-d');
                $end_date = now()->addDay()->format('Y-m-d');
            }
            if($searchFecha === 'yesterday'){
                $start_date = now()->subDays(1)->format('Y-m-d');
                $end_date = now()->format('Y-m-d');
            }
            if($searchFecha === 'this_week'){
                $start_date = now()->startOfWeek()->format('Y-m-d');
                //$end_date = now()->endOfWeek()->format('Y-m-d');
                $end_date = now()->format('Y-m-d');
            }
            if($searchFecha === 'last_week'){
                $start_date = now()->startOfWeek()->subWeek()->format('Y-m-d');
                $end_date = now()->endOfWeek()->subWeek()->format('Y-m-d');
            }
            if($searchFecha === 'this_month'){
                $start_date = now()->startOfMonth()->format('Y-m-d');
                //$end_date = now()->endOfMonth()->format('Y-m-d');
                $end_date = now()->format('Y-m-d');
            }
            if ($searchFecha === 'last_month') {
                $base = now()->copy()->startOfMonth()->subMonth();

                $start_date = $base->format('Y-m-d'); 
                $end_date = $base->copy()->endOfMonth()->format('Y-m-d');
            }
            if($searchFecha === 'this_year'){
                $start_date = now()->startOfYear()->format('Y-m-d');
                //$end_date =  now()->endOfYear()->format('Y-m-d');
                $end_date = now()->format('Y-m-d');
            }
            if($searchFecha === 'last_year'){
                $start_date = now()->subYear()->startOfYear()->format('Y-m-d');
                $end_date =  now()->subYear()->endOfYear()->format('Y-m-d');
            }
        }else{
            $start_date = $searchFechaIncio;
            $end_date =   $searchFechaFin;
        }


       

        
        $totalpayroll = DB::table('payrolls')
        ->select(DB::raw("
            SUM(
                coalesce(GREATEST(0, DATEDIFF(
                    IF('$end_date' < COALESCE(fecha_fin, '2099-01-01'), '$end_date', fecha_fin),
                    IF('$start_date' > fecha_inicio, '$start_date', fecha_inicio)
                )) * 
                salario_base/30,0)
            ) as total
        "))
        //->where('tipo_pago', 'recurrente')
        ->first();

        $totalbonus = DB::table('orders')
        ->join('users', 'orders.vendedor', '=', 'users.seller_code')
        ->join('payrolls', 'users.id', '=', 'payrolls.id_usuario')
        ->select(
            DB::raw("sum(
            CASE
                WHEN tipo_bono = 'porcentaje' THEN ((payrolls.bono_por_orden/100) * (orders.total - 25)) * 1.05
                ELSE payrolls.bono_por_orden
            END
            ) as total")
        )
        ->where('orders.estado','!=','cancelado')
        ->when($searchFecha, function ($query, $search_fecha) {
            if($search_fecha === 'today'){
                $query->whereDate('orders.created_at', now()->format('Y-m-d'));
            }
            if($search_fecha === 'yesterday'){
                $query->whereDate('orders.created_at', now()->subDay()->format('Y-m-d'));
            }
            if($search_fecha === 'this_week'){
                $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if($search_fecha === 'last_week'){
                $startOfLastWeek = now()->startOfWeek()->subWeek();
                $endOfLastWeek = now()->endOfWeek()->subWeek();
                $query->whereBetween('orders.created_at', [$startOfLastWeek, $endOfLastWeek]);
            }
            if($search_fecha === 'this_month'){
                $query->whereMonth('orders.created_at', now()->month)
                ->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_month'){
                $query->whereMonth('orders.created_at', now()->copy()->startOfMonth()->subMonth()->month)
                ->whereYear('orders.created_at', now()->copy()->startOfMonth()->subMonth()->year);
            }
            if($search_fecha === 'this_year'){
                $query->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_year'){
                $query->whereYear('orders.created_at', now()->subYear()->year);
            }
        })->when($request->input('search_nombre'), function ($query, $search_nombre) {
            $query->where(function ($query) use ($search_nombre) {
                $query->where('products.descripcion', 'like', '%' . $search_nombre . '%');
            });
        })->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            $query->whereDate('orders.created_at', '>=', $search_fecha_inicio);
        })->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            $query->whereDate('orders.created_at', '<=', $search_fecha_fin);
        })
        ->first();

        $totalenvios = DB::table('orders')
        ->join('users', 'orders.mensajero', '=', 'users.seller_code')
        ->join('payrolls', 'users.id', '=', 'payrolls.id_usuario')
        ->select(
            DB::raw('sum(payrolls.bono_por_orden) as total')
        )
        ->where('orders.estado','!=','cancelado')
        ->when($searchFecha, function ($query, $search_fecha) {
            if($search_fecha === 'today'){
                $query->whereDate('orders.created_at', now()->format('Y-m-d'));
            }
            if($search_fecha === 'yesterday'){
                $query->whereDate('orders.created_at', now()->subDay()->format('Y-m-d'));
            }
            if($search_fecha === 'this_week'){
                $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if($search_fecha === 'last_week'){
                $startOfLastWeek = now()->startOfWeek()->subWeek();
                $endOfLastWeek = now()->endOfWeek()->subWeek();
                $query->whereBetween('orders.created_at', [$startOfLastWeek, $endOfLastWeek]);
            }
            if($search_fecha === 'this_month'){
                $query->whereMonth('orders.created_at', now()->month)
                ->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_month'){
                $query->whereMonth('orders.created_at', now()->copy()->startOfMonth()->subMonth()->month)
                ->whereYear('orders.created_at', now()->copy()->startOfMonth()->subMonth()->year);
            }
            if($search_fecha === 'this_year'){
                $query->whereYear('orders.created_at', now()->year);
            }
            if($search_fecha === 'last_year'){
                $query->whereYear('orders.created_at', now()->subYear()->year);
            }
        })->when($request->input('search_nombre'), function ($query, $search_nombre) {
            $query->where(function ($query) use ($search_nombre) {
                $query->where('products.descripcion', 'like', '%' . $search_nombre . '%');
            });
        })->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            $query->whereDate('orders.created_at', '>=', $search_fecha_inicio);
        })->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            $query->whereDate('orders.created_at', '<=', $search_fecha_fin);
        })
        ->first();

        $totalexpenses = DB::table('expenses')
        ->select(DB::raw("
            SUM(
                GREATEST(0, DATEDIFF(
                    IF('$end_date' < COALESCE(fin, '2099-01-01'), '$end_date', fin),
                    IF('$start_date' > dia, '$start_date', dia)
                )) * 
                CASE
                    WHEN tipo = 'mensual' THEN valor/30 * IF(moneda = 'USD', 7.8, 1)
                    WHEN tipo = 'anual' THEN valor/365 * IF(moneda = 'USD', 7.8, 1)
                    ELSE 1
                END
            ) as total
        "))
       //->whereIn('tipo', ['mensual', 'anual'])
       ->where('tipo_pago', 'recurrente')        
       ->first();


        //TOTALES Historicos
                $total_historico_expenses = DB::table('expenses')
                ->select(DB::raw("
                   SUM(
                        GREATEST(0, DATEDIFF(
                            IF(now() < COALESCE(fin, '2099-01-01'), now(), fin),
                            dia
                        )) * 
                        CASE
                            WHEN tipo = 'mensual' THEN valor/30 * IF(moneda = 'USD', 7.8, 1)
                            WHEN tipo = 'anual' THEN valor/365 * IF(moneda = 'USD', 7.8, 1)
                            ELSE 1
                        END
                    ) as total
                "))

                ->where('tipo_pago', 'recurrente')
                ->first();


        
                $total_historico_expenses2 = DB::table('expenses')
                ->select(DB::raw("
                    SUM(
                        valor * IF(moneda = 'USD', 7.8, 1)
                    ) as total
                "))
                ->where('tipo_pago', '!=' ,'recurrente')
                ->first();
        
        $total_historico_expenses3 = DB::table('ad_costs')
        ->select(DB::raw("
            SUM(
                costo
            ) as total
        "))
        ->first();

        $total_historico_revenue1 = DB::table('bank_accounts')
        ->select(
            DB::raw('sum(ingresos) as total'), 
            
        )
        ->first();

        $total_historico_taxes = DB::table('taxes')
        ->select(DB::raw("
            SUM(
                total
            ) as total
        "))
        ->first();

        logger('REPORTS');
        //logger($sql = $products->toSql());
        return view('reportprofit.index', compact('total_revenue','totalads','totals','total_historico_expenses','total_historico_expenses2','total_historico_expenses3','total_historico_taxes','totalexpenses','total_historico_revenue1','totalbonus','totalpayroll','totalenvios'));
        
    }

    

}
    
