<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ShopifyWebhooks extends Command
{
    protected $signature = 'shopify:webhooks
                            {action : list|create|delete|replace}
                            {--topic= : Webhook topic, e.g. products/update}
                            {--address= : Full https URL Shopify should POST to}
                            {--namespaces= : Comma-separated metafield namespaces to include}
                            {--id= : Webhook id (for delete)}
                            {--api-version=2024-01}';

    protected $description = 'Manage Shopify Admin API webhook subscriptions for this shop.';

    public function handle()
    {
        $shop  = env('SHOPIFY_SHOP');
        $token = env('SHOPIFY_ADMIN_TOKEN');

        if (!$shop || !$token) {
            $this->error('SHOPIFY_SHOP and SHOPIFY_ADMIN_TOKEN must be set in .env');
            return 1;
        }

        $base = "https://{$shop}/admin/api/{$this->option('api-version')}/webhooks";
        $http = Http::withHeaders(['X-Shopify-Access-Token' => $token])->acceptJson();

        switch ($this->argument('action')) {
            case 'list':    return $this->list($http, $base);
            case 'create':  return $this->create($http, $base);
            case 'delete':  return $this->delete($http, $base);
            case 'replace': return $this->replace($http, $base);
        }

        $this->error('Unknown action. Use: list | create | delete | replace');
        return 1;
    }

    private function list($http, $base): int
    {
        $res = $http->get("{$base}.json");
        if ($res->failed()) {
            $this->error("List failed: {$res->status()} {$res->body()}");
            return 1;
        }
        $hooks = $res->json('webhooks') ?? [];
        if (!$hooks) {
            $this->info('No webhook subscriptions found for this app on this shop.');
            return 0;
        }
        $this->table(
            ['id', 'topic', 'address', 'metafield_namespaces', 'created_at'],
            array_map(fn ($w) => [
                $w['id'],
                $w['topic'],
                $w['address'],
                implode(',', $w['metafield_namespaces'] ?? []),
                $w['created_at'] ?? '',
            ], $hooks)
        );
        return 0;
    }

    private function create($http, $base): int
    {
        $topic   = $this->option('topic');
        $address = $this->option('address');
        if (!$topic || !$address) {
            $this->error('--topic and --address are required for create.');
            return 1;
        }

        $payload = [
            'webhook' => [
                'topic'   => $topic,
                'address' => $address,
                'format'  => 'json',
            ],
        ];

        if ($ns = $this->option('namespaces')) {
            $payload['webhook']['metafield_namespaces'] = array_map('trim', explode(',', $ns));
        }

        $res = $http->post("{$base}.json", $payload);
        if ($res->failed()) {
            $this->error("Create failed: {$res->status()} {$res->body()}");
            return 1;
        }

        $this->info('Created:');
        $this->line(json_encode($res->json('webhook'), JSON_PRETTY_PRINT));
        return 0;
    }

    private function delete($http, $base): int
    {
        $id = $this->option('id');
        if (!$id) {
            $this->error('--id is required for delete.');
            return 1;
        }
        $res = $http->delete("{$base}/{$id}.json");
        if ($res->failed()) {
            $this->error("Delete failed: {$res->status()} {$res->body()}");
            return 1;
        }
        $this->info("Deleted webhook {$id}.");
        return 0;
    }

    private function replace($http, $base): int
    {
        $topic   = $this->option('topic');
        $address = $this->option('address');
        if (!$topic || !$address) {
            $this->error('--topic and --address are required for replace.');
            return 1;
        }

        $existing = $http->get("{$base}.json")->json('webhooks') ?? [];
        $matches  = array_filter($existing, fn ($w) => $w['topic'] === $topic);

        foreach ($matches as $w) {
            $this->line("Deleting existing {$w['topic']} webhook id={$w['id']} → {$w['address']}");
            $del = $http->delete("{$base}/{$w['id']}.json");
            if ($del->failed()) {
                $this->error("  Delete failed: {$del->status()} {$del->body()}");
                return 1;
            }
        }

        return $this->create($http, $base);
    }
}
