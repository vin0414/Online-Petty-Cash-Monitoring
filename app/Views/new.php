
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Online Petty Cash - New PCF</title>
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
                <div class="card-title">New PCF</div>
                <?php if(!empty(session()->getFlashdata('success'))) : ?>
                    <div class="alert alert-success" role="alert">
                        <?= session()->getFlashdata('success'); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="OFF" action="<?=site_url('save')?>" enctype="multipart/form-data" class="row g-3" id="frmRequest">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="fullname" value="<?=set_value('fullname')?>" required/>
                                    <label>Fullname</label>
                                </div>
                                <div class="text-danger"><?=isset($validation)? display_error($validation,'fullname') : '' ?></div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-floating">
                                    <select class="form-control" name="department" required>
                                        <option value="">Choose</option>
                                        <?php foreach($department as $row): ?>
                                            <option><?php echo $row['departmentName'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label>Department</label>
                                </div>
                                <div class="text-danger"><?=isset($validation)? display_error($validation,'department') : '' ?></div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-floating">
                                    <input type="date" class="form-control" name="date" value="<?=date('Y-m-d')?>" required/>
                                    <label>Date Needed</label>
                                </div>
                                <div class="text-danger"><?=isset($validation)? display_error($validation,'date') : '' ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="purpose" style="height:150px;" required><?=set_value('purpose')?></textarea>
                            <label>Purpose</label>
                        </div>
                        <div class="text-danger"><?=isset($validation)? display_error($validation,'purpose') : '' ?></div>
                    </div>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="amount" value="<?=set_value('amount')?>" required/>
                                    <label>Amount</label>
                                </div>
                                <div class="text-danger"><?=isset($validation)? display_error($validation,'amount') : '' ?></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-floating">
                                    <input type="file" class="form-control" name="file" value="<?=set_value('file')?>" required/>
                                    <label>Attachment</label>
                                </div>
                                <div class="text-danger"><?=isset($validation)? display_error($validation,'file') : '' ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <select class="form-control" name="approver" required>
                                <option value="">Select</option>
                                <?php foreach($account as $row):?>
                                    <option value="<?php echo $row['accountID']?>"><?php echo $row['Fullname'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label>Department Head/Intermediate Supervisor</label>
                        </div>
                        <div class="text-danger"><?=isset($validation)? display_error($validation,'approver') : '' ?></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><span class="bx bx-mail-send"></span>&nbsp;Send</button>
                    </div>
                </form>
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
  <script type="text/javascript">
    // Reset form if the success message is displayed
    <?php if(!empty(session()->getFlashdata('success'))): ?>
        $('#frmRequest')[0].reset();
    <?php endif; ?>
   </script>
</body>

</html>