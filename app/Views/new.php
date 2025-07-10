
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
  <style>
    .text-danger{color:red;}
  </style>
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
                <?php if(!empty(session()->getFlashdata('fail'))) : ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="icon-copy bi bi-exclamation-circle"></i>&nbsp;<?= session()->getFlashdata('fail'); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="OFF" enctype="multipart/form-data" class="row g-3" id="frmRequest">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="fullname" value="<?=set_value('fullname')?>" required/>
                                    <label>Fullname</label>
                                </div>
                                 <div id="fullname-error" class="error-message text-danger text-sm"></div>
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
                                 <div id="department-error" class="error-message text-danger text-sm"></div>
                            </div>
                            <div class="col-lg-2">
                                <div class="form-floating">
                                    <input type="date" class="form-control" name="date" id="date" value="<?=date('Y-m-d')?>" required/>
                                    <label>Date Needed</label>
                                </div>
                                <div id="date-error" class="error-message text-danger text-sm"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="purpose" style="height:150px;" required><?=set_value('purpose')?></textarea>
                            <label>Purpose</label>
                        </div>
                         <div id="purpose-error" class="error-message text-danger text-sm"></div>
                    </div>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="amount" value="<?=set_value('amount')?>" required/>
                                    <label>Amount</label>
                                </div>
                                 <div id="amount-error" class="error-message text-danger text-sm"></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-floating">
                                    <input type="file" class="form-control" name="file" value="<?=set_value('file')?>" required/>
                                    <label>Attachment</label>
                                </div>
                                 <div id="file-error" class="error-message text-danger text-sm"></div>
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
                        <div id="approver-error" class="error-message text-danger text-sm"></div>
                    </div>
                    <div class="col-12">
                        <?php if(!empty(session()->getFlashdata('fail'))) : ?>
                        <button type="submit" class="btn btn-primary" disabled><span class="bx bx-mail-send"></span>&nbsp;Send File</button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-primary"><span class="bx bx-mail-send"></span>&nbsp;Send File</button>
                        <?php endif; ?>
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
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script type="text/javascript">
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').setAttribute('min', today);
    // Reset form if the success message is displayed
    $('#frmRequest').on('submit',function(e){
        e.preventDefault();
        $('.error-message').html('');
        let data = $(this).serialize();
        $.ajax({
            url: "<?=site_url('save')?>",
            method: "POST",
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    $('#frmRequest')[0].reset();
                    Swal.fire({
                        title: 'Great!',
                        text: "Successfully submitted",
                        icon: 'success',
                        confirmButtonText: 'Continue'
                    }).then((result) => {
                        // Action based on user's choice
                        if (result.isConfirmed) {
                            // Perform some action when "Yes" is clicked
                            location.reload();
                        }
                    });
                } else {
                    var errors = response.error;
                    // Iterate over each error and display it under the corresponding input field
                    for (var field in errors) {
                        $('#' + field + '-error').html('<p>' + errors[field] +
                            '</p>'); // Show the first error message
                        $('#' + field).addClass(
                            'text-danger'); // Highlight the input field with an error
                    }
                }
            }
        });
    });
   </script>
</body>

</html>