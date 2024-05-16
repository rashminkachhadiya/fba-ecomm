<?php 

return [
    'ShipmentStatusList' => [ // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#shipmentstatuslist
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
    'BoxContentsSource' => [  // https://developer-docs.amazon.com/sp-api/docs/fulfillment-inbound-api-v0-reference#boxcontentssource
        'NONE',
        'FEED',
        '2D_BARCODE',
        'INTERACTIVE'
    ],
];