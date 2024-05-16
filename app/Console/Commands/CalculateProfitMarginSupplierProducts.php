<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Services\CronCommonService;
use App\Traits\CalculateProfitMarginTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Batch;

class CalculateProfitMarginSupplierProducts extends Command
{
    use CalculateProfitMarginTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-profit-margin-supplier-products {supplier_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate profit margin for each suppliers products';

    protected CronCommonService $cronService;

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
        try {
            $supplierId = $this->argument('supplier_id');

            if(empty($supplierId))
            {
                $suppliers = Supplier::orderBy('id','desc')->pluck('id');
                foreach ($suppliers as $supplier) {
                    Artisan::call('app:calculate-profit-margin-supplier-products', ['supplier_id' => $supplier]);
                }
            }else{
                // Get products of particular supplier
                $supplierProducts = $this->getSupplierProducts($supplierId);

                if(empty($supplierProducts)) return;

                // Set cron name & cron type
                $this->cronService->cronName = 'CRON_'.time().'_'.$supplierId;
                $this->cronService->cronType = 'CALCULATE_PROFIT_MARGIN_SUPPLIER_PRODUCTS';
                
                // Log cron start
                $cronLog = $this->cronService->storeCronLogs();
                
                if(!$cronLog) return;

                // Calculate proft and margin of each products of particular supplier
                $this->calculateProfitMargin($supplierProducts);

                // Log cron end
                $this->cronService->updateCronLogs();
            }
        } catch (\Exception $e) {
            $this->cronService->storeId = 0;
            $this->storeAmazonCronErrorLog($e->getMessage());
        }
    }

    /**
     * Calculate profit and margin of each products of particular supplier
     * @param Collection $supplierProducts
     */
    private function calculateProfitMargin(Collection $supplierProducts)
    {
        foreach ($supplierProducts as $supplierProduct)
        {
            $calculatedSellingPriceProfit = $supplierProduct->amazonProduct->price - ($supplierProduct->unit_price + ($supplierProduct->amazonProduct->referral_fees + $supplierProduct->amazonProduct->fba_fees));
            // $calculatedSellingPriceMargin = ($calculatedSellingPriceProfit > 0 && $supplierProduct->amazonProduct->price > 0) ? ($calculatedSellingPriceProfit / $supplierProduct->amazonProduct->price) * 100 : 0;
            $calculatedSellingPriceMargin = ($supplierProduct->amazonProduct->price > 0) ? ($calculatedSellingPriceProfit / $supplierProduct->amazonProduct->price) * 100 : 0;
            
            $calculatedBuyboxPriceProfit = $supplierProduct->amazonProduct->buybox_price - ($supplierProduct->unit_price + ($supplierProduct->amazonProduct->buybox_referral_fees + $supplierProduct->amazonProduct->fba_fees));
            // $calculatedBuyboxPriceMargin = ($calculatedBuyboxPriceProfit > 0 && $supplierProduct->amazonProduct->buybox_price > 0) ? ($calculatedBuyboxPriceProfit / $supplierProduct->amazonProduct->buybox_price) * 100 : 0;
            $calculatedBuyboxPriceMargin = ($supplierProduct->amazonProduct->buybox_price > 0) ? ($calculatedBuyboxPriceProfit / $supplierProduct->amazonProduct->buybox_price) * 100 : 0;

            $this->updateProfitMarginData[] = [
                'id' => $supplierProduct->id,
                'buybox_price' => $supplierProduct->amazonProduct->buybox_price,
                'selling_price' => $supplierProduct->amazonProduct->price,
                'referral_fees' => $supplierProduct->amazonProduct->referral_fees,
                'buybox_referral_fees' => $supplierProduct->amazonProduct->buybox_referral_fees,
                'fba_fees' => $supplierProduct->amazonProduct->fba_fees,
                'buybox_price_profit' => $calculatedBuyboxPriceProfit,
                'buybox_price_margin' => round($calculatedBuyboxPriceMargin,2),
                'selling_price_profit' => $calculatedSellingPriceProfit,
                'selling_price_margin' => round($calculatedSellingPriceMargin,2),
            ];
            
        }
        
        if(!empty($this->updateProfitMarginData))
        {
            $chunkedUpdateDataArrArr = array_chunk($this->updateProfitMarginData, config('constants.BATCH_UPDATE_LIMIT'));
            foreach ($chunkedUpdateDataArrArr as $chunkedUpdateDataArr) {
                Batch::update(new SupplierProduct, $chunkedUpdateDataArr, 'id');
            }
        }
    }
}
