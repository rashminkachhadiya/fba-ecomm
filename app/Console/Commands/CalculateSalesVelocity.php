<?php

namespace App\Console\Commands;

use App\Models\SalesVelocity;
use App\Models\SupplierProduct;
use App\Services\CronCommonService;
use App\Traits\CalculateSalesVelocityTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Batch;
use Illuminate\Support\Facades\Log;

class CalculateSalesVelocity extends Command
{
    use CalculateSalesVelocityTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-sales-velocity {duration?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Sales Velocity(ROS) for different duration of time such as 2 days, 7 days and 30 days';

    protected $cronService;
    protected array $durationList = [2, 7, 30];

    public function __construct()
    {
        parent::__construct();
        $this->cronService = new CronCommonService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $duration = $this->argument('duration');

        if(empty($duration))
        {
            foreach ($this->durationList as $value) {
                $this->productOffset = 0;
                Artisan::call('app:calculate-sales-velocity', ['duration' => $value]);
            }
        }else{
            // Check duration is valid or not
            if(!in_array($duration, $this->durationList))
            {
                $this->error("Invalid duration");
                $this->output->write("Valid duration are ");
                $this->info(implode(',', $this->durationList));
                return;
            }
            // Set cron name & cron type
            $this->cronService->cronName = 'CRON_'.time().'_'.$duration;
            $this->cronService->cronType = 'CALCULATE_SALES_VELOCITY';
            
            // Log cron start
            $cronLog = $this->cronService->storeCronLogs();
            
            if(!$cronLog) return;
            
            // Get last N dates for duration
            $dates = $this->getlastNDates($duration);

            // Calculate sales velocity for each duraion
            $this->calculateSalesVelocity($duration, $dates);

            // Log cron end
            $this->cronService->updateCronLogs();
        }
    }

    public function calculateSalesVelocity($duration, $dates) : void
    {
        // $supplierIds = [];
        // Get amazon products
        $getProducts = $this->getAmazonProducts();
        // dd($getProducts->toArray());
    
        if($getProducts->count() == 0)
        {
            // Log cron end
            $this->cronService->updateCronLogs();
            return;
        }
    
        $getProductIds = array_column($getProducts->toArray(), 'id');

        // foreach ($getProducts as $products)
        // {
        //     if($products->supplier_products->count() > 0)
        //     {
        //         foreach ($products->supplier_products as $supplierProductsValue)
        //         {
        //             if($supplierProductsValue->supplier && !in_array($supplierProductsValue->supplier->id, $supplierIds))
        //             {
        //                 $supplierIds = [...$supplierIds, $supplierProductsValue->supplier->id];
        //             }
        //         }
        //     }
        // }

        // Get total orders for each product
        $getOrders = $this->getTotalOrders($getProductIds, $dates);
        $existingSalesVelocity = SalesVelocity::whereIn('amazon_product_id', $getProductIds)->pluck('id','amazon_product_id')->toArray();

        // if(empty($supplierIds))
        // {
        //     $getTotalOrderQty = 0;
        // }else{
            // Get total orders quantity from purchase order items based on product id and supplier id
            $getTotalOrderQty = $this->getTotalOrderQty($getProductIds);
        // }
        
        // $totalOrderQty = !empty($getTotalOrderQty) ? $getTotalOrderQty :  0;

        
        $insertArr = $updateArr = $updateSupplierProducts = [];

        foreach ($getProducts as $value) {

            $totalOrderQty = (!empty($getTotalOrderQty) && isset($getTotalOrderQty[$value->id])) ? $getTotalOrderQty[$value->id]['total_order_qty'] :  0;
            
            $unitAtAmazon = ($value->qty + ($value->afn_inbound_working_quantity + $value->afn_inbound_shipped_quantity + $value->afn_inbound_receiving_quantity)) - $value->afn_reserved_quantity;
            $ros = ($getOrders && isset($getOrders[$value->id]) && $getOrders[$value->id]['total_orders'] > 0) ? round($getOrders[$value->id]['total_orders'] / $duration, 2) : 0;
            $totalOrders = ($getOrders && isset($getOrders[$value->id])) ? $getOrders[$value->id]['total_orders'] : 0;

            if(in_array($value->id, $existingSalesVelocity))
            {
                if($duration == 2)
                {
                    $rosArr = [
                        'ros_2' => $ros,
                        'total_units_sold_2' => $totalOrders
                    ];
                } else if($duration == 7) {
                    $rosArr = [
                        'ros_7' => $ros,
                        'total_units_sold_7' => $totalOrders
                    ];
                } else if($duration == 30) {
                    $rosArr = [
                        'ros_30' => $ros,
                        'total_units_sold_30' => $totalOrders,
                        'suggested_quantity' => $this->calculateSuggestedQty($ros, $unitAtAmazon, $totalOrderQty),
                        'threshold_qty' => $this->calculateThresholdQty($ros)
                    ];
                }

                $updateArr[] = [
                    'id' => $existingSalesVelocity[$value->id],
                    ...$rosArr
                ];
            }else{
                $insertArr[] = [
                    'amazon_product_id' => $value->id,
                    'ros_2' => ($duration == 2) ? $ros : 0,
                    'ros_7' => ($duration == 7) ? $ros : 0,
                    'ros_30' => ($duration == 30) ? $ros : 0,
                    'total_units_sold_2' => $totalOrders,
                    'total_units_sold_7' => 0,
                    'total_units_sold_30' => 0,
                    'suggested_quantity' => 0
                ];
            }

            if($duration == 30 && !empty($value->supplier_products))
            {
                foreach ($value->supplier_products as $supplierProductValue)
                {
                    $supplierLeadTime = ($supplierProductValue->supplier) ? $supplierProductValue->supplier->lead_time : 0;
                    $updateSupplierProducts[] = [
                        'id' => $supplierProductValue->id,
                        'suggested_quantity' => $this->calculateSuggestedQty($ros, $unitAtAmazon, $totalOrderQty, $supplierLeadTime),
                        'threshold_qty' => $this->calculateThresholdQty($ros, $supplierLeadTime)
                    ];
                }
            }
        }
        
        if(!empty($insertArr))
        {
            Batch::insert(new SalesVelocity, $this->columnList(), $insertArr, 500);
        }

        if(!empty($updateArr))
        {
            $chunkedUpdateDataArrArr = array_chunk($updateArr, config('constants.BATCH_UPDATE_LIMIT'));
            foreach ($chunkedUpdateDataArrArr as $chunkedUpdateDataArr) {
                Batch::update(new SalesVelocity, $chunkedUpdateDataArr, 'id');
            }
        }

        if(!empty($updateSupplierProducts))
        {
            $chunkedUpdateSupplierProducts = array_chunk($updateSupplierProducts, config('constants.BATCH_UPDATE_LIMIT'));
            foreach ($chunkedUpdateSupplierProducts as $chunkedUpdateSupplierProduct) {
                Batch::update(new SupplierProduct, $chunkedUpdateSupplierProduct, 'id');
            }
        }
        
        $this->productOffset = $this->productOffset + $this->productLimit;
        $this->calculateSalesVelocity($duration, $dates);
    }
    
}
