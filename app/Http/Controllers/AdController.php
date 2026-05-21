<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Ad;
use App\Models\AdCost;
use App\Models\AdProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdController extends Controller
{
   
    public function index()
    {
        
        return view('ads.index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $ids = array_map('str_getcsv', file($file->getRealPath()));
        array_shift($ids);
      
    
        // Flatten the array and remove duplicates
        $ids = array_unique(array_column($ids, 0));
        
        // Check which IDs do not exist in the database
        $existingIds = Ad::whereIn('fb_id', $ids)->pluck('fb_id')->toArray();
        
        $missingIds = array_diff($ids, $existingIds);
        $products = Product::orderBy('descripcion', 'asc')->get();
        return view('ads.index', compact('missingIds','products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'products' => 'required|array',
        ]);

        $fbId = $request->id;
        $productIds = $request->products;

        // Create the Ad record
        $newAd = Ad::create([
            'fb_id' => $fbId,
        ]);

        // Attach products to the new Ad
        foreach ($productIds as $productId) {
            AdProduct::create([
                'id_ad' => $newAd->id,
                'id_producto' => $productId,
            ]);
        }

        return response()->json([
            'message' => "Data for ID $fbId has been saved successfully!",
        ], 200);
    }

    public function costs()
    {
        return view('ads.costs');
    }


    function costs_upload(Request $request)
    {
        
        logger('IMPORT AD COSTS');
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);
        $file = $request->file('file');

        DB::beginTransaction();

        try {
            // Open the CSV file for reading
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
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
            return redirect()->route('ads.costs')->with('success', 'Costos actualizados exitosamente!');
        } catch (Exception $e) {
            // Rollback if there's an error
            DB::rollBack();
            echo "Failed to import data: " . $e->getMessage();
        }
    }


}
    
