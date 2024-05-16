<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExcelExport implements FromArray, WithHeadings, WithColumnFormatting, WithMapping, ShouldAutoSize
{
    private $data;

    private $header;

    //Inject data 
    public function __construct($data, $header)
    {   
        $this->data   = $data;     
        $this->header = $header;     
    }

    public function array(): array
    {
        return $this->data;  
    }

    public function headings() :array
    {
    	return $this->header;
    }
    public function map($product): array
    {
        //dd($product);
        return $product;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            // 'M' => NumberFormat::FORMAT_NUMBER,
            'O' => NumberFormat::FORMAT_NUMBER,
            'Q' => NumberFormat::FORMAT_NUMBER,
            'V' => NumberFormat::FORMAT_NUMBER_00,
            'W' => NumberFormat::FORMAT_NUMBER_00,
            'X' => NumberFormat::FORMAT_NUMBER_00,
            'Y' => NumberFormat::FORMAT_NUMBER_00,
            'Z' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
