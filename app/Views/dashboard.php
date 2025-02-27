
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Online Petty Cash - Dashboard</title>
  <meta content="description" name="Online petty-cash system for APFC Employees">
  <meta content="keywords" name="petty cash, petty-cash, finance">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script> 
  <script type="text/javascript">
    google.charts.load('visualization', "1", {
      packages: ['corechart']
    });
  </script>
</head>

<body>
  <!-- ======= Header ======= -->
  <?= view('templates/header'); ?>
  <!-- ======= Sidebar ======= -->
  <?= view('templates/sidebar'); ?>
  <main id="main" class="main">

    <div class="pagetitle">
      <h1><?=$title?></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?=site_url('dashboard')?>">Home</a></li>
          <li class="breadcrumb-item active"><?=$title?></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <section class="section dashboard">
      <div class="row g-3">
        <div class="col-lg-3">
          <div class="card bg-primary text-white">
            <div class="card-body">
              <div class="card-title text-white">Pending</div>
              <h1><?=$pending?></h1>
            </div>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="card bg-primary text-white">
            <div class="card-body">
              <div class="card-title text-white">Approved</div>
              <h1><?=$approve?></h1>
            </div>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="card bg-primary text-white">
            <div class="card-body">
              <div class="card-title text-white">Total</div>
              <h1><?=$total?></h1>
            </div>
          </div>
        </div>
        <div class="col-lg-3">
          <div class="card bg-primary text-white">
            <div class="card-body">
              <div class="card-title text-white">Cash Released</div>
              <h1><?=$release?></h1>
            </div>
          </div>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <div class="card-title"><i class="bx bx-line-chart"></i>&nbsp;Expense Trend</div>
              <div id="chart" style="height:300px;"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <div class="card-title"><i class="bx bx-pie-chart-alt-2"></i>&nbsp;Departmental Expense</div>
              <div id="chartContainer" style="height:300px;"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
      <!-- All the links in the footer should remain intact. -->
      <!-- You can delete the links only if you purchased the pro version. -->
      <!-- Licensing information: https://bootstrapmade.com/license/ -->
      <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.min.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  <script>
    google.charts.setOnLoadCallback(expenseChart);
    google.charts.setOnLoadCallback(departmentExpenseChart);
    function departmentExpenseChart()
    {
        var data = google.visualization.arrayToDataTable([
        ["Department", "Total"],
        <?php 
        foreach ($expense as $row){
        echo "['".$row->Department."',".$row->Total."],";
        }
        ?>
      ]);

      var options = {
      title: '',
      curveType: 'function',
      legend: { position: 'bottom' },
      };
      /* Instantiate and draw the chart.*/
      var chart = new google.visualization.PieChart(document.getElementById('chartContainer'));
      chart.draw(data, options);
    }
    function expenseChart() 
    {
      var data = google.visualization.arrayToDataTable([
        ["Month", "Total"],
        <?php 
        foreach ($chart as $row){
        $month ="";
        switch($row->Month)
        {
          case "01":$month="January";break;
          case "02":$month="February";break;
          case "03":$month="March";break;
          case "04":$month="April";break;
          case "05":$month="May";break;
          case "06":$month="June";break;
          case "07":$month="July";break;
          case "08":$month="August";break;
          case "09":$month="September";break;
          case "10":$month="October";break;
          case "11":$month="November";break;
          case "12":$month="December";break;
        }
        echo "['".$month."',".$row->Total."],";
        }
        ?>
      ]);

      var options = {
      title: '',
      curveType: 'function',
      legend: { position: 'bottom' },
      };
      /* Instantiate and draw the chart.*/
      var chart = new google.visualization.ColumnChart(document.getElementById('chart'));
      chart.draw(data, options);
    }
  </script>
</body>

</html>