var grovyApp = angular.module('grovyApp', ['AngularChart']);

grovyApp.controller("GrovyCtrl", function($scope, $http){

  $scope.period = ["Last 35", "Day", "Month", "6 Months", "Year"];
  
  var period_default = "Day";
  $scope.selectedPeriod = "Day";
  $scope.day = moment().format('YYYY-MM-DD');
  $scope.to = moment().format('YYYY-MM-DD');
  $scope.from = moment().format('YYYY-MM-DD');
  
  function loadData() {
    getMis($scope, $http, $scope.selectedPeriod);
    // getMisPressure($scope, $http, $scope.selectedPeriod);
  }

  $scope.manualUpdate = function () {
    console.log('Button clicked (via ng-click)');
    loadData();
  };

  function getMis($scope, $http, period){
    var rest_response = {};
    // Outside
    var timestamps_out = []; 
    var areas_out = [];
    var temperatures_out = [];
    var humidity_out = [];
    
    $http.get('php/getMisPeriod.php/?period=' + $scope.selectedPeriod + '&date=' + moment($scope.day).format('YYYY-MM-DD'))
	.then(function(response){
      		rest_response = response.data;
      		for(var i=0; i < rest_response.length; i++){
        
        		// outside
       		 	if (rest_response[i]['location'] == 'outside') {
       			   	var tmp = rest_response[i]['timestamp'];

          		if ($scope.selectedPeriod == 'Last 35'){
            			var lastFive = tmp.substring(11, tmp.length-3);
          		}
          
          		if ($scope.selectedPeriod == 'Day') {
            			var lastFive = tmp.substr(tmp.length - 8, 5);
          		}  

          		if ($scope.selectedPeriod == 'Month'){
            			var lastFive = tmp;
          		} 
          
          		if ($scope.selectedPeriod == 'Year'){
            			var lastFive = tmp.substring(0,7);
          		}
          
          		if ($scope.selectedPeriod == '6 Months'){
            			var lastFive = tmp.substring(0,7);
          		}    

          		timestamps_out.push(lastFive);
          		areas_out.push(rest_response[i]['location']);
          		temperatures_out.push(rest_response[i]['temperature']);
          		humidity_out.push(rest_response[i]['humidity']);
        	}
      	}
      
      var data_out = {
        "xDataOut": timestamps_out,
        "yDataOut":[{
          "name": "Temperature [°C]",
          "data": temperatures_out
        },{
          "name": "Humidity [%]",
          "data": humidity_out
        }]
      };
      
      $scope.lineChartXDataOut=data_out.xDataOut;
      $scope.lineChartYDataOut=data_out.yDataOut;
    });
  }

function getMisPressure($scope, $http, period){
  var rest_response_press = {};
  // Pressure
  var timestamps_press = [];
  var areas_press = [];
  var pressures = [];
  var temperatures_press = [];
  var altitudes = [];

  $http.get('php/getMisPressure.php/?period=' + period + '&date=' + moment($scope.day).format('YYYY-MM-DD')).then(function(response){
    rest_response_press = response.data;
    for(var i=0; i < rest_response_press.length; i++){

      // pressure
      if (rest_response_press[i]['location'] == 'outside') {
        var tmp = rest_response_press[i]['timestamp'];

        if (period == 'Last 35'){
          var lastFive = tmp.substring(11, tmp.length-3);
        }

        if (period == 'Day') {
          var lastFive = tmp.substr(tmp.length - 8, 5);
        }

        if (period == 'Month'){
          var lastFive = tmp;
        }

        if (period == 'Year'){
          var lastFive = tmp.substring(0,7);
        }

        if (period == '6 Months'){
          var lastFive = tmp.substring(0,7);
        }

        timestamps_press.push(lastFive);
        areas_press.push(rest_response_press[i]['location']);
        pressures.push(rest_response_press[i]['pressure'] / 1000);
        temperatures_press.push(rest_response_press[i]['temperature']);
        altitudes.push(rest_response_press[i]['altitude']);
      }
    }

    var data_press = {
      "xDataPress": timestamps_press,
      "yDataPress":[{
        "name": "Pressure [hPa]",
        "data": pressures
      },{
        "name": "Temperature [°C]",
        "data": temperatures_press
      },{
        "name": "Altitude [m]",
        "data": altitudes
      }]
    };

    $scope.lineChartXDataPress=data_press.xDataPress;
    $scope.lineChartYDataPress=data_press.yDataPress;

console.log($scope.lineChartYDataPress);

  });
}

  function isValid(value) {
    // STRING FORMAT yyyy-mm-dd
    if(value=="" || value==null){return false;}                
    
    // m[1] is year 'YYYY' * m[2] is month 'MM' * m[3] is day 'DD'          
    var m = value.match(/(\d{4})-(\d{2})-(\d{2})/);
    
    // STR IS NOT FIT m IS NOT OBJECT
    if( m === null || typeof m !== 'object'){return false;}        
    
    // CHECK m TYPE
    if (typeof m !== 'object' && m !== null && m.size!==3){return false;}
          
    var ret = true; //RETURN VALUE            
    var thisYear = new Date().getFullYear(); //YEAR NOW
    var minYear = 1999; //MIN YEAR
    
    // YEAR CHECK
    if( (m[1].length < 4) || m[1] < minYear || m[1] > thisYear){ret = false;}
    // MONTH CHECK      
    if( (m[2].length < 2) || m[2] < 1 || m[2] > 12){ret = false;}
    // DAY CHECK
    if( (m[3].length < 2) || m[3] < 1 || m[3] > 31){ret = false;}
    
    return ret;    
  }
});
