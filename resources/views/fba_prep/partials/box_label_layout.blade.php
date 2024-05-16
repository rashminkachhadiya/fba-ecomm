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
</style>
<title>Box Label</title>
</head>
<body>
@if(isset($htmlData) && !empty($htmlData))
    @foreach($htmlData as $data)
		<div style="page-break-after: always; padding-top: 3mm; padding-bottom: 0mm; padding-left: 3mm; padding-right: 3mm;">
			<div style="height: 270px">
				@if(isset($data['is_printed_type']) && $data['is_printed_type'] == 2)
					<div style="padding: 0; margin: 0 auto; width: 3.0in">
						<div style="padding-top: 0mm; padding-bottom: 1mm; padding-left: 1mm; padding-right: 1mm">
							<?=html_entity_decode(file_get_contents(storage_path('app/public/uploads/barcode/3in1/'.$data['product_barcode_image'])))?>
							<div style="font-size: 10px; text-align: center; padding-top: 1.00mm; font-family: Monaco">{{ $data['shipmentData']['fnsku'] }}
							</div>
							<div style="font-size: 10px; padding-left: 2mm; line-height: 0.90; font-family: Monaco">
								<div>{!! wordwrap(substr($data['shipmentData']['product_title'],0,80), 35, "\n") !!}
									<div style="font-weight: bold; padding-top: 1mm;">
			                            @if(!empty($data['shipmentData']['product_condition']))
			                                <span style="font-size:10px; font-family:Monaco; white-space: pre; word-wrap:normal;">{{ strtoupper($data['shipmentData']['product_condition']) }}</span>
			                            @endif

			                            @if(!empty($data['shipmentData']['expiry_date']))
			                                <span style="font-size:10px; font-family:Monaco; white-space: pre; word-wrap:normal; float: right;">Expires: {{ date('m-d-Y',strtotime($data['shipmentData']['expiry_date'])) }}</span>
			                            @endif
			                        </div>
								</div>
							</div>
						</div>
					</div>
					<div style="height: 3px; width: 100%; background: #000;"></div>
				@endif
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

				<div style="padding-top: 5px; height: 114px; text-align: left">
					<div style="width: 70%; float: left;text-align: center;">
						<span style="padding-left: 7px; padding-right: 7px;">
						<?=html_entity_decode(file_get_contents(storage_path('app/public/uploads/barcode/'.$data['shipment_label_image'])))?>
						</span>
						<span style="font-size: 11px; font-family: monaco; display: block;">{{ $data['shipment_label_labelstring']; }}</span>	
					</div>
					<div style="width: 22mm;float: right;padding-right: 8px;height: 45mm;">
						<span class="rotateimg180">
						<?=html_entity_decode(file_get_contents(storage_path('app/public/uploads/barcode/'.$data['shipment_twodlabel_image'])))?>
						</span>
					</div>
				</div>
				<div style="width: 74%">
					<!-- circle -->
					<!-- <div style="width: 60%; text-align: center; font-size: 20px;">BOX {{$data['shipmentData']['box_id']}}</div>
					@if(isset($data['is_printed_type']) && $data['is_printed_type'] == 2)
						<div style="width: 40%; float: right; margin-top: -45px;">
							<div style=" width: 40px; line-height: 40px; text-align: center; font-size: 20px; border: 2px solid black; margin-left: 50px;border-radius: 50%;">
								<b>{{ 101 }}</b>
							</div>
						</div>
					@endif -->
					<!-- square -->
					<div style="width: 100%; text-align: center; font-size: 20px;">BOX {{$data['shipmentData']['box_id']}}</div>

					@if(!empty($data['shipmentData']['truck_name']) && isset($data['is_printed_type']) && $data['is_printed_type'] == 2)
						<div style="width: 100%; float: right;">
							<div style=" width: 40px; line-height: 40px; text-align: center; font-size: 20px; border: 3px solid black; margin-left: 110px; border-radius: 50%;">
								<b>{{ $data['shipmentData']['truck_name'] }}</b>
							</div>
						</div>
					@endif
				</div>
				
				<div style="height: 3px; width: 72%; background: #000"></div>
			</div>
			<div style="padding-top: 5px; height: 240px; text-align: center">
				<?=html_entity_decode(file_get_contents(storage_path('app/public/uploads/barcode/2d/'.$data['shipment_boxlabel_image'])))?>
				<h3 style="margin-top: 5px; margin-bottom: 5px; line-height: 1">
					<small>Dest:</small>{{ $data['shipmentData']['destination_center_id'] }}
					<small>Shipment:</small>{{ $data['shipmentData']['amazon_shipment_id'] }}
					@if(isset($data['is_printed_type']) && $data['is_printed_type'] != 2)
						<small>Units:</small>{{ $data['shipmentData']['qty'] }}
					@endif
				</h3>
				@if(isset($data['is_printed_type']) && $data['is_printed_type'] != 2)
					<strong>{{ $data['shipmentData']['fnsku'] }}</strong>: {{$data['shipmentData']['qty']}}
				@endif
			</div>
			@if(!empty($data['shipmentData']['truck_name']) && isset($data['is_printed_type']) && $data['is_printed_type'] != 2)
				<div style=" padding-right: 7px; text-align: right;">
					<div style="float: right; width: 40px; line-height: 40px; border-radius: 50%; text-align: center; font-size: 20px; border: 2px solid black;">
						<b>{{ $data['shipmentData']['truck_name'] }}</b>
					</div>
				</div>
			@endif
		</div>
	@endforeach
@endif
<input id="printer_name" type="hidden" name="printer_name" />
<script type="text/javascript">
var printerName = '';
window.print();
</script></body>
</html>