<?php

namespace App\Services;

use App\Models\MetabotAd;
use Illuminate\Support\Facades\DB;

/**
 * Read-only view of the shop catalog for the bot.
 *
 * Reads through the connection named in config('metabot.catalog_connection')
 * — by default the RO user (mysql_metabot_ro). It NEVER writes: per CLAUDE.md
 * the bot must not touch products / variants / orders / tags.
 *
 * The shape returned by forAd() is handed to Claude as structured context; tags
 * are read generically (whatever (tag, valor) rows exist) rather than hard-coding
 * a vocabulary, so new tags appear in the bot's context without a code change.
 */
class MetabotCatalog
{
    private function db()
    {
        return DB::connection(config('metabot.catalog_connection'));
    }

    /**
     * Build the catalog context for an ad's active scope.
     *
     * - product_set (incl. single-product): the explicitly mapped products.
     * - site_wide: every product that carries a `categoria` tag (catalog is small).
     *
     * @return array{scope:string, products:array}
     */
    public function forAd(MetabotAd $ad): array
    {
        $scope = $ad->scope ?: 'product_set';

        if ($scope === 'site_wide') {
            $productIds = $this->db()->table('product_tags')
                ->where('tag', 'categoria')
                ->whereNull('id_variante')
                ->distinct()
                ->pluck('id_producto')
                ->all();
        } else {
            $productIds = $this->db()->table('metabot_ad_products')
                ->where('metabot_ad_id', $ad->id)
                ->pluck('id_producto')
                ->all();
        }

        return [
            'scope'    => $scope,
            'products' => $this->products($productIds),
        ];
    }

    /**
     * The whole catalog, hydrated and grouped by the product-level `categoria`
     * tag (untagged products fall under "Otros"). Used by the inbox quick-reply
     * console. Small-shop sized; products() caps the set at 200.
     *
     * @return array<array{categoria:string, products:array}>
     */
    public function everything(): array
    {
        $ids = $this->db()->table('products')->orderBy('descripcion')->pluck('id')->all();

        $groups = [];
        foreach ($this->products($ids) as $p) {
            $cat = $p['categoria'] !== null && $p['categoria'] !== '' ? $p['categoria'] : 'Otros';
            $groups[$cat][] = $p;
        }
        ksort($groups);

        $out = [];
        foreach ($groups as $cat => $prods) {
            $out[] = ['categoria' => $cat, 'products' => $prods];
        }

        return $out;
    }

    /**
     * Hydrate a set of products with their variants, tags, prices and photos.
     *
     * @param  array<int>  $productIds
     * @return array
     */
    public function products(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }
        // Safety cap so a misconfigured site-wide ad can't dump the whole DB into a prompt.
        $productIds = array_slice($productIds, 0, 200);

        $products = $this->db()->table('products')
            ->whereIn('id', $productIds)
            ->get(['id', 'descripcion']);

        $variants = $this->db()->table('variants')
            ->whereIn('id_producto', $productIds)
            ->get(['id', 'id_producto', 'descripcion', 'codigo', 'precio', 'stock']);

        $tags = $this->db()->table('product_tags')
            ->whereIn('id_producto', $productIds)
            ->get(['id_producto', 'id_variante', 'tag', 'valor']);

        // Index tags: product-level (id_variante NULL) and per-variant.
        $productTags = [];   // [id_producto][tag] = valor
        $variantTags = [];   // [id_variante][tag] = valor
        foreach ($tags as $t) {
            if ($t->id_variante === null) {
                $productTags[$t->id_producto][$t->tag] = $t->valor;
            } else {
                $variantTags[$t->id_variante][$t->tag] = $t->valor;
            }
        }

        $variantsByProduct = [];
        foreach ($variants as $v) {
            $variantsByProduct[$v->id_producto][] = $v;
        }

        $out = [];
        foreach ($products as $p) {
            $pTags = $productTags[$p->id] ?? [];
            $pivot = $pTags['pivot'] ?? null;

            $vList   = $variantsByProduct[$p->id] ?? [];
            $prices  = [];
            $vOut    = [];
            foreach ($vList as $v) {
                $vTags = $variantTags[$v->id] ?? [];
                $price = $v->precio !== null ? (float) $v->precio : null;
                if ($price !== null) {
                    $prices[] = $price;
                }
                $stock = (int) ($v->stock ?? 0);
                $vOut[] = [
                    'codigo'  => $v->codigo,
                    'nombre'  => $v->descripcion,
                    'precio'  => $price,
                    'stock'   => $stock,
                    'agotado' => $stock <= 0,
                    'pivot_valor' => $pivot ? ($vTags[$pivot] ?? null) : null,
                    'images'  => $this->imageUrls($vTags),
                    'tags'    => $this->displayTags($vTags),
                ];
            }

            // Product-level photos are the fallback when a variant has none.
            $productImages = $this->imageUrls($pTags);

            $out[] = [
                'id'           => $p->id,
                'nombre'       => $p->descripcion,
                'categoria'    => $pTags['categoria'] ?? null,
                'pivot'        => $pivot,
                'precio_min'   => !empty($prices) ? min($prices) : null,
                'precio_max'   => !empty($prices) ? max($prices) : null,
                'agotado_todo' => !empty($vOut) && collect($vOut)->every(fn ($v) => $v['agotado']),
                'images'       => $productImages,
                'tags'         => $this->displayTags($pTags),
                'variants'     => $vOut,
            ];
        }

        return $out;
    }

    /**
     * Ordered image URLs from image_1, image_2, … tags.
     *
     * @param  array<string,string>  $tags
     * @return array<string>
     */
    private function imageUrls(array $tags): array
    {
        $images = [];
        foreach ($tags as $tag => $valor) {
            if (preg_match('/^image_(\d+)$/', $tag, $m) && $valor) {
                $images[(int) $m[1]] = $valor;
            }
        }
        ksort($images);

        return array_values($images);
    }

    /**
     * Tags worth showing Claude as descriptive attributes — drops the structural
     * ones it already gets through dedicated fields (images, pivot, categoria).
     *
     * @param  array<string,string>  $tags
     * @return array<string,string>
     */
    private function displayTags(array $tags): array
    {
        $out = [];
        foreach ($tags as $tag => $valor) {
            if ($tag === 'pivot' || $tag === 'categoria' || preg_match('/^image_\d+$/', $tag)) {
                continue;
            }
            $out[$tag] = $valor;
        }

        return $out;
    }
}
