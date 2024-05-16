<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\Setting;
use App\Models\Supplier;

class POExport implements FromArray, WithEvents
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return \Illuminate\Support\FromArray
     */
    public function array(): array
    {
        $poDetails = PurchaseOrder::find($this->id);
        $supplier_id = $poDetails->supplier_id;
        $supplier_name = Supplier::where('id', $supplier_id)->select('name')->withTrashed()->pluck('name')->first();
        $shipping_address = Setting::select('shipping_address')->first();

        $details = [
            ['Order Date: ' . $poDetails->po_order_date],
            ['PO Name: ' . $poDetails->po_number],
            ['Shipping Address: '. $shipping_address->shipping_address],
            ['Supplier Name: ' . $supplier_name],
            ['']
        ];

        $tableData[] = [
            [
                "TITLE",
                "SKU",
                "ASIN",
                "SUPPLIER SKU",
                "UNIT PRICE",
                "ORDER QTY",
                "TOTAL PRICE",

            ],
        ];

        $totalPriceSum = 0;

        $poItems = PurchaseOrder::getPurchaseOrderIteamsForExport($this->id);
        foreach ($poItems as $item) {
            $tableData[] = [
                $item->title,
                $item->sku,
                $item->asin,
                $item->supplier_sku,
                $item->unit_price,
                $item->order_qty,
                $item->total_price,
            ];

            $totalPriceSum += $item->total_price;
        }

        $sumOFTotalPrice[] = [
            [
                " ",
                " ",
                " ",
                " ",
                " ",
                "TOTAL" ,$totalPriceSum,
            ],
        ];

        $data = array_merge($details, $tableData, $sumOFTotalPrice);
        return $data;
    }

    public function setEmptyRow(): array
    {
        return ['A5'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A6:Z6')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                    ],
                ]);

                $col1 = ['A'];
                $w1 = 25;
                foreach ($col1 as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setWidth($w1);
                }

                $col2 = ['B', 'C', 'D'];
                $w2 = 17;
                foreach ($col2 as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setWidth($w2);
                }
                $col3 = ['E', 'F', 'G'];
                $w3 = 12;
                foreach ($col3 as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setWidth($w3);
                }

            },
        ];
    }
}
