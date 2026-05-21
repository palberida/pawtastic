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

class ReportAdsController extends Controller
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
            $searchFecha = "lifetime";
            $searchFecha = null;
        }elseif($request->input('search_fecha') == "lifetime"){
            $searchFecha = null;
        }


        //$dateFrom = '2026-01-01'; // <-- variable
        // or Carbon::parse($dateFrom)

        $adCostsSub = DB::table('ad_costs')
            ->select('id_ad', DB::raw('SUM(costo) AS ad_costo'))
            ->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
                // Search by estado
                $query->whereDate('dia', '>=', $search_fecha_inicio);
            })
            ->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
                // Search by estado
                $query->whereDate('dia', '<=', $search_fecha_fin);
            })
            ->when($searchFecha, function ($query, $search_fecha) {
                if($search_fecha === 'today'){
                    $query->whereDate('dia', now()->format('Y-m-d'));
                }
                if($search_fecha === 'yesterday'){
                    $query->whereDate('dia', now()->subDay()->format('Y-m-d'));
                }
                if($search_fecha === 'this_week'){
                    $query->whereBetween('dia', [now()->startOfWeek(), now()->endOfWeek()]);
                }
                if($search_fecha === 'last_week'){
                    $startOfLastWeek = now()->startOfWeek()->subWeek();
                    $endOfLastWeek = now()->endOfWeek()->subWeek();
                    $query->whereBetween('dia', [$startOfLastWeek, $endOfLastWeek]);
                }
                if($search_fecha === 'this_month'){
                    $query->whereMonth('dia', now()->month)
                    ->whereYear('dia', now()->year);
                }
                if($search_fecha === 'last_month'){
                    $query->whereMonth('dia', now()->copy()->startOfMonth()->subMonth()->month)
                    ->whereYear('dia', now()->copy()->startOfMonth()->subMonth()->year);
                }
                if($search_fecha === 'this_year'){
                    $query->whereYear('dia', now()->year);
                }
                if($search_fecha === 'last_year'){
                    $query->whereYear('dia', now()->subYear()->year);
                }
            })
            ->groupBy('id_ad');

        $productsSub = DB::table('ad_products as ap')
            ->join('products as pr', 'pr.id', '=', 'ap.id_producto')
            ->select(
                'ap.id_ad',
                DB::raw("GROUP_CONCAT(DISTINCT pr.descripcion ORDER BY pr.descripcion SEPARATOR ', ') AS product_descriptions")
            )
            ->groupBy('ap.id_ad');

        $salesSub = DB::table('ad_products as ap')
            ->select(
                'ap.id_ad',
                DB::raw('SUM(i.cantidad * i.precio) AS total_ventas'),
                DB::raw('SUM(i.cantidad * v.costo) AS total_costo'),
                DB::raw('SUM( ( i.cantidad * i.precio ) * 0.10) AS total_comision'),
                DB::raw('SUM(i.cantidad) AS total_cantidad')
            )
            ->join('variants as v', 'v.id_producto', '=', 'ap.id_producto')
            ->join('items as i', 'i.id_variante', '=', 'v.id')
            ->join('orders as o', 'o.id', '=', 'i.id_orden')
            ->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
                // Search by estado
                $query->whereDate('o.created_at', '>=', $search_fecha_inicio);
            })
            ->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
                // Search by estado
                $query->whereDate('o.created_at', '<=', $search_fecha_fin);
            })
            ->when($searchFecha, function ($query, $search_fecha) {
                if($search_fecha === 'today'){
                    $query->whereDate('o.created_at', now()->format('Y-m-d'));
                }
                if($search_fecha === 'yesterday'){
                    $query->whereDate('o.created_at', now()->subDay()->format('Y-m-d'));
                }
                if($search_fecha === 'this_week'){
                    $query->whereBetween('o.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                }
                if($search_fecha === 'last_week'){
                    $startOfLastWeek = now()->startOfWeek()->subWeek();
                    $endOfLastWeek = now()->endOfWeek()->subWeek();
                    $query->whereBetween('o.created_at', [$startOfLastWeek, $endOfLastWeek]);
                }
                if($search_fecha === 'this_month'){
                    $query->whereMonth('o.created_at', now()->month)
                    ->whereYear('o.created_at', now()->year);
                }
                if($search_fecha === 'last_month'){
                    $query->whereMonth('o.created_at', now()->copy()->startOfMonth()->subMonth()->month)
                    ->whereYear('o.created_at', now()->copy()->startOfMonth()->subMonth()->year);
                }
                if($search_fecha === 'this_year'){
                    $query->whereYear('o.created_at', now()->year);
                }
                if($search_fecha === 'last_year'){
                    $query->whereYear('o.created_at', now()->subYear()->year);
                }
            })
            ->groupBy('ap.id_ad');

        $ads = DB::table('ads as a')
            ->joinSub($adCostsSub, 'ac', function ($join) {
                $join->on('ac.id_ad', '=', 'a.id');
            })
            ->leftJoinSub($productsSub, 'p', function ($join) {
                $join->on('p.id_ad', '=', 'a.id');
            })
            ->leftJoinSub($salesSub, 's', function ($join) {
                $join->on('s.id_ad', '=', 'a.id');
            })
            ->select(
                'a.id',
                'ac.ad_costo',
                'p.product_descriptions',
                DB::raw('COALESCE(s.total_ventas, 0) AS total_ventas'),
                DB::raw('COALESCE(s.total_costo, 0) AS total_costo'),
                DB::raw('COALESCE(s.total_comision, 0) AS total_comision'),
                DB::raw('COALESCE(s.total_cantidad, 0) AS total_cantidad')
            )
            ->get();


        logger('REPORTS ADS');
        //logger($sql = $products->toSql());
        return view('reportads.index', compact('ads'));
        
    }

    

}
    
