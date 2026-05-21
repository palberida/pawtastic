<?php
// Load environment variables from .env file
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
//print_r($_ENV);

// Retrieve database credentials from environment variables
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_DATABASE') ?: 'pawtastic';
$dbUser = getenv('DB_USERNAME') ?: 'pawtastic_user';
$dbPass = getenv('DB_PASSWORD') ?: 'pawtastic/web6';



try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$accessToken = 'EAAKNMCga8CQBOyFOYvC3Quuq3zJpC1a7nCfXVyHv7Bs8vd4NxD1w3wuG0rGptCoj8vj89RFzfrqzM4155ZAmjP7JZBElXHj0ykY29txRdEtXHR2q6UO82KZBVyCJGN0E7cwf6LjUw0AqDYzLQqZBhhy1dbyVymVZAdhkIXZA4Q1WnuIhqMwLgzFpeLdNWTZCzY70AZDZD'; // Replace with your access token
$adAccountId = '693535683206987'; 
$endDate = date('Y-m-d'); 
$endDate = date('Y-m-d', strtotime('-0 day')); 
if (isset($argv[1])) {
    $param = $argv[1];
}
echo 'CALCULATING DAYS ' . $param;
$startDate = date('Y-m-d', strtotime("-$param day")); 


$apiVersion = 'v22.0'; // Meta API version

// API URL
$url = "https://graph.facebook.com/$apiVersion/act_$adAccountId/ads?fields=id,name,created_time,status&limit=100&access_token=$accessToken";
$url_costs = "https://graph.facebook.com/$apiVersion/act_$adAccountId/insights?fields=ad_id,date_start,spend&access_token=$accessToken&time_range={'since':'$startDate','until':'$endDate'}&level=ad&time_increment=1";

// Function to check if an ad ID exists in the database
function adExists($pdo, $adId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE fb_id = ?");
    $stmt->execute([$adId]);
    return $stmt->fetchColumn() > 0;
}

// Function to check if an ad ID exists in the database
function productExists($pdo, $productId) {

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetchColumn() > 0;
}

function getAdId($pdo, $adId) {
    $stmt = $pdo->prepare("SELECT id FROM ads WHERE fb_id = ?");
    $stmt->execute([$adId]);
    return $stmt->fetchColumn();
}

function getAdCostId($pdo, $adId,$dia) {
    $stmt = $pdo->prepare("SELECT id FROM ad_costs WHERE id_ad = ? and dia = ?");
    $stmt->execute([$adId,$dia]);
    return $stmt->fetchColumn();
}

function stripNonBasicChars($text) {
    return preg_replace('/[^\x20-\x7E]/u', '', $text);
}

// Function to fetch all ads from Meta API
function fetchAllAds($url, $startDate, $endDate, $pdo) {
    $newAds = 0;
    $newAdCosts = 0;
    $newAdCostsU = 0;
    while ($url) {
        // Fetch API Data
        $response = file_get_contents($url);
        if (!$response) {
            die("Error fetching data from Meta API.");
        }

        $data = json_decode($response, true);

        // Process each ad
        if (isset($data['data'])) {
            foreach ($data['data'] as $ad) {
                $adId = $ad['id'];
                $adName = stripNonBasicChars($ad['name']);
                $createdTime = date('Y-m-d', strtotime($ad['created_time']));
                //echo "XAd ID: $adId \n";
                if ($createdTime >= $startDate && $createdTime <= $endDate) {
                    if (!adExists($pdo, $adId)) {
                        // Insert new ad ID into the database
                        //echo "------------------------\n";
                        $stmt = $pdo->prepare("INSERT INTO ads (fb_id,descripcion) VALUES (?,?)");
                        $stmt->execute([$adId,$adName]);
                        $newRowId = $pdo->lastInsertId();
                        $newAds++;
                        //echo "Inserted Ad ID: $adId\n";
                        $products = explode("-", $adName);
                        //echo "Inserted Ad NAME: $adName\n";
                        //echo "------------------------\n";
                        foreach($products as $product){
                            //echo "Inserted Ad PRODUCT: $product\n";
                            if (productExists($pdo, $product)) {
                                echo "Inserted Ad PRODUCT EXISTS: $product\n";
                                $stmt = $pdo->prepare("INSERT INTO ad_products (id_ad,id_producto) VALUES (?,?)");
                                $stmt->execute([$newRowId,$product]);
                            }
                        }
                    } else {
                        echo "Ad ID already exists: $adId\n";
                    }
                }
            }
        }

        // Check for pagination
        $url = isset($data['paging']['next']) ? $data['paging']['next'] : null;
    }

    echo "Total New Ads Inserted: $newAds\n";
}

// Function to fetch all ads from Meta API
function fetchAllAdCosts($url, $startDate, $endDate, $pdo) {
    $newAdCosts = 0;
    $newAdCostsU = 0;
    while ($url) {
        // Fetch API Data
        $response = file_get_contents($url);
        if (!$response) {
            die("Error fetching data from Meta API.");
        }

        $data = json_decode($response, true);

        // Process each ad
        if (isset($data['data'])) {
            foreach ($data['data'] as $ad) {
                $adId = $ad['ad_id'];
                $spend = $ad['spend'];
                $spend = $spend * 7.8;
                $createdTime = date('Y-m-d', strtotime($ad['date_start']));
                
                    $adOssuId = getAdId($pdo, $adId);
                    //echo "XAd ID: $adOssuId $spend $createdTime\n";
                    if ($adOssuId) {
                        // Insert new ad ID into the database
                        $adCostoId = getAdCostId($pdo, $adOssuId,$createdTime);
                        if (!$adCostoId) {
                            $stmt = $pdo->prepare("INSERT INTO ad_costs (id_ad,costo,dia) VALUES (?,?,?)");
                            $stmt->execute([$adOssuId,$spend,$createdTime]);
                            $newRowId = $pdo->lastInsertId();
                            echo "INSERTED $newRowId \n";
                            $newAdCosts++;
                        }else{
                            $stmt = $pdo->prepare("UPDATE ad_costs set costo = ? where id = ?");
                            $stmt->execute([$spend,$adCostoId]);
                            $newRowId = $pdo->lastInsertId();
                            $newAdCostsU++;
                        }
                        
                        
                    } else {
                        echo "Ad ID not found: $adId\n";
                    }
                
            }
        }

        // Check for pagination
        $url = isset($data['paging']['next']) ? $data['paging']['next'] : null;
    }

    echo "Total New Ad Costs Inserted $newAdCosts   Updated $newAdCostsU\n";
}

// Run the script
fetchAllAds($url, $startDate, $endDate, $pdo);
fetchAllAdCosts($url_costs, $startDate, $endDate, $pdo);
?>


