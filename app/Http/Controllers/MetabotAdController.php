<?php

namespace App\Http\Controllers;

use App\Models\MetabotAd;
use App\Models\Product;
use Illuminate\Http\Request;

class MetabotAdController extends Controller
{
    public function index()
    {
        $ads = MetabotAd::withCount('products')
            ->orderByDesc('created_at')
            ->get();

        return view('metabot.ads.index', compact('ads'));
    }

    public function new()
    {
        $products = Product::orderBy('descripcion')->get();
        $selected = [];

        return view('metabot.ads.new', compact('products', 'selected'));
    }

    public function store(Request $request)
    {
        $data = $this->validateAd($request);

        $ad = MetabotAd::create([
            'source_id'    => $data['source_id'],
            'name'         => $data['name'] ?? null,
            'scope'        => $data['scope'],
            'welcome_text' => $data['welcome_text'] ?? null,
            'status'       => $data['status'],
        ]);

        $this->syncProducts($ad, $request);

        return redirect()->route('metabot.ads.index')->with('success', 'Anuncio creado.');
    }

    public function edit($id)
    {
        $ad       = MetabotAd::findOrFail($id);
        $products = Product::orderBy('descripcion')->get();
        $selected = $ad->products()->pluck('products.id')->all();

        return view('metabot.ads.edit', compact('ad', 'products', 'selected'));
    }

    public function update(Request $request, $id)
    {
        $ad   = MetabotAd::findOrFail($id);
        $data = $this->validateAd($request, $ad->id);

        $ad->update([
            'source_id'    => $data['source_id'],
            'name'         => $data['name'] ?? null,
            'scope'        => $data['scope'],
            'welcome_text' => $data['welcome_text'] ?? null,
            'status'       => $data['status'],
        ]);

        $this->syncProducts($ad, $request);

        return redirect()->route('metabot.ads.index')->with('success', 'Anuncio actualizado.');
    }

    private function validateAd(Request $request, $ignoreId = null)
    {
        $unique = 'unique:metabot_ads,source_id' . ($ignoreId ? ',' . $ignoreId : '');

        return $request->validate([
            'source_id'     => ['required', 'string', 'max:128', $unique],
            'name'          => ['nullable', 'string', 'max:150'],
            'scope'         => ['required', 'in:site_wide,product_set'],
            'welcome_text'  => ['nullable', 'string', 'max:1024'],
            'status'        => ['required', 'in:active,paused'],
            'product_ids'   => ['array'],
            'product_ids.*' => ['integer'],
        ]);
    }

    /**
     * Only product_set ads map products; site_wide derives its scope from the
     * product-level `categoria` tag, so we clear any mapping if scope flips.
     */
    private function syncProducts(MetabotAd $ad, Request $request)
    {
        if ($request->input('scope') === 'product_set') {
            $ad->products()->sync($request->input('product_ids', []));
        } else {
            $ad->products()->detach();
        }
    }
}
