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

class ReportProductController extends Controller
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

        $products1 = DB::table('orders')
        ->join('items', 'orders.id', '=', 'items.id_orden')
        ->join('variants', 'variants.id', '=', 'items.id_variante')
        ->join('products', 'products.id', '=', 'variants.id_producto')
        ->select('products.id','products.descripcion', DB::raw('sum(items.cantidad) as total') ,DB::raw('sum(items.precio) as total_dinero') , DB::raw('sum(variants.costo * items.cantidad) as total_costo'), DB::raw('sum( ((items.precio / items.cantidad ) - variants.costo) * items.cantidad) as total_ganancia') )
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
        ->groupBy('products.id','products.descripcion')->get();
        
        $products_list = [];
        foreach ($products1 as $p){
            
            $products_list[] = $p->id;
        }
        
        $products2 = DB::table('ads')
        ->join('ad_products', 'ad_products.id_ad', '=', 'ads.id')
        ->join('ad_costs', 'ad_costs.id_ad', '=', 'ads.id')
        ->join('products', 'ad_products.id_producto', '=', 'products.id')
        ->whereNotIn('products.id', $products_list)
        ->select('products.id','products.descripcion', DB::raw('0 as total,0 as total_dinero,0 as total_costo,0 as total_ganancia'))
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
        ->groupBy('products.id','products.descripcion')->get();
        
        //print_r($products1); die;
        //print_r($products2); die;
        $products = $products1->merge($products2)->sortByDesc('total');
        //$products = $products2;
        $totals = DB::table('orders')
        ->join('items', 'orders.id', '=', 'items.id_orden')
        ->join('variants', 'variants.id', '=', 'items.id_variante')
        ->join('products', 'products.id', '=', 'variants.id_producto')
        ->select(
            DB::raw('sum(cantidad) as total'), 
            DB::raw('sum(items.precio) as total_precio'), 
            DB::raw('sum(variants.costo * items.cantidad) as total_costo'),
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

        $total_ads = 0;
        foreach ($products as &$product){
            $costs = DB::table('ad_costs')
            ->join('ads', 'ad_costs.id_ad', '=', 'ads.id')
            ->join('ad_products', 'ads.id', '=', 'ad_products.id_ad')
            //->join(
            //    DB::raw('(SELECT id_ad, COUNT(DISTINCT id_producto) as product_count FROM ad_products GROUP BY id_ad) as product_counts'),
            //    'ads.id', '=', 'product_counts.id_ad'
            //)
            ->join(DB::raw('(SELECT id_ad, COUNT(*) as total FROM ad_products GROUP BY id_ad) as x'), function ($join) {
                $join->on('x.id_ad', '=', 'ads.id');
            })
            ->select(
                DB::raw('SUM(ad_costs.costo / x.total)  as total')
            )
            ->where('ad_products.id_producto', $product->id)
            ->when($searchFecha, function ($query, $searchFecha) {
                if ($searchFecha === 'today') {
                    $query->whereDate('ad_costs.dia', now()->format('Y-m-d'));
                }
                if ($searchFecha === 'yesterday') {
                    $query->whereDate('ad_costs.dia', now()->subDay()->format('Y-m-d'));
                }
                if ($searchFecha === 'this_week') {
                    $query->whereBetween('ad_costs.dia', [now()->startOfWeek(), now()->endOfWeek()]);
                }
                if ($searchFecha === 'last_week') {
                    $startOfLastWeek = now()->startOfWeek()->subWeek();
                    $endOfLastWeek = now()->endOfWeek()->subWeek();
                    $query->whereBetween('ad_costs.dia', [$startOfLastWeek, $endOfLastWeek]);
                }
                if ($searchFecha === 'this_month') {
                    $query->whereMonth('ad_costs.dia', now()->month)
                        ->whereYear('ad_costs.dia', now()->year);
                }
                if ($searchFecha === 'last_month') {
                    $query->whereMonth('ad_costs.dia', now()->copy()->startOfMonth()->subMonth()->month)
                        ->whereYear('ad_costs.dia', now()->copy()->startOfMonth()->subMonth()->year);
                }
                if ($searchFecha === 'this_year') {
                    $query->whereYear('ad_costs.dia', now()->year);
                }
                if ($searchFecha === 'last_year') {
                    $query->whereYear('ad_costs.dia', now()->subYear()->year);
                }
            })
            ->first();

            $product->ads = $costs->total;
            $total_ads += $costs->total;
        }
        $totals->total_ads = $total_ads;

        logger('REPORTS');
        //logger($sql = $products->toSql());
        return view('reportproduct.index', compact('products','totals'));
        
    }

    public function details($id,Request $request)
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
        $variants = DB::table('orders')
        ->join('items', 'orders.id', '=', 'items.id_orden')
        ->join('variants', 'variants.id', '=', 'items.id_variante')
        ->join('products', 'products.id', '=', 'variants.id_producto')
        ->select('variants.id','variants.descripcion', DB::raw('count(*) as total'))
        ->where('products.id',$id)
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
        ->groupBy('variants.id','variants.descripcion')
        ->orderBy('total', 'desc')
        ->get();

        return response()->json(['variants' => $variants]);
    }

    public function details2($id,Request $request)
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
        $variants = DB::table('orders')
        ->join('items', 'orders.id', '=', 'items.id_orden')
        ->join('variants', 'variants.id', '=', 'items.id_variante')
        ->join('products', 'products.id', '=', 'variants.id_producto')
        ->select('variants.id', DB::raw('SUBSTRING_INDEX(variants.descripcion, "/", -1) AS variant_name'), DB::raw('count(*) as total')) // Modified select clause
        ->where('products.id', $id)
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
        ->groupBy(DB::raw('SUBSTRING_INDEX(variants.descripcion, "/", -1)')) 
        ->orderBy('total', 'desc')
        ->get();

        return response()->json(['variants' => $variants]);
    }

}
    
