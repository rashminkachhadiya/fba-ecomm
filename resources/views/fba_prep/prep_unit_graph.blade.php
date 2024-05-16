@if(!empty($data))

    <div  id="chartdiv" class="chartdiv"></div>
    @else
    <div class="row">
        <div class="col-md-12">
            <p>No Records to display.</p>
        </div>
    </div>
@endif

<!-- Styles -->
@if(!empty($data))
<style>
.chartdiv {
    width: 100%;
  height: 500px;
}
</style>
@endif
<!-- Chart code -->
<script>
var all_data = '{!! $data !!}';
var data = JSON.parse(all_data.replace(/&quot;/g,"'"));
if(data.length > 0){
    $('.chartdiv').html('');
    
    // Themes begin
    am4core.useTheme(am4themes_animated);
    //am4core.useTheme(am4themes_dataviz);
    // Themes end
  
    // Create chart instance
    var chart = am4core.create("chartdiv", am4charts.XYChart);
    chart.maskBullets = false;
    chart.numberFormatter.numberFormat = "#.#";
  
    chart.scrollbarX = new am4core.Scrollbar();
    chart.scrollbarX.parent = chart.topAxesContainer;
  
    chart.data = data;
 
    // Create axes
    var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
    categoryAxis.dataFields.category = "date";
    categoryAxis.renderer.grid.template.location = 0;
  
    var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
    valueAxis.renderer.inside = false;
    valueAxis.renderer.labels.template.disabled = false;
    valueAxis.min = 0;
    valueAxis.extraMax = 0.1;
    valueAxis.calculateTotals = true;
    
    // Create series
    function createSeries(field, name) {
  
      // Set up series
      var series = chart.series.push(new am4charts.ColumnSeries());
      series.name = name;
      series.dataFields.valueY = field;
      series.dataFields.categoryX = "date";
      series.sequencedInterpolation = true;
      
      // Make it stacked
      series.stacked = true;
      
      // Configure columns
      series.columns.template.width = am4core.percent(60);
      series.columns.template.tooltipText = "[bold]{name}[/]\n[font-size:14px]{categoryX}: {valueY}";
      
      // Add label
      var labelBullet = series.bullets.push(new am4charts.LabelBullet());
      labelBullet.label.text = "{valueY}";
      labelBullet.label.fill = am4core.color("#fff");
      labelBullet.locationY = 0.5;
      
      return series;
    }
    
    var prepUserNames = '{!! $prepUserNames !!}';
    var prepUserNames = JSON.parse(prepUserNames.replace(/&quot;/g,"'"));
    $.each(prepUserNames, function(key,uName) {
        createSeries(uName, capitalize(uName));
    });
  
    // Create series for total
    var totalSeries = chart.series.push(new am4charts.ColumnSeries());
    totalSeries.dataFields.valueY = "none";
    totalSeries.dataFields.categoryX = "date";
    totalSeries.stacked = true;
    totalSeries.hiddenInLegend = true;
    totalSeries.columns.template.strokeOpacity = 0;
  
    var totalBullet = totalSeries.bullets.push(new am4charts.LabelBullet());
    totalBullet.dy = -20;
    totalBullet.label.text = "{valueY.total}";
    totalBullet.label.hideOversized = false;
    totalBullet.label.fontSize = 18;
    totalBullet.label.background.fill = totalSeries.stroke;
    totalBullet.label.background.fillOpacity = 0.2;
    totalBullet.label.padding(5, 10, 5, 10);
  
  
    // Legend
    chart.legend = new am4charts.Legend();
}else{
    $('.chartdiv').html("<b style='text-align:center;'>No records to display.</b>");
}  
    function capitalize(str) {
        strVal = '';
        str = str.split(' ');
        for (var chr = 0; chr < str.length; chr++) {
            strVal += str[chr].substring(0, 1).toUpperCase() + str[chr].substring(1, str[chr].length) + ' '
        }
        return strVal
    }
  </script>
