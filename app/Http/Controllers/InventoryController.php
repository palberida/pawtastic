<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Inventario — staff screen to look up stock and correct it by hand.
 *
 * Writes straight to variants.stock. There is no longer a Shopify sync that
 * could overwrite manual values, so this screen is the source of truth for
 * stock alongside the automatic decrement in OrderController::save().
 * Every change is logged with the old and new value so a bad edit can be traced.
 */
class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $variants = DB::table('variants as v')
            ->join('products as p', 'v.id_producto', '=', 'p.id')
            ->select(
                'v.id',
                'v.descripcion as variant_description',
                'v.codigo',
                'v.stock',
                'p.descripcion as product_description'
            )
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('p.descripcion', 'like', $like)
                        ->orWhere('v.descripcion', 'like', $like)
                        ->orWhere('v.codigo', 'like', $like);
                });
            })
            ->orderBy('p.descripcion')
            ->orderBy('v.descripcion')
            ->paginate(50);

        return view('inventory.index', compact('variants', 'search'));
    }

    /**
     * Saves the stock of a single variant — one row, one POST, so an edit is
     * never lost by paginating or re-searching before saving.
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
        ], [
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer'  => 'El stock debe ser un número entero.',
            'stock.min'      => 'El stock no puede ser negativo.',
        ]);

        $redirect = [
            'search' => $request->input('search'),
            'page'   => $request->input('page'),
        ];

        try{
            $variant  = Variant::findOrFail($id);
            $newStock = (int) $request->input('stock');

            if ((int) $variant->stock === $newStock) {
                return redirect()->route('inventory.index', $redirect)
                    ->with('success', 'Sin cambios en ' . $variant->descripcion . '.');
            }

            logger('INVENTORY STOCK UPDATE: variante ' . $variant->id
                . ' de ' . $variant->stock . ' a ' . $newStock
                . ' por ' . (Auth::user()->name ?? 'desconocido'));

            $oldStock = $variant->stock;
            $variant->stock = $newStock;
            $variant->save();
        } catch (Exception $err) {
            logger('--------------------------------------------------INVENTORY UPDATE ERROR: ' . print_r($err, true));
            return redirect()->back()
                ->withInput()
                ->withErrors(['update_error' => 'An error occurred: ' . $err->getMessage()]);
        }

        return redirect()->route('inventory.index', $redirect)
            ->with('success', $variant->descripcion . ': stock de ' . $oldStock . ' a ' . $newStock . '.');
    }
}
