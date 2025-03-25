
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Online Petty Cash - Manage</title>
  <meta content="description" name="Online petty-cash system for APFC Employees">
  <meta content="keywords" name="petty cash, petty-cash, finance">

  <!-- Favicons -->
  <link href="/assets/img/favicon.png" rel="icon">
  <link href="/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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
      <div class="card">
        <div class="card-body">
          <div class="card-title">Manage Petty Cash</div>
          <table class="table table-striped datatable" style="font-size:12px;">
            <thead class="bg-primary text-white">
              <th>Date Needed</th>
              <th>Fullname</th>
              <th>Department</th>
              <th>Amount</th>
              <th>Purpose</th>
              <th>Status</th>
              <th>Release?</th>
              <th>When?</th>
              <th>Comment</th>
            </thead>
            <tbody>
            <?php foreach($files as $row): ?>
              <tr>
                <td><?php echo $row->Date ?></td>
                <td><?php echo $row->Fullname ?></td>
                <td><?php echo $row->Department ?></td>
                <td><?php echo number_format($row->Amount,2) ?></td>
                <td><?php echo substr($row->Purpose,0,50) ?>...</td>
                <td>
                  <?php if($row->Status==0){ ?>
                    <span class="badge bg-warning">To Department Head</span>
                  <?php }else if($row->Status==1){?>
                    <span class="badge bg-info">For Verification</span>
                  <?php }else if($row->Status==2){?>
                    <span class="badge bg-danger">Rejected</span>
                  <?php }else if($row->Status==3){?>
                    <span class="badge bg-primary">To Fuel Department</span>
                  <?php }else if($row->Status==4){?>
                    <span class="badge bg-primary">To General Manager</span>
                  <?php }else if($row->Status==5){?>
                    <span class="badge bg-success">Approved</span>
                  <?php }else {?>
                    <a href="<?=site_url('re-apply')?>/<?php echo $row->requestID ?>" class="badge bg-danger">Re-Apply</a>
                  <?php } ?>
                </td>
                <td>
                  <?php if($row->Status==5){?>
                    <?php if($row->tag==0){ ?>
                      No
                    <?php }else {?>
                      Yes
                    <?php }?>
                  <?php }?>
                </td>
                <td><?php echo $row->DateTagged ?></td>
                <td><?php echo substr($row->Comment,0,100) ?>...</td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
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
</body>

</html>