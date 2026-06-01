<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTag;
use App\Models\Variant;
use Illuminate\Http\Request;

/**
 * Staff UI to edit product_tags (the tags the bot/catalog read) without SQL.
 *
 * Writes through the default read-write connection (ProductTag/Eloquent) — this
 * is the admin, not the bot. It only ever touches product_tags. Product-level
 * tags use id_variante = NULL; variant-level tags carry the variant id.
 *
 * Note: the Shopify sync rebuilds VARIANT-level tags from each variant's
 * ossu_tags metafield, so manual variant tags here can be overwritten on the
 * next sync. Product-level tags are never touched by the sync.
 */
class MetabotTagController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('descripcion')->get(['id', 'descripcion']);

        // Tag counts per product (product-level only, id_variante NULL) for a quick glance.
        $counts = ProductTag::whereNull('id_variante')
            ->selectRaw('id_producto, COUNT(*) as c')
            ->groupBy('id_producto')
            ->pluck('c', 'id_producto');

        return view('metabot.tags.index', compact('products', 'counts'));
    }

    public function product($id)
    {
        $product     = Product::findOrFail($id);
        $productTags = ProductTag::where('id_producto', $id)->whereNull('id_variante')
            ->orderBy('tag')->get();
        $variants    = Variant::where('id_producto', $id)->orderBy('descripcion')
            ->get(['id', 'descripcion', 'codigo', 'precio', 'stock']);

        // Each variant's current tags, keyed by variant id, for the inline editors.
        $variantTags = ProductTag::where('id_producto', $id)->whereNotNull('id_variante')
            ->orderBy('tag')->get()->groupBy('id_variante');

        return view('metabot.tags.product', compact('product', 'productTags', 'variants', 'variantTags'));
    }

    /**
     * Saves the whole product screen in one POST: the product-level tags plus
     * every variant's tags (variant_tags[variantId][], variant_values[...][]).
     */
    public function saveProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->validateTags($request);

        // Product-level tags.
        $this->replaceTags((int) $product->id, null, $request->input('tags', []), $request->input('values', []));

        // Variant-level tags — only for variants that actually belong to this product.
        $validVariantIds = Variant::where('id_producto', $product->id)->pluck('id')
            ->map(fn ($i) => (int) $i)->all();
        $variantValues = $request->input('variant_values', []);
        foreach ($request->input('variant_tags', []) as $variantId => $vtags) {
            $vid = (int) $variantId;
            if (!in_array($vid, $validVariantIds, true)) {
                continue;
            }
            $this->replaceTags((int) $product->id, $vid, is_array($vtags) ? $vtags : [], $variantValues[$variantId] ?? []);
        }

        return redirect()->route('metabot.tags.product', ['id' => $product->id])
            ->with('success', 'Tags guardados.');
    }

    public function variant($id)
    {
        $variant = Variant::findOrFail($id);
        $product = Product::find($variant->id_producto);
        $tags    = ProductTag::where('id_variante', $id)->orderBy('tag')->get();

        // Sibling variants + their tags, so the editor can copy from one to another.
        $siblings = Variant::where('id_producto', $variant->id_producto)
            ->where('id', '!=', $id)
            ->orderBy('descripcion')
            ->get(['id', 'descripcion', 'codigo']);

        $siblingTags = ProductTag::where('id_producto', $variant->id_producto)
            ->whereNotNull('id_variante')
            ->where('id_variante', '!=', $id)
            ->get(['id_variante', 'tag', 'valor'])
            ->groupBy('id_variante')
            ->map(function ($rows) {
                return $rows->map(fn ($r) => ['tag' => $r->tag, 'valor' => $r->valor])->values();
            });

        return view('metabot.tags.variant', compact('variant', 'product', 'tags', 'siblings', 'siblingTags'));
    }

    public function saveVariant(Request $request, $id)
    {
        $variant = Variant::findOrFail($id);
        $this->validateTags($request);

        $this->replaceTags((int) $variant->id_producto, (int) $variant->id, $request->input('tags', []), $request->input('values', []));

        return redirect()->route('metabot.tags.variant', ['id' => $variant->id])
            ->with('success', 'Tags de la variante guardados.');
    }

    private function validateTags(Request $request): void
    {
        $request->validate([
            'tags'              => ['array'],
            'tags.*'            => ['nullable', 'string', 'max:50'],
            'values'            => ['array'],
            'values.*'          => ['nullable', 'string', 'max:500'],
            'variant_tags'      => ['array'],
            'variant_tags.*'    => ['array'],
            'variant_tags.*.*'  => ['nullable', 'string', 'max:50'],
            'variant_values'    => ['array'],
            'variant_values.*'  => ['array'],
            'variant_values.*.*' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * Replace the tag set for a product-level (variantId null) or variant-level row
     * group: delete the existing rows for that scope, then insert the submitted pairs.
     * Empty tag names are skipped; empty values store NULL.
     */
    private function replaceTags(int $productId, ?int $variantId, array $tags, array $values): void
    {
        $query = ProductTag::where('id_producto', $productId);
        if ($variantId === null) {
            $query->whereNull('id_variante');
        } else {
            $query->where('id_variante', $variantId);
        }
        $query->delete();

        foreach ($tags as $i => $tag) {
            $tag = trim((string) $tag);
            if ($tag === '') {
                continue;
            }
            $value = $values[$i] ?? null;

            ProductTag::create([
                'id_producto' => $productId,
                'id_variante' => $variantId,
                'tag'         => mb_substr($tag, 0, 50),
                'valor'       => ($value === null || $value === '') ? null : mb_substr((string) $value, 0, 500),
            ]);
        }
    }
}
