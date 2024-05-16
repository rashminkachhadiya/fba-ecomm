<?php

namespace Database\Seeders;

use App\Models\StoreConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storeConfigList = [
            [
                'id' => 1,
                'store_type' => 'Amazon CA',
                'store_country' => 'CA',
                'store_url' => 'Amazon.ca',
                'aws_endpoint' => 'https://sellingpartnerapi-na.amazon.com',
                'store_currency' => 'CAD',
                'store_timezone' => 'America/Los_Angeles',
                'amazon_marketplace_id' => 'A2EUQ1WTGCTBG2',
                'amazon_aws_region' => 'us-east-1'
            ],
            [
                'id' => 2,
                'store_type' => 'Amazon US',
                'store_country' => 'US',
                'store_url' => 'Amazon.com',
                'aws_endpoint' => 'https://sellingpartnerapi-na.amazon.com',
                'store_currency' => 'USD',
                'store_timezone' => 'America/Los_Angeles',
                'amazon_marketplace_id' => 'ATVPDKIKX0DER',
                'amazon_aws_region' => 'us-east-1'
            ],
            [
                'id' => 3,
                'store_type' => 'Shopify',
                'store_country' => 'CA',
                'store_url' => 'canadatraxxas-ca.myshopify.com',
                'aws_endpoint' => null,
                'store_currency' => 'CAD',
                'store_timezone' => 'America/Los_Angeles',
                'amazon_marketplace_id' => null,
                'amazon_aws_region' => null
            ],
        ];

        foreach($storeConfigList as $storeConfig){
            $StoreConfigData = StoreConfig::find($storeConfig['id']);
            if ($StoreConfigData) {
                $StoreConfigData->update($storeConfig);
            } else {
                StoreConfig::create($storeConfig);
            }
        }
    }
}
