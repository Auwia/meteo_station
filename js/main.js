var grovyApp = angular.module('grovyApp', ['AngularChart']);

grovyApp.controller("GrovyCtrl", function ($scope, $http) {
  // Opzioni di periodo disponibili
  $scope.period = ["Last 35", "Day", "Month", "6 Months", "Year"];

  // Inizializzazione dei valori di default
  $scope.selectedPeriod = "Day";
  $scope.day = new Date(); // Usa un oggetto Date per il campo "date"
  $scope.lineChartXDataOut = [];
  $scope.lineChartYDataOut = [];
  $scope.lineChartXDataPress = [];
  $scope.lineChartYDataPress = [];

  // Carica i dati iniziali
  function loadData() {
    console.log("Caricamento dati per il periodo:", $scope.selectedPeriod, "e la data:", $scope.day);
    getMis($scope, $http, $scope.selectedPeriod);
//    getMisPressure($scope, $http, $scope.selectedPeriod);
  }

  // Funzione per aggiornamento manuale
  $scope.manualUpdate = function () {
    console.log("Aggiornamento manuale avviato");
    loadData();
  };

  // Funzione per ottenere dati meteo generali
  function getMis($scope, $http, period) {
    var selectedDate = moment($scope.day).format("YYYY-MM-DD");
    console.log("Richiesta dati meteo generali per il periodo:", period, "e la data:", selectedDate);

    $http
      .get("php/getMisPeriod.php/?period=" + period + "&date=" + selectedDate)
      .then(function (response) {
        if (response.data && Array.isArray(response.data)) {
          var rest_response = response.data;

          var timestamps_out = [];
          var temperatures_out = [];
          var humidity_out = [];

          rest_response.forEach(function (item) {
            if (item.location === "outside") {
              var tmp = item.timestamp;
              var lastFive;

              if (period === "Last 35") lastFive = tmp.substring(11, tmp.length - 3);
              if (period === "Day") lastFive = tmp.substr(tmp.length - 8, 5);
              if (period === "Month") lastFive = tmp;
              if (period === "Year") lastFive = tmp.substring(0, 7);
              if (period === "6 Months") lastFive = tmp.substring(0, 7);

              timestamps_out.push(lastFive);
              temperatures_out.push(item.temperature);
              humidity_out.push(item.humidity);
            }
          });

          $scope.lineChartXDataOut = timestamps_out;
          $scope.lineChartYDataOut = [
            { name: "Temperature [°C]", data: temperatures_out },
            { name: "Humidity [%]", data: humidity_out },
          ];

          console.log("Dati meteo generali caricati:", $scope.lineChartYDataOut);
        } else {
          console.error("Formato dati non valido:", response.data);
        }
      })
      .catch(function (error) {
        console.error("Errore durante il caricamento dei dati generali:", error);
      });
  }

  // Funzione per ottenere dati meteo della pressione
  function getMisPressure($scope, $http, period) {
    var selectedDate = moment($scope.day).format("YYYY-MM-DD");
    console.log("Richiesta dati pressione per il periodo:", period, "e la data:", selectedDate);

    $http
      .get("php/getMisPressure.php/?period=" + period + "&date=" + selectedDate)
      .then(function (response) {
        if (response.data && Array.isArray(response.data)) {
          var rest_response_press = response.data;

          var timestamps_press = [];
          var pressures = [];
          var temperatures_press = [];
          var altitudes = [];

          rest_response_press.forEach(function (item) {
            if (item.location === "outside") {
              var tmp = item.timestamp;
              var lastFive;

              if (period === "Last 35") lastFive = tmp.substring(11, tmp.length - 3);
              if (period === "Day") lastFive = tmp.substr(tmp.length - 8, 5);
              if (period === "Month") lastFive = tmp;
              if (period === "Year") lastFive = tmp.substring(0, 7);
              if (period === "6 Months") lastFive = tmp.substring(0, 7);

              timestamps_press.push(lastFive);
              pressures.push(item.pressure / 1000); // Conversione in kPa
              temperatures_press.push(item.temperature);
              altitudes.push(item.altitude);
            }
          });

          $scope.lineChartXDataPress = timestamps_press;
          $scope.lineChartYDataPress = [
            { name: "Pressure [hPa]", data: pressures },
            { name: "Temperature [°C]", data: temperatures_press },
            { name: "Altitude [m]", data: altitudes },
          ];

          console.log("Dati pressione caricati:", $scope.lineChartYDataPress);
        } else {
          console.error("Formato dati non valido per la pressione:", response.data);
        }
      })
      .catch(function (error) {
        console.error("Errore durante il caricamento dei dati di pressione:", error);
      });
  }

  // Funzione per validare le date
  function isValid(value) {
    if (!value) return false;
    var m = value.match(/(\d{4})-(\d{2})-(\d{2})/);
    if (!m || m.length !== 4) return false;

    var year = parseInt(m[1], 10);
    var month = parseInt(m[2], 10);
    var day = parseInt(m[3], 10);

    var today = new Date();
    if (year < 1999 || year > today.getFullYear()) return false;
    if (month < 1 || month > 12) return false;
    if (day < 1 || day > 31) return false;

    return true;
  }

  // Aggiorna i dati quando cambia il periodo selezionato
  $scope.$watch("selectedPeriod", function (newVal, oldVal) {
    if (newVal !== oldVal) {
      console.log("Periodo selezionato cambiato:", newVal);
      loadData();
    }
  });

  // Carica i dati iniziali
  loadData();
});
