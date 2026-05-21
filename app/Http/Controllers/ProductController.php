<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Models\Item;
use App\Models\ProductTag;
use Illuminate\Http\Request;
use Exception;
use DateTime;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
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
        }elseif($request->input('search_fecha') == "lifetime"){
            $searchFecha = null;
        }
        $orders->when($request->input('search_nombre'), function ($query, $search_nombre) {
            $query->where(function ($query) use ($search_nombre) {
                // Search within the 'orders' table
                $query->where('nombre_cliente', 'like', '%' . $search_nombre . '%')
                      // Search within the related 'items' table
                      ->orWhereHas('items', function ($query) use ($search_nombre) {
                          $query->where('descripcion', 'like', '%' . $search_nombre . '%');
                      });
            });
        })->when($request->input('search_estado'), function ($query, $search_estado) {
            // Search by estado
            $query->where('estado', 'like', '%' . $search_estado . '%');
        })
        ->when($request->input('search_mensajero'), function ($query, $search_mensajero) {
            // Search by estado
            $query->where('mensajero', $search_mensajero);
        })
        ->when($request->input('search_vendedor'), function ($query, $search_vendedor) {
            // Search by estado
            $query->where('vendedor', $search_vendedor);
        })
        ->when($request->input('search_fecha_inicio'), function ($query, $search_fecha_inicio) {
            // Search by estado
            $query->whereDate('created_at', '>=', $search_fecha_inicio);
        })
        ->when($request->input('search_fecha_fin'), function ($query, $search_fecha_fin) {
            // Search by estado
            $query->whereDate('created_at', '<=', $search_fecha_fin);
        })
        ->when($searchFecha, function ($query, $search_fecha) {
            if($search_fecha === 'today'){
                $query->whereDate('created_at', now()->format('Y-m-d'));
            }
            if($search_fecha === 'yesterday'){
                $query->whereDate('created_at', now()->subDay()->format('Y-m-d'));
            }
            if($search_fecha === 'this_week'){
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if($search_fecha === 'last_week'){
                $startOfLastWeek = now()->startOfWeek()->subWeek();
                $endOfLastWeek = now()->endOfWeek()->subWeek();
                $query->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek]);
            }
            if($search_fecha === 'this_month'){
                $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            }
            if($search_fecha === 'last_month'){
                $query->whereMonth('created_at', now()->copy()->startOfMonth()->subMonth()->month)
                ->whereYear('created_at', now()->copy()->startOfMonth()->subMonth()->year);
            }
            if($search_fecha === 'this_year'){
                $query->whereYear('created_at', now()->year);
            }
            if($search_fecha === 'last_year'){
                $query->whereYear('created_at', now()->subYear()->year);
            }
        });
        $totalRows = $orders->count();
        
        $totalSum = $orders->get()->sum(function ($order) {
            return $order->total;
        });

        $totalNonCancelledRows = (clone $orders)->where('estado', '!=', 'cancelado')->count();

        $totalNonCancelledSum = (clone $orders)->where('estado', '!=', 'cancelado')->get()->sum(function ($order) {
            return $order->total;
        });
        logger($sql = $orders->toSql());
        $orders = $orders->orderBy('created_at', 'desc')
                ->with('items')
                ->paginate(20);
        return view('orders.index', compact('orders', 'totalRows', 'totalSum', 'totalNonCancelledRows', 'totalNonCancelledSum'));
        
    }

    public function show($id)
    {
        $order = Order::findOrFail($id); // Find the record by ID
        return view('orders.show', compact('order')); // Pass the record to the view
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id); // Find the record by ID
        $order->delete(); // Delete the record
        return redirect()->route('orders.index')->with('success', 'Record deleted successfully.');
    }

    public function create(Request $request){
        logger('TESTING PRODUCT CREATE');
        // Retrieve the HMAC header and request data
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        // Verify the webhook
        $verified = $this->verifyWebhook($data, $hmacHeader);
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // Decode JSON data
        $data = json_decode($data);
        //logger(print_r($data,true));

        //save in the DB
        $product = new Product();
        $product->descripcion = $data->title ?? '';
        $product->id_shopify = $data->id;
        //$product->variants()->delete();
        $product->save(); 
        foreach ($data->variants as $line) {
            $variant = new Variant();
            $variant->id_shopify = $line->id;
            $variant->id_producto = $product->id;
            $variant->descripcion = $line->title;
            $variant->codigo = $line->sku;
            $variant->precio = $line->price;
            //$variant->costo = 0;
            $variant->id_shopify_inventory = $line->inventory_item_id;
            $variant->save();

            ProductTag::where('id_variante', $variant->id)->delete();
            foreach ($line->metafields ?? [] as $mf) {
                if (($mf->namespace ?? '') !== 'custom' || ($mf->key ?? '') !== 'ossu_tags') {
                    continue;
                }
                $pairs = json_decode($mf->value ?? '', true);
                if (!is_array($pairs)) {
                    break;
                }
                foreach ($pairs as $tag => $valor) {
                    ProductTag::create([
                        'id_producto' => $product->id,
                        'id_variante' => $variant->id,
                        'tag'         => mb_substr((string) $tag, 0, 50),
                        'valor'       => $valor === null ? null : mb_substr((string) $valor, 0, 500),
                    ]);
                }
            }
        }
		return response('Success', 200); // Plain text response
    }

    public function update(Request $request){
        logger('TESTING PRODUCT UPDATE');
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $verified = $this->verifyWebhook($data, $hmacHeader);
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($data);
        //logger(print_r($data,true));
        $product = Product::firstOrCreate(
            ['id_shopify' => $data->id], // Search criteria
            ['id_shopify' => $data->id, 'descripcion' => $data->title ?? '']
        );
        $product->descripcion = $data->title ?? '';
        //$product->variants()->delete();
        $product->save(); 
        $updated_ids = array();

        foreach ($data->variants as $line) {
            $variant = Variant::where('id_shopify', $line->id)->first();
            if (!$variant) {
                $variant = new Variant();
            }
            $variant->id_shopify = $line->id;
            $variant->id_producto = $product->id;
            $variant->descripcion = $line->title == "Default Title" ? '' : $line->title;
            $variant->codigo = $line->sku;
            $variant->precio = $line->price;
            $variant->id_shopify_inventory = $line->inventory_item_id;
            $variant->stock = $line->inventory_quantity;
            //$variant->costo = 0;
            $variant->save();
            $updated_ids[] = $variant->id;

            ProductTag::where('id_variante', $variant->id)->delete();
            foreach ($line->metafields ?? [] as $mf) {
                if (($mf->namespace ?? '') !== 'custom' || ($mf->key ?? '') !== 'ossu_tags') {
                    continue;
                }
                $pairs = json_decode($mf->value ?? '', true);
                if (!is_array($pairs)) {
                    break;
                }
                foreach ($pairs as $tag => $valor) {
                    ProductTag::create([
                        'id_producto' => $product->id,
                        'id_variante' => $variant->id,
                        'tag'         => mb_substr((string) $tag, 0, 50),
                        'valor'       => $valor === null ? null : mb_substr((string) $valor, 0, 500),
                    ]);
                }
            }
        }

        $variantsToDelete = Variant::whereNotIn('id', $updated_ids)->get();

        if ($variantsToDelete->isNotEmpty()) {
            Variant::where('id_producto', $product->id)
                ->whereNotIn('id', $updated_ids)
                ->update(['enabled' => 0]);
        }
		return response('Success', 200);
    }

    public function item_update(Request $request){
        logger('TESTING ITEM UPDATE');
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $verified = $this->verifyWebhook($data, $hmacHeader);
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($data);
        //logger(print_r($data,true));
        
        $variant = Variant::where('id_shopify_inventory', $data->id)->firstOrFail();
        $variant->costo = $data->cost ?? 0;
        $variant->save(); 
        
		return response('Success', 200);
    }

    public function inventory_update(Request $request){
        logger('TESTING INVENTORY UPDATE');
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $verified = $this->verifyWebhook($data, $hmacHeader);
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($data);
        //logger(print_r($data,true));    
        
        $variant = Variant::where('id_shopify_inventory', $data->inventory_item_id)->firstOrFail();
        $variant->stock = $data->available ?? 0;
        $variant->save(); 
		return response('Success', 200);
    }

    public function delete(Request $request){
        logger('TESTING PRODUCT DELETE');
        // Retrieve the HMAC header and request data
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        // Verify the webhook
        $verified = $this->verifyWebhook($data, $hmacHeader);
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // Decode JSON data
        $data = json_decode($data);
        //logger(print_r($data,true));

        //save in the DB
        $product = Product::where('id_shopify', $data->id)->firstOrFail();
        $product->variants()->delete();
        $product->delete(); 
        
		return response('Success', 200); // Plain text response
    }

    


    private function verifyWebhook($data, $hmac_header)
	{
		foreach (['CLIENT_SECRET', 'SHOPIFY_INSTALL_CLIENT_SECRET'] as $key) {
			$secret = env($key, '');
			if ($secret === '') continue;
			$calculated = base64_encode(hash_hmac('sha256', $data, $secret, true));
			if (hash_equals($calculated, $hmac_header)) {
				return true;
			}
		}
		return false;
	}

	function customInArray($needle, $haystack) {
		foreach ($haystack as $item) {
			if ($this->customComparison($needle, $item)) {
				return true;
			}
		}
		return false;
	}

	function customComparison($str1, $str2) {
		return trim(strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $str1))) === trim(strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $str2)));
	}

    public function print(Request $request, $id)
    {
        return view('orders.print', compact('id'));
    }

    public function pdf(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $data = [
            'nombre' => $order->nombre_cliente,
            'telefonos' => $order->telefono1_cliente . ' ' . ($order->telefono2_cliente ?? ''),
            'direccion' => $order->direccion_cliente,
            'total' => $order->total,
            'items' => $order->items,
            'pagado' => $order->pagado,
            'notas' => $order->notas_envio
            
            
        ];

        // Load the view and pass data to it
        $pdf = PDF::loadView('orders.pdf', $data)->setPaper([0, 0, 283.5, 283.5]);

        // Download the PDF or open it in a new tab
        return $pdf->stream('sample.pdf');
    }

    public function finish(Request $request, $id)
    {
        
        return redirect()->route('orders.index', ['search' => $request->input('search')])
                     ->with('success', 'Order updated successfully');
    }   

    public function pay(Request $request){
        logger('TESTING PAY');
        
        // Retrieve the HMAC header and request data
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        // Verify the webhook
        $verified = $this->verifyWebhook($data, $hmacHeader);
        
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($data);
        //logger(print_r($data,true));
        $order = Order::where('idx_shopify', $data->id)->firstOrFail();
        $order->pagado = 1;
        $order->save(); 
		return response('Success', 200); 
    }

    public function refund(Request $request){
        logger('TESTING REFUND');
        
        // Retrieve the HMAC header and request data
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        // Verify the webhook
        $verified = $this->verifyWebhook($data, $hmacHeader);
        
        if (!$verified) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($data);
        //logger(print_r($data,true));
        $order = Order::where('idx_shopify', $data->order_id)->firstOrFail();
        $order->pagado = 1;
        $order->save(); 
		return response('Success', 200); 
    }
}
    
