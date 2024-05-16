@extends('layouts.app')
@section('title', 'FBA Prep Productivity')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="javascript:void(0)">{{ __('Prep Productivity Listing') }}</a></li>
@endsection

@section('content')
<style>
    #chartdiv {
  width: 100%;
  height: 500px;
}
</style>

<main class="py-0">
    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="post d-flex flex-column-fluid" id="kt_post">
            <div id="kt_content_container" class="container-fluid px-0">
                <div class="container-fluid">
                    <div class="card ">
                        <div class="card-body px-0">
                            <div class="row">
                                <div class="col-lg-2 col-md-4 my-lg-0 my-3 col-6">
                                    <div class="border rounded">
                                        <div class="d-flex p-3 gap-3 justify-content-between ">
                                            <div>
                                                <p class="fw-500 mb-2">Units Prepped</p>
                                                <h4 class="d-block text-primary mb-0 fw-700 todayUnit">{{ isset($today['units_prepped']) && !empty($today['units_prepped']) ? $today['units_prepped'] : 0}}</span></h4>
                                            </div>
                                            <div>
                                                <p class="fw-500 mb-2">SKU</p>
                                                <h4 class="d-block text-primary mb-0 fw-700 todaySKU">{{ isset($today['units_prepped']) && !empty($today['sku_counts']) ? $today['sku_counts'] : 0}}</span></h4>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center bg-light py-2 mt-3 bg-primary bg-opacity-25">
                                            <strong class="fw-bold">Today</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 my-lg-0 my-3 col-6">
                                    <div class="border rounded">
                                        <div class="d-flex p-3 gap-3 justify-content-between ">
                                            <div>
                                                <p class="fw-500 mb-2">Units Prepped</p>
                                                <h4 class="d-block text-info mb-0 fw-700 yesterdayUnit">{{ isset($yesterday['units_prepped']) && !empty($yesterday['units_prepped']) ? $yesterday['units_prepped'] : 0}}</span></h4>
                                            </div>
                                            <div>
                                                <p class="fw-500 mb-2">SKU</p>
                                                <h4 class="d-block text-info mb-0 fw-700 yesterdaySKU">{{ isset($yesterday['units_prepped']) && !empty($yesterday['sku_counts']) ? $yesterday['sku_counts'] : 0}}</span></h4>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center py-2 mt-3 bg-info bg-opacity-25">
                                            <strong class="fw-bold ">YESTERDAY</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 my-lg-0 my-3 col-6">
                                    <div class="border rounded">
                                        <div class="d-flex p-3 gap-3 justify-content-between ">
                                            <div>
                                                <p class="fw-500 mb-2">Units Prepped</p>
                                                <h4 class="d-block text-primary mb-0 fw-700 currentWeekUnit">{{ isset($currentWeek['units_prepped']) && !empty($currentWeek['units_prepped']) ? $currentWeek['units_prepped'] : 0}}</span></h4>
                                            </div>
                                            <div>
                                                <p class="fw-500 mb-2">SKU</p>
                                                <h4 class="d-block text-primary mb-0 fw-700 currentWeekSKU">{{ isset($currentWeek['units_prepped']) && !empty($currentWeek['sku_counts']) ? $currentWeek['sku_counts'] : 0}}</span></h4>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center bg-light py-2 mt-3 bg-primary bg-opacity-25">
                                            <strong class="fw-bold">THIS WEEK</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 my-lg-0 my-3 col-6">
                                    <div class="border rounded">
                                        <div class="d-flex p-3 gap-3 justify-content-between ">
                                            <div>
                                                <p class="fw-500 mb-2">Units Prepped</p>
                                                <h4 class="d-block text-info mb-0 fw-700 lastWeekUnit">{{ isset($lastWeek['units_prepped']) && !empty($lastWeek['units_prepped']) ? $lastWeek['units_prepped'] : 0}}</span></h4>
                                            </div>
                                            <div>
                                                <p class="fw-500 mb-2">SKU</p>
                                                <h4 class="d-block text-info mb-0 fw-700 lastWeekSKU">{{ isset($lastWeek['units_prepped']) && !empty($lastWeek['sku_counts']) ? $lastWeek['sku_counts'] : 0}}</span></h4>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center py-2 mt-3 bg-info bg-opacity-25">
                                            <strong class="fw-bold ">LAST WEEK</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 my-lg-0 my-3 col-6">
                                    <div class="border rounded">
                                        <div class="d-flex p-3 gap-3 justify-content-between ">
                                            <div>
                                                <p class="fw-500 mb-2">Units Prepped</p>
                                                <h4 class="d-block text-primary mb-0 fw-700 currentMonthUnit">{{ isset($currentMonth['units_prepped']) && !empty($currentMonth['units_prepped']) ? $currentMonth['units_prepped'] : 0}}</span></h4>
                                            </div>
                                            <div>
                                                <p class="fw-500 mb-2">SKU</p>
                                                <h4 class="d-block text-primary mb-0 fw-700 currentMonthSKU">{{ isset($currentMonth['units_prepped']) && !empty($currentMonth['sku_counts']) ? $currentMonth['sku_counts'] : 0}}</span></h4>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center bg-light py-2 mt-3 bg-primary bg-opacity-25">
                                            <strong class="fw-bold">THIS MONTH</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 my-lg-0 my-3 col-6">
                                    <div class="border rounded">
                                        <div class="d-flex p-3 gap-3 justify-content-between ">
                                            <div>
                                                <p class="fw-500 mb-2">Units Prepped</p>
                                                <h4 class="d-block text-info mb-0 fw-700 lastMonthUnit">{{ isset($lastMonth['units_prepped']) && !empty($lastMonth['units_prepped']) ? $lastMonth['units_prepped'] : 0}}</span></h4>
                                            </div>
                                            <div>
                                                <p class="fw-500 mb-2">SKU</p>
                                                <h4 class="d-block text-info mb-0 fw-700 lastMonthSKU">{{ isset($lastMonth['units_prepped']) && !empty($lastMonth['sku_counts']) ? $lastMonth['sku_counts'] : 0}}</span></h4>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center py-2 mt-3 bg-info bg-opacity-25">
                                            <strong class="fw-bold ">LAST MONTH</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-8">
                                <div class="col-12">
                                    <div class="border rounded p-4">
                                        <form class="form d-flex align-items-center mb-4">
                                            <div class="d-flex">
                                                <div class="form-group me-3">
                                                    <input type="radio" name="view_type" value="day" checked>
                                                    <label for="dayRadio"> Day</label>
                                                </div>
                                                <div class="form-group me-3">
                                                    <input type="radio" name="view_type" value="week">
                                                    <label for="monthRadio ">Week</label>
                                                </div>
                                                <div class="form-group me-3">
                                                    <input type="radio" name="view_type" value="month">
                                                    <label for="yearRadio">Month</label>
                                                </div>
                                            </div>
                                            <div class="d-flex ms-auto gap-4 align-items-center">
                                                <input type="text" class="form-control form-control-sm filter_daterange kt_daterangepicker_5" name="filter_daterange" placeholder="Pick  date range" id="kt_daterangepicker_5" value=""/>
                                                @error('filter_daterange')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                                <input type="hidden" name="" id="daterange" value="">
                                                <input type="hidden" name="" id="dateRangeType" value="Last 7 Days">
                                                <input type="hidden" name="" id="rowasin" value="">
                                                <select name="users" id="users" class="form-select form-select-sm" onchange="loadPrepGraph();getPreppedUnitsByUser(this.value)">
                                                    <option value="All">All Users</option>
                                                    @if(isset($users) && !empty($users))
                                                        @foreach($users as $key => $user)
                                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                
                                            </div>
                                        </form>
                                        <h2 class="fw-700 mb-6">Units Prepped</h2>
                                        <div  id="chartdivdata" class="chartdivdata"></div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection

@section('page-script')

{{-- <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script> --}}

<script src="https://www.amcharts.com/lib/4/core.js"></script>
<script src="https://www.amcharts.com/lib/4/charts.js"></script>
<script src="https://www.amcharts.com/lib/4/themes/animated.js"></script>
<script src="https://www.amcharts.com/lib/4/themes/dataviz.js"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            var start = moment().subtract(6, 'days');
            var end = moment();
            function cb(start, end) {
                $(".kt_daterangepicker_5").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"));
                $("#daterange").val($("#kt_daterangepicker_5").text());
            }
            $('.kt_daterangepicker_5').daterangepicker({
                startDate: start,
                endDate: end,
                maxDate: moment(),
                ranges: {
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'This Year': [moment().startOf('year')]
                }
            }, cb);

            $(".kt_daterangepicker_5").on("apply.daterangepicker", function (ev, picker) {
                $(this).val(
                    picker.startDate.format("MM/DD/YYYY")+
                        " - " +
                    picker.endDate.format("MM/DD/YYYY")
                );
                loadPrepGraph();
            });

            cb(start, end);

            
            $(".ranges ul li").on('click',function(){
                $("#dateRangeType").val($(this).text());
            })
            loadPrepGraph();
        });

        function loadPrepGraph(){
            var daterange = $("#kt_daterangepicker_5").text();
            var user_id = $("#users").val();
            var view_type = $("input[name=view_type]:checked").val();
           
            var myUrl = '/fba-shipment/prep-dashboard-detail?date_range_filter='+(window.btoa(daterange))+'&user_id='+(window.btoa(user_id))+'&view_type='+(window.btoa("day"));
           
            $("a.dashDetail").attr("href", myUrl);
            
            $.ajax({
                url: "{{ url('get-prep-graph')}}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    daterange : daterange,
                    user_id : user_id,
                    view_type: view_type
                },
                beforeSend: function () {
                    // show_loader();
                },
                success: function(data) {
                    $('#chartdivdata').html("");
                    $('#chartdivdata').append(data);

                    hide_loader();
                },
            });
        }

        function getPreppedUnitsByUser(userId){
            $.ajax({
                url: "{{ url('fba-shipment/get-prep-units-by-user')}}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    user_id : userId,
                },
                beforeSend: function () {
                    show_loader();
                },
                success: function(data) {
                    $(".todayUnit").text("0");
                    $(".todaySKU").text("0");

                    $(".yesterdayUnit").text("0");
                    $(".yesterdaySKU").text("0");

                    $(".currentWeekUnit").text("0");
                    $(".currentWeekSKU").text("0");

                    $(".lastWeekUnit").text("0");
                    $(".lastWeekSKU").text("0");

                    $(".currentMonthUnit").text("0");
                    $(".currentMonthSKU").text("0");

                    $(".lastMonthUnit").text("0");
                    $(".lastMonthSKU").text("0");

                    if(data.today.units_prepped != "" && data.today.sku_counts != ""){
                        $(".todayUnit").text(data.today.units_prepped);
                        $(".todaySKU").text(data.today.sku_counts);
                    }

                    if(data.yesterday.units_prepped != "" && data.yesterday.sku_counts != ""){
                        $(".yesterdayUnit").text(data.yesterday.units_prepped);
                        $(".yesterdaySKU").text(data.yesterday.sku_counts);
                    }

                    if(data.currentWeek.units_prepped != "" && data.currentWeek.sku_counts != ""){
                        $(".currentWeekUnit").text(data.currentWeek.units_prepped);
                        $(".currentWeekSKU").text(data.currentWeek.sku_counts);
                    }

                    if(data.lastWeek.units_prepped != "" && data.lastWeek.sku_counts != ""){
                        $(".lastWeekUnit").text(data.lastWeek.units_prepped);
                        $(".lastWeekSKU").text(data.lastWeek.sku_counts);
                    }

                    if(data.currentMonth.units_prepped != "" && data.currentMonth.sku_counts != ""){
                        $(".currentMonthUnit").text(data.currentMonth.units_prepped);
                        $(".currentMonthSKU").text(data.currentMonth.sku_counts);
                    }

                    if(data.lastMonth.units_prepped != "" && data.lastMonth.sku_counts != ""){
                        $(".lastMonthUnit").text(data.lastMonth.units_prepped);
                        $(".lastMonthSKU").text(data.lastMonth.sku_counts);
                    }
                    hide_loader();
                },
            });
        }

    $("body").on("change","input[name=view_type]",function(){
        loadPrepGraph();
    });
    </script>

@endsection