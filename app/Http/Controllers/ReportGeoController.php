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

class ReportGeoController extends Controller
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

        $searchFecha = 'last_month';
        $orders = DB::table('orders')
        ->select('orders.departamento_cliente', DB::raw('count(*) as total'),
        DB::raw('(count(*) / (SELECT COUNT(*) FROM orders)) * 100 as percentage'))
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
                $query->where('products.departamento_cliente', 'like', '%' . $search_nombre . '%');
            });
        })->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            $query->whereDate('orders.created_at', '>=', $search_fecha_inicio);
        })->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            $query->whereDate('orders.created_at', '<=', $search_fecha_fin);
        })
        ->groupBy('orders.departamento_cliente')
        ->orderBy('total', 'desc')
        ->paginate(30);

        logger('GEO');
        //logger($sql = $products->toSql());
        return view('reportgeo.index', compact('orders'));
        
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

}
    
