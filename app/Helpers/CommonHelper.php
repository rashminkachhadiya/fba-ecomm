<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Session;
use Illuminate\Support\Str;

class CommonHelper
{
    public static function getBuilderParameters()
    {
        $parameters = [
            "dom" => "<'table-responsive'tr>" .
                "<'row px-3 m-0 border-top border-gray-300'" .
                "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'li>" .
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" .
                ">",
            "stateSave" => true,
            "renderer" => 'bootstrap',
            'fixedHeader' => true,
            "processing" => true,
            "language" => [
                "processing" => "<script>$('body').addClass('page-loading1');</script>",
            ],
            "serverSide" => true,
            "lengthMenu" => [array_keys(config('constants.PER_PAGE')), array_keys(config('constants.PER_PAGE'))],
            "bAutoWidth" => true,
            "deferRender" => true,
            'pageLength' => intval(request()->per_page ?? config('constants.DEFAULT_PER_PAGE')),
            "order" => [],
            'searching' => false,
            "bLengthChange" => true, // Will Disabled Record number per page
            "bInfo" => true, //Will show "1 to n of n entries" Text at bottom
            "bPaginate" => true,
            "scrollX" => true,
            "columnDefs" => [],
            'drawCallback' => 'function() {
                this.api().state.clear();
                // magnificPopup();
                // popOverData();
                KTMenu.createInstances();

                if ($(this).find(".dataTables_empty").length == 1) {
                   $(this).find(".dataTables_empty").text("No results found");
                }
                hide_loader();
                $("body").removeClass("page-loading1");
            }',
        ];

        $stateSave = Session::get('stateSave');
        if (!empty($stateSave)) {

            $page = Session::get('perPage');
            $parameters['displayStart'] = $page;
        }

        return $parameters;
    }

    public static function getStoreConfig($slug = null)
    {
        if (!empty($slug)) {
            $amazon_str = 'Amazon';
            $amazon_us_str = $amazon_str . ' US';
            $amazon_uk_str = $amazon_str . ' UK';
            $amazon_ca_str = $amazon_str . ' CA';
            $big_commerce_str = 'BigCommerce';
            $walmart_str = 'Walmart';
            $usd_str = 'USD';
            $gbp_str = 'GBP';
            $cad_str = 'CAD';

            $configArr = [
                'store_marketplace' => [
                    $amazon_str,
                    $big_commerce_str,
                    $walmart_str,
                ],
                'store_type' => [
                    $amazon_us_str,
                    $amazon_uk_str,
                    $amazon_ca_str,
                    $big_commerce_str,
                    $walmart_str,
                ],
                'store_country' => [
                    'US',
                    'UK',
                    'CA'
                ],
                'store_currency' => [
                    $usd_str,
                    $gbp_str,
                    $cad_str
                ],
                'amazon_region' => [
                    'us-east-1',
                    'us-west-2',
                    'eu-west-1'
                ],
                'internal_order_status' => [
                    'InProcess',
                    'OnHold',
                    'ProblemOrder',
                    'BackOrder',
                    'Completed',
                    'Cancelled'
                ]
            ];

            if (isset($configArr[$slug])) {
                return $configArr[$slug];
            } else {
                switch ($slug) {
                    case 'STORE_MARKETPLACE_AMAZON':
                        return array_search($amazon_str, $configArr['store_marketplace']);
                        break;

                    case 'STORE_MARKETPLACE_BIGCOMMERCE':
                        return array_search($big_commerce_str, $configArr['store_marketplace']);
                        break;

                    case 'STORE_MARKETPLACE_WALMART':
                        return array_search($walmart_str, $configArr['store_marketplace']);
                        break;

                    case 'STORE_TYPE_AMAZON_US':
                        return array_search($amazon_us_str, $configArr['store_type']);
                        break;

                    case 'STORE_TYPE_AMAZON_UK':
                        return array_search($amazon_uk_str, $configArr['store_type']);
                        break;

                    case 'STORE_TYPE_AMAZON_CA':
                        return array_search($amazon_ca_str, $configArr['store_type']);
                        break;

                    case 'STORE_TYPE_BIGCOMMERCE':
                        return array_search($big_commerce_str, $configArr['store_type']);
                        break;

                    case 'STORE_TYPE_WALMART':
                        return array_search($walmart_str, $configArr['store_type']);
                        break;

                    case 'STORE_CURRENCY_USD':
                        return array_search($usd_str, $configArr['store_currency']);
                        break;

                    case 'STORE_CURRENCY_GBP':
                        return array_search($gbp_str, $configArr['store_currency']);
                        break;

                    case 'STORE_CURRENCY_CAD':
                        return array_search($cad_str, $configArr['store_currency']);
                        break;
                }
            }
        }
        return '';
    }

    public static function getInsertedDateTime()
    {
        return Carbon::now();
    }

    public static function getValue($variable, $keys = false, $default = null, $callable = false, $is_object = false)
    {
        if ($is_object) {
            // To build
        } else {
            if (is_array($keys)) {
                // Do nothing
            } else {
                $keys = explode('|', $keys);
            }

            $value = $variable;

            foreach ($keys as $key) {
                if (isset($value[$key])) {
                    if ($key == end($keys)) {
                        if ($callable && is_callable($callable)) {
                            return $callable($value[$key]);
                        } else {
                            return $value[$key];
                        }
                    } else {
                        $value = $value[$key];
                    }
                } else {
                    break;
                }
            }
        }

        return $default;
    }

    public static function extractFileContent($file_content, $report_id)
    {
        if (!Storage::exists('public/temp/')) {
            Storage::makeDirectory('public/temp/', 0777, true);
        }

        $report_folder = storage_path("app/public/temp/");

        $zipFile = $report_folder . $report_id . '.gz';

        $feedHandle = fopen($zipFile, 'w');

        fclose($feedHandle);

        $feedHandle = fopen($zipFile, 'rw+');

        fwrite($feedHandle, $file_content);

        $gz = gzopen($zipFile, 'rb');

        $file_name = $report_folder . $report_id . '.txt';

        $dest = fopen($file_name, 'wb');

        stream_copy_to_stream($gz, $dest);

        gzclose($gz);

        fclose($dest);

        $report_data = file_get_contents($file_name);

        if (Storage::disk('public')->exists('temp/' . $report_id . '.txt')) {
            Storage::disk('public')->delete('temp/' . $report_id . '.txt');
        }

        if (Storage::disk('public')->exists('temp/' . $report_id . '.gz')) {
            Storage::disk('public')->delete('temp/' . $report_id . '.gz');
        }
        return $report_data;
    }

    public static function getPOStatusList()
    {
        return [
            'Draft' => 'Draft',
            'Ready' => 'Ready',
            'Sent' => 'Sent',
            'Dispatch' => 'Dispatch',
            'Arrived' => 'Arrived',
            'Receiving' => 'Receiving',
            'Closed' => 'Closed',
            'Cancelled' => 'Cancelled',
        ];
    }

    public static function returnStatusNameById($status)
    {
        $statusName =  "-";

        if (!empty($status)) {
            switch ($status) {
                case 0:
                    $statusName = "WORKING";
                    break;
                case 1:
                    $statusName = "READY_TO_SHIP";
                    break;
                case 2:
                    $statusName = "SHIPPED";
                    break;
                case 3:
                    $statusName = "RECEIVING";
                    break;
                case 4:
                    $statusName = "CANCELLED";
                    break;
                case 5:
                    $statusName = "DELETED";
                    break;
                case 6:
                    $statusName = "CLOSED";
                    break;
                case 7:
                    $statusName = "ERROR";
                    break;
                case 8:
                    $statusName = "IN_TRANSIT";
                    break;
                case 9:
                    $statusName = "DELIVERED";
                    break;
                case 10:
                    $statusName = "CHECKED_IN";
                    break;
                default:
                    $statusName = "-";
            }
        }

        return $statusName;
    }

    /*
    @Description: Function to auto generated sku
    @Author     : Sanjay Chabhadiya used Mehul Modh's code
    @Input      : prefix and postfix
    @Output     : sku
    @Date       : 18-03-2021
     */
    public static function getSku($pre = "", $post = "")
    {

        $firstChar = Str::random(1);

        $uniqid = substr(uniqid(rand(), true), 2, 2);

        $uniqid = $firstChar . $uniqid;

        $uniqid = rtrim($uniqid, ".");

        $uniqid = strtoupper($pre . $uniqid . mt_rand() . $post);

        return strtoupper($uniqid);
    }

}