<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Item;
use App\Models\Variant;
use App\Models\ProductCombo;
use App\Models\Product;
use App\Models\Ad;
use App\Models\AdProduct;
use App\Models\AdCost;
use App\Models\ShipmentCod;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Exception;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDF; 
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
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
                $query->where('nombre_cliente', 'like', '%' . $search_nombre . '%')
                       ->orWhere('id_shopify', $search_nombre )
                      ->orWhere('guia', 'like', '%' . $search_nombre . '%')
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
        ->when($request->input('search_pago'), function ($query, $search_pago) {
            // Search by estado
            $query->where('forma_pago', $search_pago);
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

        $totalNonCancelledNotPaidRows = (clone $orders)->where('estado', '!=', 'cancelado')->where('pagado', '=', 0)->count();

        $totalNonCancelledNotPaidSum = (clone $orders)->where('estado', '!=', 'cancelado')->where('pagado', '=', 0)->get()->sum(function ($order) {
            return $order->total;
        });
        //logger($sql = $orders->toSql());
        $orders = $orders->orderByRaw('CAST(id_shopify AS UNSIGNED) DESC')
                ->with('items');

        
        if ($request->input('search_output') === 'archivo') {
            $orders = $orders->get();

            $filename = 'orders_export_' . now()->format('Ymd_His') . '.csv';
            $headers = [
                "Content-type" => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            $columns = ['Estado','Vendedor', 'Mensajero', 'Pagado', 'Fecha', 'Orden', 'Nombre', 'Ciudad', 'Teléfono', 'Total','Items'];

            $callback = function () use ($orders, $columns) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, $columns, ';');

                foreach ($orders as $order) {
                    fputcsv($file, [
                        $order->estado,
                        $order->vendedor,
                        $order->mensajero,
                        $order->pagado ? 'Pagado' : '',
                        $order->created_at->format('d/m/y H:i'),
                        $order->id_shopify,
                        $order->nombre_cliente,
                        $order->municipio_cliente,
                        $order->telefono_cliente,
                        number_format($order->total, 2),
                        '',
                    ], ';');
                    foreach ($order->items as $item){
                        fputcsv($file, [
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $item->cantidad . ' x ' . $item->descripcion
                        ], ';');
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } else {
            // Default to HTML view with pagination
            $orders = $orders->paginate(20);

            return view('orders.index', compact(
                'orders',
                'totalRows',
                'totalSum',
                'totalNonCancelledRows',
                'totalNonCancelledSum',
                'totalNonCancelledNotPaidRows',
                'totalNonCancelledNotPaidSum'
            ));
        }

    }

    public function show($id)
    {
        $order = Order::findOrFail($id); // Find the record by ID
        return view('orders.show', compact('order')); // Pass the record to the view
    }

    public function edit(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $search = $request->input('search');

        return view('orders.edit', compact('order', 'search'));
    }

    public function add(Request $request)
    {
        $search = $request->input('search');
        $variants = Variant::with('product')
        ->join('products', 'variants.id_producto', '=', 'products.id')
        ->orderBy('products.descripcion')
        ->orderBy('variants.descripcion')
        ->select('variants.*') 
        ->get();
        return view('orders.add', compact( 'search', 'variants'));
    }

    public function partialEdit(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $search = $request->input('search');

        return view('orders.partial_edit', compact('order', 'search'));
    }

    public function update(Request $request, $id)
    {
        try{
            $order = Order::findOrFail($id); 
            $order->update($request->all()); 
            if ($request->input('mensajero') != 'caex' && $request->input('mensajero') != 'forza'){
                return redirect()->route('orders.print', ['id' => $id])
                        ->with('success', 'Order updated successfully');
                
            }
        } catch (Exception $err) {
            logger('--------------------------------------------------ORDER UPDATE API ERROR: ' . print_r($err, true));
            return redirect()->back()
                ->withInput()
                ->withErrors(['update_error' => 'An error occurred: ' . $err->getMessage()]);
            }
        return redirect()->route('orders.index', ['search' => $request->input('search')])
                        ->with('success', 'Order updated successfully');              
    }

    public function save(Request $request)
    {

        try{
            //check stock firsts
            foreach ($request->input('variants', []) as $variantId => $data) {
                if (!isset($data['selected'])) continue;

                $variant = Variant::with('product')->find($variantId);
                if (!$variant) continue;
                if($variant->stock < ($data['quantity'] ?? 1 )){
                    logger('--------------------------------------------------STOCK FAIL: ');
                    return redirect()->back()
                    ->withInput()
                    ->withErrors(['update_error' => 'No hay inventario del producto ' . $variant->descripcion]);
                }
            }

            } catch (Exception $err) {
            logger('--------------------------------------------------ORDER CREATE API ERROR: ' . print_r($err, true));
            return redirect()->back()
                ->withInput()
                ->withErrors(['update_error' => 'An error occurred: ' . $err->getMessage()]);
            }
        DB::beginTransaction();
        try{


            
            
            $order = Order::create($request->all());
            $nextShopifyId = Order::max('id_shopify') + 1;
            $order->update(['id_shopify' => $nextShopifyId]);
            $total = 25;
            foreach ($request->input('variants', []) as $variantId => $data) {
                if (!isset($data['selected'])) continue;

                $variant = Variant::with('product')->find($variantId);
                if (!$variant) continue;
                $item = new Item();
                $item->descripcion = $variant->product->descripcion . ' ' . $variant->descripcion;
                $item->cantidad = $data['quantity'] ?? 1;
                $item->precio = $variant->precio * $item->cantidad;
                $item->id_orden = $order->id;
                $item->id_variante = $variant->id;
                $item->save();
                $total += $item->precio;
                Variant::where('id', $variant->id)->decrement('stock', $item->cantidad);
            }
            $order->update(['total' => $total]);

            

            DB::commit();
        } catch (Exception $err) {
            logger('--------------------------------------------------ORDER CREATE API ERROR: ' . print_r($err, true));
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['update_error' => 'An error occurred: ' . $err->getMessage()]);
            }
        return redirect()->route('orders.index', ['search' => $request->input('search')])
                        ->with('success', 'Order saved successfully');       
        
    }


    public function delete(Request $request){
        logger('TESTING DELETE');
        
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
        $order->items()->delete();
        $order->delete(); // Delete the record
		return response('Success', 200); 
    }

    /*public function create()
    {
        return view('orders.create'); // Return the view for creating a record
    }*/

    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Create a new record with the validated data
        Order::create($validated);

        // Redirect to the records index with a success message
        return redirect()->route('orders.index')->with('success', 'Record created successfully.');
    }

    public function create(Request $request){
        logger('TESTING CREAATION');
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

        $seller_codes = [];
        foreach(getUsersWithRole(3) as $seller){
            $seller_codes[] = $seller->seller_code;
        }

        

        //save in the DB
        DB::beginTransaction();
        try {
            $order = new Order();
            $order->id_shopify = $data->order_number ?? '';
            $order->idx_shopify = $data->id ?? '';
            $order->nombre_cliente = $data->shipping_address->name ?? '';
            $order->direccion_cliente = $data->shipping_address->address1;
            $order->departamento_cliente = $data->shipping_address->province;
            $order->municipio_cliente = $data->shipping_address->city;
            $order->telefono1_cliente = $data->shipping_address->phone ?? '';
            $order->telefono2_cliente = '';
            $order->email_cliente =  $data->email ?? '';
            //$order->mensajero = isset($data->billing_address->zip) && in_array($data->billing_address->zip, $seller_codes) ? $data->billing_address->zip : isset($data->shipping_address->zip) && in_array($data->shipping_address->zip, $seller_codes) ? $data->shipping_address->zip : 'DIRECTO' ;
            $order->nit_cliente =  $data->billing_address->company ? $data->billing_address->company  : $data->shipping_address->company;

            $order->nombre_factura = $data->billing_address->name ?? '';
            $order->direccion_factura = ($data->billing_address->address1 && $data->billing_address->city && $data->billing_address->province) ? $data->billing_address->address1 . '. ' . $data->billing_address->city . ', ' . $data->billing_address->province : '';
            
            $order->vendedor = isset($data->shipping_address->zip) && in_array($data->shipping_address->zip, $seller_codes) ? $data->shipping_address->zip : 'DIRECTO';
            $order->total = $data->total_price ?? 0;
            $order->descuento = $data->total_discounts ?? 0;
            $order->forma_pago = $data->payment_gateway_names[0];
            $order->guia ='';
            $order->costo_envio_aproximado = 0;
            $order->notas = $data->note ?? '';
            // Set other fields as needed
            $order->save(); // Save the model to the database
            foreach ($data->line_items as $line) {
                $item = new Item();
                $item->descripcion = $line->name;
                $item->cantidad = $line->quantity;
                $item->precio = $line->price * $line->quantity;
                $item->id_orden = $order->id;
                $variant = Variant::where('id_shopify', $line->variant_id)->first(); 

                $item->id_variante = $variant->id;
                $item->save();
            }		
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            logger('--------------------------------------------------ORDER CREATION ERROR: ' . print_r($e, true));
            return response('Bad Request', 400); // Plain text response
        }
        /*try {
            $client = new Google_Client();
            $client->setApplicationName('Google Sheets API');
            $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');
            // Path to credentials
            $path = storage_path('app/keys/credentials.json'); // Adjust path to your credentials file
            $client->setAuthConfig($path);
            // Configure the Sheets Service
            $service = new Google_Service_Sheets($client);
            $spreadsheetId = '1Zr0yFLS58b54IEJjRHuHkgn7ut1hEJ_moePLEGNhXbk';
            $dateTime = new DateTime($data->created_at);
            
            // Prepare rows data
            $rows = [];
            $newRow = [
                '-',
                isset($data->shipping_address->zip) && in_array($data->shipping_address->zip, $seller_codes) ? $data->shipping_address->zip : '',
                $data->order_number ?? '',
                $dateTime->format('d/m/y H:i'),
                $data->email ?? '',
                $data->total_price ?? '',
                $data->billing_address->name ?? '',
                $data->billing_address->company ?? '',
                ($data->billing_address->address1 && $data->billing_address->city && $data->billing_address->province) ? $data->billing_address->address1 . '. ' . $data->billing_address->city . ', ' . $data->billing_address->province : '',
                $data->shipping_address->name ?? '',
                ($data->billing_address->address1 && $data->billing_address->city && $data->billing_address->province) ? $data->billing_address->address1 . '. ' . $data->billing_address->city . ', ' . $data->billing_address->province : '',
                '',
                '',
                '',
                '',
                '',
                '',
                'INICIADA'
            ];
            $rows[] = $newRow;
            $x = 0;
            foreach ($data->line_items as $line) {
                $x++;
                $newRow = [
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    $line->name,
                    $line->quantity,
                    $line->price,
                    $line->price * $line->quantity
                ];
                if ($x == count($data->line_items)) {
                    $newRow[] = $data->total_outstanding;
                } else {
                    $newRow[] = '-';
                    $newRow[] = '-';
                }
                $newRow[] = '-';
                $rows[] = $newRow;
            }
            $valueRange = new Google_Service_Sheets_ValueRange();
            $valueRange->setValues($rows);
            $options = ['valueInputOption' => 'USER_ENTERED'];
            
                $service->spreadsheets_values->append($spreadsheetId, 'Ordenes Todas', $valueRange, $options);
        } catch (Exception $err) {
            logger('--------------------------------------------------ORDER CREATION API ERROR: ' . print_r($err, true));
        }*/
		return response('Success', 200); // Plain text response
    }

    public function cancel(Request $request){
        logger('TESTING CANCEL');
        
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
        $order->estado = 'cancelado';
        $order->save(); // Save the model to the database
		return response('Success', 200); 
    }


    private function verifyWebhook($data, $hmac_header)
	{
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data,  env('CLIENT_SECRET', ''), true));
		return hash_equals($calculated_hmac, $hmac_header);
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
        $order->forma_pago = end($data->payment_gateway_names);
       
        $order->save(); 
		return response('Success', 200); 
    }

    public function refund(Request $request){
        logger('TESTING REFUND');
        /*
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
		*/
        return response('Success', 200); 
    }

    public function invoices(Request $request, $state)
    {
        $searchFecha = $request->input('search_fecha');
        $searchFechaIncio = $request->input('search_fecha_inicio');
        $searchFechaFin = $request->input('search_fecha_fin');
        $orders = Order::query();
        if($state == "done")
            $orders->where('facturado', 1);
        else
            $orders->where('facturado', 0);
        $orders->where(function($query) {
        $query->where('estado', '!=', 'cancelado')
              ->where('pagado', 1);
        });

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
        
        $orders = $orders->orderBy('id_shopify', 'desc')
                ->with('items');
        
        if ($request->input('search_output') === 'archivo') {
            $orders = $orders->get();
            
            $fileName = 'data_export_' . date('Y-m-d_H-i-s') . '.csv';
            $handle = fopen('php://output', 'w');
            
            header('Content-Type: text/csv charset=UTF-8"');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Pragma: no-cache');
            header('Cache-Control: "must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Orden', 'Fecha', 'Nombre', 'Direccion', 'Nit', 'Item', 'Cantidad', 'Total Item', 'Descuento' , 'Total', 'Guía', 'Transferencia']); 
            
            foreach ($orders as $row) {
                
                fputcsv($handle, [
                    $row->id_shopify, 
                    $row->created_at->format('d/m/y'),
                    $row->nombre_cliente,
                    $row->direccion_factura,
                    $row->nit_cliente ? $row->nit_cliente : 'CF',
                    '',
                    '',
                    '',
                    number_format($row->descuento, 2),
                    number_format($row->total, 2),
                    $row->guia,
                    $row->transfers()->first() ?  $row->transfers()->first()->transfer->codigo : ''
                ], ';');
                
                foreach ($row->items as $item) {
                    fputcsv($handle, [
                        '',
                        '',
                        '',
                        '',
                        '',
                        $item->descripcion,
                        $item->cantidad,
                        $item->precio / $item->cantidad,
                        '',
                        ''
                    ], ';');
                }
            }

            fclose($handle);
            exit;
        }else{
            $orders = $orders->paginate(200);
            return view('orders.invoices', compact('orders','state'));
        }
    }

    

    public function invoices_done(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->facturado = 1;
        $order->autorizacion = $request->input('autorizacion');
        $order->save();
        return redirect()->back()->withInput();
        //return view('orders.partial_edit', compact('order'));
    }

    public function import_ads(){
        return response('Success', 200); // Plain text response
        logger('IMPORT ADS');
        DB::beginTransaction();

        try {
            $filePath = '/home/pablo.merida/ads.csv';
            // Open the CSV file for reading
            if (($handle = fopen($filePath, 'r')) !== false) {
                // Loop through each line in the CSV file
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    // Assuming first column is ad_id and the second is product names
                    $adId = $data[0];
                    $productNames = $data[1];
                    
                    // Create an Ad entry if it doesn't already exist
                    $ad = Ad::firstOrCreate(['fb_id' => $adId]);
                    
                    // Split product names by "/" and insert them into ad_products
                    $products = explode('/', $productNames);
                    foreach ($products as $productName) {
                        $product = Product::where('descripcion', $productName)->firstOrFail();
                        AdProduct::create([
                            'id_ad' => $ad->id,
                            'id_producto' => $product->id
                        ]);
                    }
                }
                fclose($handle);
            }
    
            // Commit the transaction
            DB::commit();
            echo "Data imported successfully!";
        } catch (Exception $e) {
            // Rollback if there's an error
            DB::rollBack();
            echo "Failed to import data: " . $e->getMessage();
        }
    }

    function import_ad_costs()
    {
        return response('Success', 200); // Plain text response
        logger('IMPORT AD COST');
        $filePath = '/home/pablo.merida/adcosts.csv';
        // Start a transaction
        DB::beginTransaction();

        try {
            // Open the CSV file for reading
            if (($handle = fopen($filePath, 'r')) !== false) {
                // Loop through each line in the CSV file
                $x = 0;
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $x++;
                    if($x == 1)
                        continue;
                    // Assuming columns in the CSV are: ad_id, day, and amount_spent
                    $adId = $data[0];
                    $day = $data[1];
                    $amountSpent = $data[3];
                    $ad = Ad::where('fb_id', $adId)->first();
                    if(!$ad){
                        logger('AD NOT FOUND ' . $adId);
                        continue;
                    }
                    
                    AdCost::updateOrCreate(
                        [
                            'id_ad' => $ad->id,
                            'dia' => $day
                        ],
                        [
                            'costo' => $amountSpent * 7.8 
                        ]
                    );
                    logger('AD UPDATED ' . $adId);
                }
                fclose($handle);
            }

            // Commit the transaction
            DB::commit();
            echo "Data imported successfully!";
        } catch (Exception $e) {
            // Rollback if there's an error
            DB::rollBack();
            echo "Failed to import data: " . $e->getMessage();
        }
    }

    /*public function import(Request $request){
        return response('Success', 200); // Plain text response
        logger('IMPORT');
        // Configure Google Client
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        // Path to credentials
        $path = storage_path('app/keys/credentials.json'); // Adjust path to your credentials file
        $client->setAuthConfig($path);
        // Configure the Sheets Service
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = '1Zr0yFLS58b54IEJjRHuHkgn7ut1hEJ_moePLEGNhXbk';
        $seller_codes = [];
        foreach(getUsersWithRole(3) as $seller){
            $seller_codes[] = $seller->seller_code;
        }
        $range = 'import!A4821:R4946'; 
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        $currentOrder = null;
        $createdAt = null;
        $exists = 0;
        foreach ($values as $row) {
            
            if(count($row)<5){
                continue;
            }

            if(isset($row[19]) && trim($row[19]) == "CANCELADO"){
                continue;
            }
            
            if(isset($row[2]) && is_numeric(trim($row[2]))){
                logger('ORDER ' . $row[2]);
                $exists = Order::where('id_shopify', $row[2])->exists();
                
                if($exists){
                    logger('ORDER EXISTS');
                    continue;
                }
                $exists = 0;
                
                //save in the DB
                $order = new Order();
                $order->id_shopify = $row[2];
                $order->nombre_cliente = $row[9];
                $parts = explode('.', $row[10]);
                if (isset($parts[1])) {
                    $location = trim($parts[1]);
                    $locationParts = explode(',', $location);            
                    if (isset($locationParts[0]) && isset($locationParts[1])) {
                        $ciudad = trim($locationParts[0]);
                        $departamento = trim($locationParts[1]);

                    }
                    $order->direccion_cliente = $parts[0];
                    $order->departamento_cliente =  $departamento;
                    $order->municipio_cliente = $ciudad ;
                } 
                $order->email_cliente = $row[4];
                $order->nombre_factura = $row[6];
                $order->direccion_factura = $row[8];
                $order->vendedor = in_array($row[1], $seller_codes) ? $row[1] : '';
                //if(isset($row[16]) && !empty($row[16]))
                //$order->guia = $row[16];
                $order->costo_envio_aproximado = 0;
                $order->total = $data->total_price ?? 0;
                $createdAt = \Carbon\Carbon::createFromFormat('d/m/y H:i', $row[3]);
                $order->created_at = $createdAt;
                $order->updated_at = $createdAt;
                $order->save(); 
                $currentOrder = $order->id;
                //$currentOrder = $row[2];
            }else{
                logger('ITEM' . $row[2]);
                if(!$exists){

                    $item = new Item();
                    $item->descripcion = $row[11];
                    $item->cantidad = $row[12];
                    $item->precio = floatval($row[14]);
                    $item->id_orden = $currentOrder;
                    logger('ITEM' . floatval($row[14]));
                    
                    $pparts = explode('-', $row[11]);
                    $producto = trim($pparts[0]);
                    $dbproduct = Product::where('descripcion', $producto)->first(); 
                    if(!$dbproduct)
                        logger('PRODUCTO NO ENCONTRADO ' . $producto);
                    
                        
                    $variante = "";
                    if (isset($pparts[1])) {
                        $variante = trim($pparts[1]);
                    }
                    if ($dbproduct ) {
                        $dbvariant = Variant::where('descripcion', $variante)->where('id_producto', $dbproduct->id)->first(); 
                        if(!$dbvariant){
                            $dbvariant = Variant::where('id_producto', $dbproduct->id)->first(); 
                        }
                        if(!$dbvariant){
                            logger('VARIANTE NO ENCONTRADO ' );
                        }   
                        else{
                            
                            $item->id_variante = $dbvariant->id; 
                            $item->created_at = $createdAt;
                            $item->updated_at = $createdAt;
                            $item->save();
                        }
                    }
                       
                    
                    
                }
            }
        }
		return response('Success', 200); // Plain text response
    }*/

    public function fulfill(Request $request){
        logger('TESTING FULFILL');
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $verified = $this->verifyWebhook($data, $hmacHeader);
        if (!$verified) {
            logger('TESTING FULFILL UNATHORIZED');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($data);
        $order = Order::where('idx_shopify', $data->id)->firstOrFail();
        $order->estado = 'completado';
        $order->save(); 
		return response('Success', 200); 
    }

    public function shipments(Request $request)
    {
        $searchFecha = $request->input('search_fecha');
        $searchFechaIncio = $request->input('search_fecha_inicio');
        $searchFechaFin = $request->input('search_fecha_fin');
        $orders = Order::query();
        $orders->where(function ($query) {
           $query->where('estado', 'enviado');
            $query->where('pagado',0);
        });
        

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
        //logger($sql = $orders->toSql());
        $orders = $orders->orderBy('id_shopify', 'desc')
                ->with('items')
                ->paginate(20);
        return view('orders.shipments', compact('orders'));
    }

    public function shipments_done(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->estado = 'completado';
        $order->save();
        return redirect()->back()->withInput();
        //return view('orders.partial_edit', compact('order'));
    }

    
    public function shipments_load()
    {
        return view('orders.load');
    }

    function shipments_upload(Request $request)
    {
        
        logger('IMPORT COD');
        
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        DB::beginTransaction();

        try {

            $n = 0;
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                
                $expectedHeaders = ['Número Guía', 'Fecha Entrega', 'Fecha Liquidación', 'Número Operación', 'Monto COD', '% Comision', 'Valor Comisión', 'Monto Liquidado', 'Cuenta Bancaria', 'Destinatario', 'Origen', 'Destino', 'Referencia 1', 'Referencia 2', 'ID Recibo de caja'];
                $start_importing = 0;
                while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                    $cleanRow = array_filter($data, fn($value) => !is_null($value) && trim($value) !== '');
                    
                    if (count($cleanRow) === count($expectedHeaders)) {

                        $start_importing = true;
                        continue;
                    }
                    if($start_importing && isset($data[7]) && trim($data[7]) != '' ){
                        ShipmentCod::updateOrCreate(
                            ['guia' => trim($data[0])], // Condition to find existing record
                            [
                                'fecha_transaccion' => \Carbon\Carbon::createFromFormat('d/m/Y', trim($data[2])),
                                'numero_operacion' => trim($data[3]),
                                'total' => trim($data[7])
                            ]
                        );
                        $n++;
                        //logger('COD 5');
                        //logger('COD transaction added ');
                    }
                }
                fclose($handle);
            }

            //calculate invoices
            $this->calculate_invoices();

            // Commit the transaction
            DB::commit();
            
            return redirect()->route('shipments.load')->with('success', 'Envios COD actualizados exitosamente! Actualizados: ' . $n);
        } catch (Exception $e) {
            // Rollback if there's an error
            DB::rollBack();
            logger("Failed to import COD data: " . $e->getMessage() . $n);
        }
        
        return redirect()->route('shipments.load')->with('error', 'Ocurrió un error al actualizar los envíos COD.');

    }

    public function calculate_invoices()
    {
       
        //update CAEX COD's
       $subQuery = DB::table('shipments_cod as b')
            ->select('b.fecha_transaccion', 'b.numero_operacion', DB::raw('ROUND(SUM(total), 2) as total'))
            ->groupBy('b.numero_operacion');

        $ordersToUpdate = DB::table('bank_statements as a')
            ->joinSub($subQuery, 'x', function ($join) {
                $join->on(DB::raw('DATE(a.fecha_transaccion)'), '=', DB::raw('DATE(x.fecha_transaccion)'))
                    ->whereRaw('ABS(a.total - x.total) < 0.1');
            })
            ->join('shipments_cod as c', 'x.numero_operacion', '=', 'c.numero_operacion')
            ->join('orders as d', 'd.guia', '=', 'c.guia')
            ->where('a.tipo_transaccion', 'NC')
            ->whereNotIn('d.estado', ['completado', 'cancelado'])
            ->get(['d.id as order_id', 'a.id as bank_statement_id']);

        if ($ordersToUpdate->isNotEmpty()) {
            foreach ($ordersToUpdate as $row) {
                Order::where('id', $row->order_id)
                    ->update([
                        'estado' => 'completado',
                        'pagado' => 1,
                        'bank_statement_id' => $row->bank_statement_id,
                        'updated_at' => now(),
                    ]);
            }
        }

        //UPDATE MESSENGER DELIVERIES
        DB::update("
                UPDATE orders d
                JOIN transfer_orders c ON d.id = c.id_orden
                JOIN transfers b ON b.id = c.id_transferencia
                JOIN bank_statements a ON DATE(b.created_at) = DATE(a.fecha_transaccion) AND a.total = b.total
                SET d.bank_statement_id = a.id
                WHERE d.bank_statement_id IS NULL
        ");


    }

    private function _get_discount(&$discount,$price){
        $discount_to_apply = 0;
        if($price >= $discount){
            $discount_to_apply = $discount;
            $discount = 0;
        }else{
            $discount_to_apply = $price;
            $discount = $discount - $discount_to_apply;
        }
        if($discount_to_apply > 0){
            return   [
                "Discount" => [
                    [
                        "Amount" => $discount_to_apply
                    ]
                ]
            ];
        }
        return null;
    }

    public function invoices_generate_batch(Request $request)
    {
        $ids = $request->order_ids ?? [];

        if (empty($ids)) {
            return response()->json(['error' => 'No orders'], 422);
        }
        $res = $this->_invoices_generate($ids);
        if(!$res)
            return response()->json(['error' => 'Error while creating'], 400);
        return response()->json(['message' => 'Ordenes Facturadas'], 200);
    }

    
    function invoices_generate(Request $request, $id){
       $res = $this->_invoices_generate([$id]);
        if(!$res)
            return response()->json(['error' => 'Error while creating'], 400);
        return response()->json(['message' => 'Ordenes Facturadas'], 200);

    }

    function _invoices_generate($ids,$cf = 0){
        try{
            logger('START INVOICE GENERATE');
        $provider = 'digifact';
        $token = DB::table('tokens')
            ->where('provider', $provider)
            ->where('expires', '>', now())
            ->first();

        if (!$token) {
            $token_request = ["Username"=>env('DIGIFACT_USER', ''),"Password"=>env('DIGIFACT_PASS', '')];
            $token_json = json_encode($token_request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $token_url = "https://felgtaws.digifact.com.gt/gt.com.apinuc/api/login/get_token";
            $response_token = Http::withBody($token_json, 'application/json')->post($token_url);
            if ($response_token->successful()) {
                $response_token_body = json_decode($response_token->body(),true);
                if(isset($response_token['Token']) ){
                   $expires_in = \Carbon\Carbon::createFromFormat('n/j/Y g:i:s A', $response_token_body['expira_en'])->format('Y-m-d H:i:s');
                    $token = $response_token_body['Token'];

                    DB::table('tokens')->updateOrInsert(
                        ['provider' => 'digifact'], // condition to check if it exists
                        [
                            'expires' => $expires_in,
                            'token'   => $token,
                        ]
                    );
                }else{
                    echo $response['message'];
                    logger('DIGIFACT ERROR: '  . $response_token['message']);
                    return;
                }
                
            } else {
                echo $response_token->body();
                logger( 'ERROR DIGIFACT HTTP' .  $response_token->status() .  $response_token->body() );
                return;
            }
        }else{
            $token = $token->token;
        }


        $shipping_fixed = 25;
    
        //vars to save the final shipping item values
        $TaxableAmount = 0;
        $Amount = 0;
        $TotalItem = 0;
        $DiscountToApply= 0;
        $TotalShip = 0;



        $items = [];
        $nitems = 0;
        //iterate over all orders///////////
        foreach($ids as $id){
            $order = Order::findOrFail($id);
            if($cf){
                $nombre_cliente = '';
                $nit_cliente =  "CF";
            }else{
                $nombre_cliente = $this->getClientName($order->nit_cliente,$token);
                $nit_cliente =  $this->_formatNIT($order->nit_cliente);
            }
            
            
            $discount = $order->descuento;
            
            $discount_applied = $discount;
            $discount_object = $this->_get_discount($discount,25);
            $discount_applied = $discount_applied - $discount;
            $total_iva = 0;
            
            //calculate shipping values
            $DiscountToApply += $discount_applied;
            $total_iva += ( ( $shipping_fixed ) - $discount_applied ) * 0.12;
            $TaxableAmount +=  ( ( $shipping_fixed ) - $discount_applied ) / 1.12;
            $Amount +=  ( ( $shipping_fixed ) - $discount_applied ) * 0.12;
            $TotalItem += $shipping_fixed - $discount_applied;
            $TotalShip += $shipping_fixed;
                    
            foreach ($order->items as $item){
                $nitems++;
                $discount_applied = $discount;
                $discount_object = $this->_get_discount($discount,$item->precio * $item->cantidad);
                $discount_applied = $discount_applied - $discount;

                $total_iva += ( ( $item->precio ) - $discount_applied ) * 0.12;
                $items[] = [
                    "Number" => $nitems,
                    "Codes" => null,
                    "Type" => "Bien",
                    "Description" => $item->descripcion,
                    "Qty" => $item->cantidad,
                    "UnitOfMeasure" => "SER",
                    "Price" => $item->precio / $item->cantidad,
                    "Discounts" => $discount_object,
                    "Taxes" => [
                        "Tax" => [
                            [
                                "Code" => "1",
                                "Description" => "IVA",
                                "TaxableAmount" =>  ( ( $item->cantidad * $item->precio ) - $discount_applied ) / 1.12,
                                "Amount" => ( ( $item->cantidad * $item->precio ) - $discount_applied ) * 0.12
                            ]
                        ]
                    ],
                    "Totals" => [
                        "TotalItem" => ( $item->cantidad * $item->precio ) - $discount_applied
                    ]
                ];
            }
        }
        
        //add shippinng item//////
        $items[] = [
            "Number" => $nitems + 1,
            "Codes" => null,
            "Type" => "Servicio",
            "Description" => "Envío",
            "Qty" => "1.000000",
            "UnitOfMeasure" => "SER",
            "Price" => $TotalShip,
            "Discounts" => [
                "Discount" => [
                    [
                        "Amount" => $DiscountToApply
                    ]
                ]
            ],
            "Taxes" => [
                "Tax" => [
                    [
                        "Code" => "1",
                        "Description" => "IVA",
                        "TaxableAmount" =>  $TaxableAmount,
                        "Amount" => $Amount
                    ]
                ]
            ],
            "Totals" => [
                "TotalItem" => $TotalItem
            ]
        ];

        $record = DB::table('auto_invoice_date')->first();

        if (!$record || !$record->fecha) {
            $issuedDateTime = now()->format('Y-m-d\TH:i:sP');
        } else {
            $issuedDateTime = \Carbon\Carbon::parse($record->fecha)->format('Y-m-d\TH:i:sP');
        }
        
       $data = [
            "Version" => "1.00",
            "CountryCode" => "GT",
            "Header" => [
                "DocType" => "FACT",
                //"IssuedDateTime" => now()->format('Y-m-d\TH:i:sP'),
                "IssuedDateTime" => $issuedDateTime,
                "Currency" => "GTQ"
            ],
            "Seller" => [
                "TaxID" => "119214814",
                "USERNAME" => "Ossu",
                "TaxIDAdditionalInfo" => [
                    [
                        "Name" => "AfiliacionIVA",
                        "Data" => null,
                        "Value" => "GEN"
                    ]
                ],
                "Name" => "Ossu",
                "Contact" => [
                    "EmailList" => [
                        "Email" => [
                            "admin@ossu.gt"
                        ]
                    ]
                ],
                "AdditionlInfo" => [
                    [
                        "Name" => "TipoFrase",
                        "Data" => "1",
                        "Value" => "1"
                    ],
                    [
                        "Name" => "Escenario",
                        "Data" => "1",
                        "Value" => "2"
                    ]
                ],
                "BranchInfo" => [
                    "Code" => "1",
                    "Name" => "Ossu S.A.",
                    "AddressInfo" => [
                        "Address" => "Ciudad",
                        "City" => "01006",
                        "District" => "Guatemala",
                        "State" => "Guatemala",
                        "Country" => "GT"
                    ]
                ]
            ],
            "Buyer" => [
                "TaxID" => $nit_cliente,
                "Name" => $nombre_cliente,
                "AddressInfo" => [
                    "Address" => "CIUDAD",
                    "City" => "01001",
                    "District" => "GUATEMALA",
                    "State" => "GUATEMALA",
                    "Country" => "GT"
                ]
            ],
            "ThirdParties" => null,
            "Items" => $items,
            "Totals" => [
                "TotalTaxes" => [
                    "TotalTax" => [
                        [
                            "Description" => "IVA",
                            "Amount" => $total_iva
                        ]
                    ]
                ],
                "GrandTotal" => [
                    "InvoiceTotal" => $order->total
                ]
            ],
            "AdditionalDocumentInfo" => [
                "AdditionalInfo" => [
                    
                ]
            ]
        ];

        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        //$token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJVc2VyIjoiR1QuMDAwMTE5MjE0ODE0LlRFU1RVU0VSIiwiQ291bnRyeSI6IkdUIiwiRW52IjoiMiIsIm5iZiI6MTc1NDYwNDY5OCwiZXhwIjoxNzU3MTk2Njk4LCJpYXQiOjE3NTQ2MDQ2OTgsImlzcyI6Imh0dHBzOi8vd3d3LmRpZ2lmYWN0LmNvbS5ndCIsImF1ZCI6Imh0dHBzOi8vYXBpbnVjLmRpZ2lmYWN0LmNvbS5kby9kby5jb20uYXBpbnVjIn0.EJ44S0RG2wXtqcz6tgJnEU2VN63FWU1LSoSRJdimyTQ";

        $url = "https://felgtaws.digifact.com.gt/gt.com.apinuc/api/v2/transform/nuc_json";
        $params = [
            'TAXID' => env('DIGIFACT_NIT1', ''),
            'USERNAME' => env('DIGIFACT_NIT2', ''),
            'FORMAT' => 'PDF'
        ];
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($url . '?' . http_build_query($params));


            if ($response->successful()) {
                if(isset($response['code']) && $response['code'] == "1" ){
                    foreach($ids as $id){
                        $order = Order::findOrFail($id);
                        $order->facturado = 1;
                        $order->autorizacion = $response['authNumber'] ;
                        $order->save();
                    }
                    logger('FACTURADO CORRECTAMENTE');
                    $response = json_decode($response->body(), true);
                    $data_pdf = base64_decode($response['responseData3']);
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="output.pdf"');
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Expires: 0');
                    echo $data_pdf;
                    exit;
                }else{

            logger('START INVOICE GENERATE 10' );
                    return response()->json([
                        'error' => 'Bad Request',
                        'message' => 'DIGIFACT ERROR: '  . $response['message']
                    ], 400);
                    logger('DIGIFACT ERROR: '  . $response['message']);

                }
            } else {
                
            logger('START INVOICE GENERATE 11' );
                logger( 'ERROR DIGIFACT HTTP' .  $response->status() .  $response->body() );
            }
        } catch (Exception $err) {
            logger('--------------------------------------------------ORDER INVOICE GENERATE ERROR: ' . print_r($err, true));
            return 0;
        }
        return 1;       
    }

    function _formatNIT($nit) {
        $nit = strtoupper(trim($nit));
        $nit = str_replace('-', '', $nit);
        if (!preg_match('/^\d{1,8}[0-9K]$/', $nit)) {
            return "CF";
        }
        return $nit;
        /*$cuerpo = substr($nit, 0, -1);
        $verificador = substr($nit, -1);
        $factor = strlen($cuerpo) + 1;
        $total = 0;
        for ($i = 0; $i < strlen($cuerpo); $i++) {
            $total += $cuerpo[$i] * ($factor - $i);
        }

        $residuo = $total % 11;
        $digitoCalculado = 11 - $residuo;

        if ($digitoCalculado == 10) {
            $digitoCalculado = 'K';
        } elseif ($digitoCalculado == 11) {
            $digitoCalculado = '0';
        } else {
            $digitoCalculado = (string)$digitoCalculado;
        }

        // Validar contra el dígito ingresado
        if ($digitoCalculado !== $verificador) {
            return "CF";
        }*/

        //return str_pad($nit, 12, '0', STR_PAD_LEFT);
        //return $nit;
    }

    public function calculator(Request $request)
    {
        $combos = ProductCombo::orderBy('descripcion')
        ->select(
            'id',
            'descripcion',
            'precio'
        )
        ->get();
        
         $variants = Variant::with('product')
        ->join('products', 'variants.id_producto', '=', 'products.id')
        ->orderBy('products.descripcion')
        ->orderBy('variants.descripcion')
        ->select(
            'variants.id',
            'variants.precio',
            DB::raw("CONCAT(products.descripcion,' - ', variants.descripcion) as descripcion")
        )
        ->get();

        return view('orders.calculator', compact( 'variants','combos'));
    }

    

}
    
