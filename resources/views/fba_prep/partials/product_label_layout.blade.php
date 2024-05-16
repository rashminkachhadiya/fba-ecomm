<html>
<head>
    <script type="text/javascript">if(window.PrimeFaces){PrimeFaces.settings.locale='en_US';PrimeFaces.settings.validateEmptyFields=true;PrimeFaces.settings.considerEmptyStringNull=false;}</script>
    <style type="text/css">
        @font-face {
            font-family: 'Monaco'; font-style: normal; font-weight: normal; src: local('Monaco'), url('../css/Monaco.woff') format('woff');
        }
        html {
            margin: 0; padding: 0;
        }
        body {
            margin: 0; padding: 0; width: 2.25in; height: 1.25in;
        } 
        @page {
            margin: 0; size: 2.25in 1.25in
        }
    </style>
</head>
<body>
    @if(isset($html) && !empty($html))
        @foreach($html as $data)
            <div style="padding-top: 3mm; padding-bottom: 0mm; padding-left: 1mm; padding-right: 1mm; page-break-before: always;">
                <div style="padding-left: 2mm; padding-right: 3mm;">
                    {{-- {!! \Storage::disk('public')->url('uploads/app/public/barcode/3in1/'.$data['barcodeImage']) !!} --}}
                    <?=html_entity_decode(file_get_contents(storage_path('app/public/uploads/barcode/3in1/'.$data['barcodeImage'])))?>
                </div>
                <div style="font-size: 10px; text-align: center; padding-top: 2.00mm; font-family: Monaco">
                    {{ $data['fnsku'] }}
                </div>
                <div style="font-size: 10px; padding-left: 2mm; padding-right: 3mm; line-height: 0.90; font-family: Monaco">
                    <div>
                        {!! wordwrap(substr($data['title_data'],0,120), 35, "\n") !!}
                        
                        <div style="font-weight: bold; padding-top: 1mm;">
                            @if(!empty($data['product_condition']))
                                <span style="font-size:10px; font-family:Monaco; white-space: pre; word-wrap:normal;">{{ strtoupper($data['product_condition']) }}</span>
                            @endif

                            @if(!empty($data['expire_date']))
                                <span style="font-size:10px; font-family:Monaco; white-space: pre; word-wrap:normal; float: right;">Expires: {{ date('m-d-Y',strtotime($data['expire_date'])) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</body>
</html>