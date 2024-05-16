<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"><head id="j_idt8"><script type="text/javascript">if(window.PrimeFaces){PrimeFaces.settings.locale='en_US';PrimeFaces.settings.validateEmptyFields=true;PrimeFaces.settings.considerEmptyStringNull=false;}</script>
<style type="text/css">
@font-face {
	font-family: 'Monaco'; font-style: normal; font-weight: normal; src: local('Monaco'), url('../css/Monaco.woff') format('woff');
}
html {margin: 0; padding: 0;} body {margin: 0; padding: 0; width: 4in; height: 6in; font-family: sans-serif; color-adjust: exact; print-color-adjust: exact; -webkit-print-color-adjust: exact !important;} @page {margin: 0; size: 4in 6in}

.rotateimg180 {
	transform-origin: bottom center;
	transform: rotate(270deg);
	float: inherit;
	position: absolute;
}
.bottomright {
	position: fixed;
    bottom: 15px;
    right: 16px;
    left: 28%;
}
</style>
<title>Box Label</title>
</head>
<body>
@if(isset($htmlData) && !empty($htmlData))
    @foreach($htmlData as $data)
		<div style="page-break-after: always; padding-top: 3mm; padding-bottom: 0mm; padding-left: 3mm; padding-right: 3mm;">
			<div style="height: 270px">
				<div style="text-align: center; font-size: smaller; font-weight: bold">PLEASE LEAVE THIS LABEL UNCOVERED</div>
				<div style="font-size: larger; font-weight: bold">FBA</div>
				<div style="height: 3px; width: 100%; background: #000;"></div>
				<div style="font-size: 0">
					<div style="font-size: 10px; width: 49%; display: inline-block; float:left; clear:both">
						<div>SHIP FROM:</div>
						<div>{{ isset($data['ship_from_address']['ship_from_addr_name'])?$data['ship_from_address']['ship_from_addr_name'] : '' }}</div>
						<div>
							{{ isset($data['ship_from_address']['AddressLine1'])?$data['ship_from_address']['AddressLine1'] : '' }}

							@if(isset($data['ship_from_address']['AddressLine2']) && !empty($data['ship_from_address']['AddressLine2']))
								<p>{{ $data['ship_from_address']['AddressLine2'] }}</p>
							@endif
						</div>
						<div>
							{{ isset($data['ship_from_address']['City'])?$data['ship_from_address']['City'].',':'' }} 
							{{ isset($data['ship_from_address']['StateOrProvinceCode'])?$data['ship_from_address']['StateOrProvinceCode'].',':''}}
							{{ isset($data['ship_from_address']['PostalCode'])?$data['ship_from_address']['PostalCode']:''}}
						</div>
						<div>{{ isset($data['ship_from_address']['CountryCode'])?$data['ship_from_address']['CountryCode']:''}}</div>
						<div>&nbsp;</div>
					</div>
					<div style="font-size: 10px; width: 51%; display: inline-block;">
						<div>SHIP TO:</div>
						<div>{{ isset($data['ship_from_address']['ship_from_addr_name'])? 'FBA: '.$data['ship_from_address']['ship_from_addr_name'] : '' }}</div>
						<div>{{ isset($data['ship_to_address']['ship_to_addr_name'])?$data['ship_to_address']['ship_to_addr_name'] : '' }}</div>
						<div>
							{{ isset($data['ship_to_address']['AddressLine1'])?$data['ship_to_address']['AddressLine1'] : '' }}
							@if(isset($data['ship_to_address']['AddressLine2']) && !empty($data['ship_to_address']['AddressLine2']))
									<p>{{ $data['ship_to_address']['AddressLine2'] }}</p>
							@endif
						</div>
						<div>
							{{ isset($data['ship_to_address']['City'])?$data['ship_to_address']['City'].',' : ''}}
							{{ isset($data['ship_to_address']['StateOrProvinceCode'])?$data['ship_to_address']['StateOrProvinceCode'].',' : ''}}
							{{ isset($data['ship_to_address']['PostalCode'])?$data['ship_to_address']['PostalCode'] : ''}}
						</div>
						<div>{{ isset($data['ship_to_address']['CountryCode'])?$data['ship_to_address']['CountryCode']:'' }}</div>
					</div>
				</div>
				<div style="height: 12px; width: 100%; background: #000; white-space: nowrap; overflow: hidden;">
					<div style="font-size: 9px; color: #FFFFFF; padding-top: 1px">
						<span style="min-width: 53%; display: inline-block;">{{ $data['shipmentData']['bcode'] }}</span>
						<span>Created: {{ date('Y/m/d H:i:s', strtotime($data['shipmentData']['created_at'])) }} EST (-05)</span>
					</div>
				</div>

				<div style="padding-top: 5px; height: 100px; text-align: left">
					<div style="width: 70%; float: left;text-align: center;">
						<span style="padding-left: 7px; padding-right: 7px;">
					@php	asset('storage/uploads/barcode/pallet_label/'.$data['shipment_label_image']) @endphp
                    <img src="{{asset('storage/uploads/barcode/pallet_label/'.$data['shipment_label_image'])}}" alt="">
						</span>
						<span style="font-size: 11px; font-family: monaco; display: block;">{{ $data['shipment_label_labelstring']; }}</span>	
					</div>
					<div style="width: 22mm;float: right;padding-right: 8px;height: 45mm;">
						<span class="rotateimg180">
						@php public_path('storage/uploads/barcode/pallet_label/'.$data['shipment_twodlabel_image']) @endphp
                        <img src="{{asset('storage/uploads/barcode/pallet_label/'.$data['shipment_twodlabel_image'])}}" alt="">
						</span>
					</div>
				</div>
				<div style="height: 3px; width: 75%; background: #000"></div>
			</div>
		</div>
	@endforeach
@endif
<input id="printer_name" type="hidden" name="printer_name" />
<script type="text/javascript">
var printerName = '';
window.print();
</script></body>
</html>