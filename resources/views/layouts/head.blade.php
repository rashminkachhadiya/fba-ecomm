<head>
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex">
    <link rel="shortcut icon" href="{{ asset('media/Stanbi_FavIcon.svg') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('media/Stanbi_FavIcon.svg') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="{{ asset('plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet" type="text/css" />
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" /> --}}
    
    <style>
        tr td.dtfc-fixed-left:first-child{
            box-shadow:none;
        }
        tr .dtfc-fixed-left{
            -webkit-box-shadow: 4px 0px 4px rgba(0, 0, 0, 0.1);
            -moz-box-shadow: 4px 0px 4px rgba(0, 0, 0, 0.1);
            box-shadow: 4px 0px 4px rgba(0, 0, 0, 0.1);
        }
        
        .fw-100{font-weight:100 !important;}
        .fw-200{font-weight:200 !important;}
        .fw-300{font-weight:300 !important;}
        .fw-400{font-weight:400 !important;}
        .fw-500{font-weight:500 !important;}
        .fw-500{font-weight:500 !important;}
        .fw-700{font-weight:700 !important;}
        .fw-800{font-weight:800 !important;}
        .fw-900{font-weight:900 !important;}
    
    </style>
</head>