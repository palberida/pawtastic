<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\TransferOrder;
use App\Models\Order;
use App\Models\Item;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use DateTime;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(Request $request)
    {

        $transfers = Transfer::query()
            ->orderBy('created_at', 'desc')
            ->paginate(10); 

        return view('transfers.index', compact('transfers'));
    }

    public function new(Request $request)
    {
        return view('transfers.new_step1');
    }

    public function newStep2(Request $request)
    {
        $rangoFin = Carbon::parse($request->input('rango_fin'));
        $now = Carbon::now();

        // Check if rango_fin is sooner than today
        if ($rangoFin->gte($now)) {
            return back()->withErrors([
                'rango_fin' => 'La fecha de rango fin debe ser anterior a hoy.'
            ])->withInput();
        }

        $rangoInicio = $this->getLastTransfer();
        $total = $this->sumOrdersBeforeDate($rangoFin, $rangoInicio);

        return view('transfers.new_step2', compact('total', 'rangoFin', 'rangoInicio'));
    }

    public function newStep3(Request $request)
    {

        $rangoFin = Carbon::parse($request->input('rango_fin'));
        $rangoInicio = $this->getLastTransfer();

        $total = $this->sumOrdersBeforeDate($rangoFin,$rangoInicio);
        return view('transfers.new_step2', compact('total','rangoFin','rangoInicio'));
    }

    public function store(Request $request)
    {
        logger('TRANSFER ');
        DB::beginTransaction();
        try {
            $rangoInicio = $request->input('rango_inicio');
            $rangoFin = Carbon::parse($request->input('rango_fin'));
            if($rangoInicio!=''){
                $validatedData = $request->validate([
                    'codigo' => 'required|string|max:50',
                    'descripcion' => 'required|string|max:150',
                    'total' => 'required|numeric',
                    
                ]);

                $transfer = Transfer::create([
                    'codigo' => $validatedData['codigo'],
                    'descripcion' => $validatedData['descripcion'],
                    'total' => $validatedData['total'],
                    'rango_fin' => $rangoFin,
                    'rango_inicio' => $rangoInicio,
                ]);

                $ids = $this->getOrdersBeforeDate($rangoFin,$rangoInicio);
                
            }else{
        
                $validatedData = $request->validate([
                    'codigo' => 'required|string|max:50',
                    'descripcion' => 'required|string|max:150',
                    'total' => 'required|numeric',
                    
                ]);

                $transfer = Transfer::create([
                    'codigo' => $validatedData['codigo'],
                    'descripcion' => $validatedData['descripcion'],
                    'total' => $validatedData['total'],
                    'rango_fin' => $rangoFin,
                    
                ]);
                
                $ids = $this->getOrdersBeforeDate($rangoFin);
            }
            $idTransferencia = $transfer->id;
            $insertData = $ids->map(function ($id) use ($idTransferencia) {
                return [
                    'id_orden' => $id,
                    'id_transferencia' => $idTransferencia,
                    
                ];
            })->toArray();
            
            TransferOrder::insert($insertData);

            //complete orders
            Order::whereIn('id', $ids)
            ->whereNotIn('estado', ['completado', 'cancelado'])
            ->update([
                'estado' => 'completado',
                'pagado' => 1,
                'updated_at' => now(),
            ]);
            DB::commit();
            return redirect()->route('transfers.index')
            ->with('success', 'Transfer created successfully!');
        } catch (Exception $e) {
            
            DB::rollBack();
            echo "Failed to import data: " . $e->getMessage();
        }
        

        
    }

    public function sumOrdersBeforeDate($endDate,$startDate = null)
    {
        $endDate = Carbon::parse($endDate);
        $sum = 0;
        if($startDate){
            $sum = Order::whereBetween('created_at', [$startDate, $endDate])
            //->where('mensajero', '!=', 'caex')
            ->whereNotIn('mensajero', ['caex', 'forza'])
            ->where('estado', '!=', 'cancelado')
            ->where('pagado', '=', 0)
            ->sum('total');
            logger('TRANSFER');
            logger($startDate);
            logger($endDate);
        }else{
            $sum = Order::where('created_at', '<', $endDate)
            //->where('mensajero', '!=', 'caex')
            ->whereNotIn('mensajero', ['caex', 'forza'])
            ->where('estado', '!=', 'cancelado')
            ->where('pagado', '=', 0)
            ->sum('total');
            logger('TRANSFER 2');
            logger($endDate);
            logger($startDate);
 
        }
        return number_format($sum,2, '.', '');
    }

    public function getOrdersBeforeDate($endDate,$startDate = null)
    {
        $endDate = Carbon::parse($endDate);
        $sum = 0;
        $orders = null;
        if($startDate){

            $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            //->where('mensajero', '!=', 'caex')
            ->whereNotIn('mensajero', ['caex', 'forza'])
            ->where('estado', '!=', 'cancelado')
            ->where('pagado', '=', 0)
            ->pluck('id');

        }else{

            $orders = Order::where('created_at', '<', $endDate)
            //->where('mensajero', '!=', 'caex')
            ->whereNotIn('mensajero', ['caex', 'forza'])
            ->where('estado', '!=', 'cancelado')
            ->where('pagado', '=', 0)
            ->pluck('id');

 
        }
        return $orders;
    }


    public function getLastTransfer()
    {
        $latestTransfer = Transfer::latest('rango_fin')->first();

        if($latestTransfer){
            return $latestTransfer->rango_fin;    
        }
        return '';
    }

}
