<?php

$orderStatusColor = [
    'PendingAvailability' => 'label-light-warning',
    'Pending' => 'label-light-warning',
    'Cancelled' => 'label-light-danger',
    'Shipped' => 'label-light-success',
    'Unshipped' => 'label-light-primary',
    'PartiallyShipped' => 'label-light-brand',
    'InvoiceUnconfirmed' => 'label-light-dark',
    'Unfulfillable' => 'label-light-info',
    'Shipping' => 'label-light-brand',
];

return [
    "PER_PAGE" => [10 => 10, 25 => 25, 50 => 50, 100 => 100],
    "DEFAULT_PER_PAGE" => 25,
    'statusArr' => [
        "1" => "Active",
        "0" => "In-Active"
    ],
    "module_name" => [
        'users' => 'users',
        'suppliers' => 'suppliers',
        'supplier_contacts' => 'supplier_contacts',
        'stores' => 'stores',
        'products' => 'products',
        'supplier_products' => 'supplier_products',
        'purchase_order' => 'purchase_order',
        'restocks' => 'restocks',
        'restock_products' => 'restock_products',
        'order_report' => 'order_report',
        'fba_products' => 'fba_products',
        'fba_plan' => 'fba_plan',
        'fba_shipment' => 'fba_shipment',
        'fba_prep_list' => 'fba_prep_list',
        'shopify_orders' => 'shopify_orders'
    ],
    "INSERT_DATE_FORMAT" => 'Y-m-d H:i:s',
    'CRON_STOP_MINUTE' => 4,
    "BATCH_UPDATE_LIMIT" => 50,
    'order_status_color' => $orderStatusColor,
    'order_status' => array_keys($orderStatusColor),
    'fulfillment_channel' => [
        'Amazon' => 'Amazon',
        'Merchant' => 'Merchant',
    ],
    "po_status" => [
        "Draft" => ['title' => 'Draft', 'action' => 'Mark as Draft', 'icon' => 'fa-solid fa-pen-to-square'],
        "Sent" => ['title' => 'Sent', 'action' => 'Mark as Sent', 'icon' => 'fa-solid fa-envelope-open'],
        "Shipped" => ['title' => 'Shipped', 'action' => 'Mark as Shipped', 'icon' => 'fa-solid fa-truck-fast'],
        "Arrived" => ['title' => 'Arrived', 'action' => 'Mark as Arrived', 'icon' => 'bi bi-truck'],
        "Receiving" => ['title' => 'Receiving', 'action' => 'Start Receiving', 'icon' => 'fa-solid fa-box'],
        "Partial Received" => ['title' => 'Partial Received', 'action' => 'Continue Receiving', 'icon' => 'fa-solid fa-clipboard-list'],
        "Received" => ['title' => 'Received', 'action' => 'Mark as Received', 'icon' => 'fa-solid fa-clipboard-check'],
        "Closed" => ['title' => 'Closed', 'action' => 'Closed', 'icon' => 'fa-solid fa-circle-xmark'],
        "Cancelled" => ['title' => 'Cancelled', 'action' => 'Cancelled', 'icon' => 'fa-solid fa-xmark'],
    ],
    "redirect_amazon_product_url" => [
        1 => 'https://www.amazon.ca/dp/',
        2 => 'https://www.amazon.com/dp/',
    ],
    "discrepancy_reason" => [
        "Damaged" => ['title' => "Damaged"],
        "Missing" => ["title" => "Missing"],
        "Other" => ["title" => "Other"],
    ],
    "box_content" => [
        '2D_BARCODE' => '2D_BARCODE',
    ],
    "prep_preference" => [
        "SELLER_LABEL" => 'SELLER_LABEL',
    ],
    "packing_details" => [
        "Individual Pack" => 'Individual Pack',
        "Case Pack" => 'Case Pack',
    ],
    "plan_status" => [
        "Finalized" => 'Finalized',
        "Draft" => 'Draft',
    ],
    // "fba_shipment_status"=>[
    //     "NONE" => "NONE",
    //     "WORKING" => "WORKING",
    //     "SHIPPED" => "SHIPPED",
    //     "IN_TRANSIT" => "IN_TRANSIT",
    //     "DELIVERED" => "DELIVERED",
    //     "CHECKED_IN" => "CHECKED_IN",
    //     "RECEIVING" => "RECEIVING",
    //     "CLOSED" => "CLOSED",
    //     "CANCELLED" => "CANCELLED",
    //     "DELETED" => "DELETED",
    //     "ERROR" => "ERROR",
    // ],

    "currency_symbol"=> "$",

    // FBA shipments
    'draft_tab_status' => [
        'Pending Approval',
        'Shipment Deleted',
        'Shipment ID Expired',
        'All'
    ],
    'label_prep_type' => [// https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#labelpreptype
        'NO_LABEL',
        'SELLER_LABEL',
        'AMAZON_LABEL'
    ],
    'box_content_source' => [       // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#boxcontentssource
        'NONE',
        'FEED',
        '2D_BARCODE',
        'INTERACTIVE'
    ],
    'prep_status' => [
        '0' => 'Prep Pending',
        '1' => 'Prep In Progress',
        '2' => 'Prep Completed',
    ],
    
    "fba_shipment_status"=>[
        'WORKING',
        'READY_TO_SHIP',
        'SHIPPED',
        'RECEIVING',
        'CANCELLED',
        'DELETED',
        'CLOSED',
        'ERROR',
        'IN_TRANSIT',
        'DELIVERED',
        'CHECKED_IN'
    ],

    "fba_shipment_status_filter"=>[
        '0' => 'WORKING',
        '1' => 'READY TO SHIP',
        '2' => 'SHIPPED',
        '3' => 'RECEIVING',
        '6' => 'CLOSED',
        '8' => 'IN TRANSIT',
        '9' => 'DELIVERED',
        '10' => 'CHECKED IN'
    ],
    'SHIPMENT_ASIN_QTY_PERCENT' => 5,
    'SHIPMENT_ASIN_QTY_MAX' => 6,

    'fba_shipment_transport_carrier' => [
        'DHL_EXPRESS_USA_INC' => 'DHL_EXPRESS_USA_INC',
        'FEDERAL_EXPRESS_CORP' => 'FEDERAL_EXPRESS_CORP',
        'UNITED_STATES_POSTAL_SERVICE' => 'UNITED_STATES_POSTAL_SERVICE',
        'UNITED_PARCEL_SERVICE_INC' => 'UNITED_PARCEL_SERVICE_INC',
        'OTHER' => 'OTHER'
    ],

    'shopify_order_status' => [
        'shipped' => 'SHIPPED',
        'partial' => 'PARTIALLY SHIPPED',
        'unshipped' => 'UNSHIPPED',
        'fulfilled' => 'FULFILLED',
        'restocked' => 'RESTOCKED',
    ],
    
    'ORDER_FETCHING_LAST_HOURS' => 24,

];
