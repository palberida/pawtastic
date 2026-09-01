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
     * Saves the stock of every row edited on the current page in one POST.
     * Only values that actually differ from what is stored are written.
     */
    public function updateStock(Request $request)
    {
        $request->validate([
            'stock'   => 'array',
            'stock.*' => 'nullable|integer|min:0',
        ], [
            'stock.*.integer' => 'El stock debe ser un número entero.',
            'stock.*.min'     => 'El stock no puede ser negativo.',
        ]);

        $changed = 0;
        DB::beginTransaction();
        try{
            foreach ($request->input('stock', []) as $variantId => $value) {
                if ($value === null || $value === '') continue;

                $variant = Variant::find($variantId);
                if (!$variant) continue;

                $newStock = (int) $value;
                if ((int) $variant->stock === $newStock) continue;

                logger('INVENTORY STOCK UPDATE: variante ' . $variant->id
                    . ' de ' . $variant->stock . ' a ' . $newStock
                    . ' por ' . (Auth::user()->name ?? 'desconocido'));

                $variant->stock = $newStock;
                $variant->save();
                $changed++;
            }
            DB::commit();
        } catch (Exception $err) {
            DB::rollBack();
            logger('--------------------------------------------------INVENTORY UPDATE ERROR: ' . print_r($err, true));
            return redirect()->back()
                ->withInput()
                ->withErrors(['update_error' => 'An error occurred: ' . $err->getMessage()]);
        }

        return redirect()->route('inventory.index', [
                'search' => $request->input('search'),
                'page'   => $request->input('page'),
            ])
            ->with('success', $changed === 1
                ? 'Se actualizó 1 variante.'
                : 'Se actualizaron ' . $changed . ' variantes.');
    }
}
