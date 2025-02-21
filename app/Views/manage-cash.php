
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Online Petty Cash - Manage Cash</title>
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
  <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.css" />
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
      <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true"><span class="bi bi-download"></span>&nbsp;For Release</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false"><span class="bi bi-stack"></span>&nbsp;For Replenish</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false"><span class="bi bi-hdd-stack"></span>&nbsp;Others</button>
        </li>
      </ul> 
      <div class="tab-content pt-2" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
          <div class="card">
            <div class="card-body">
              <h6 class="card-title">For Release - Petty Cash</h6>
              <div class="table-responsive">
                <table class="table table-striped" id="tblapprove" style="font-size:12px;"> 
                  <thead>
                    <th>Date</th>
                    <th>PCF No</th>
                    <th>Fullname</th>
                    <th>Department</th>
                    <th>Amount</th>
                    <th>Purpose</th>
                    <th>Release?</th>
                    <th>When</th>
                    <th>Action</th>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
          <div class="row">
            <div class="col-lg-9">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Liquidated/Settled
                    <button type="button" class="btn btn-primary btn-sm add" style="float:right;"  data-bs-toggle="modal" data-bs-target="#replenishModal"><span class="bi bi-plus-circle"></span>&nbsp;Add</button>
                  </h6>
                  <div class="table-responsive">
                    <table class="table table-striped" style="font-size:12px;"> 
                      <thead>
                        <th>Date</th>
                        <th>Fullname</th>
                        <th>Department</th>
                        <th>Particulars</th>
                        <th>Amount</th>
                        <th>Action</th>
                      </thead>
                      <tbody id="tblresult">
                        <tr>
                          <td colspan="6" class="text-center">No Record(s)</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Unliquidated</h6>
                  <div class="list-group">
                    <?php if(empty($unsettle)){ ?>
                      <a href="#" class="list-group-item list-group-item-action" aria-current="true">
                        <p class="mb-1">No Record(s)</p>
                      </a>
                    <?php }else { ?>
                    <?php foreach($unsettle as $row): ?>
                      <a href="#" class="list-group-item list-group-item-action" aria-current="true">
                        <p class="mb-1"><?php echo $row->Fullname ?></p>
                        <small><?php echo number_format($row->Amount,2) ?></small>
                      </a>
                    <?php endforeach; ?>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">

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
  <div class="modal fade" id="replenishModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Replenishment Form</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" class="row g-3" id="form1">
            <?= csrf_field(); ?>
            <div class="col-lg-12">
              <label>Date</label>
              <input type="date" class="form-control" name="date" required/>
              <div id="date-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12">
              <label>PCF Number</label>
              <select class="form-control" name="request" required>
                <option value="">Choose</option>
                <?php foreach($files as $row): ?>
                <option value="<?php echo $row['requestID'] ?>"><?php echo "PCF-".str_pad($row['requestID'], 4, '0', STR_PAD_LEFT) ?></option>
                <?php endforeach; ?>
              </select>
              <div id="request-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12">
              <label>Particulars</label>
              <textarea name="particulars" class="form-control" required></textarea>
              <div id="particulars-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12">
              <button type="submit" class="form-control btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div><!-- End Basic Modal-->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.min.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  <script>
    $(document).ready(function(){
      fetch();
    });
    $('#form1').on('submit',function(e){
      e.preventDefault();
      let data = $(this).serialize();
      $.ajax({
          url: "<?=site_url('add-item')?>",
          method: "POST",
          data: data,
          success: function(response) 
          {
            if (response.success) {
                $('#form1')[0].reset();
                $('#replenishModal').modal('hide');
                fetch();
                Swal.fire({
                    title: "Great!",
                    text: "Successfully saved",
                    icon: "success"
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

    $(document).on('click','.close',function(){
      let confirmation = confirm('Do you want to tag this as replenished?');
      if(confirmation)
      {
        $.ajax({
          url:"<?=site_url('close-item')?>",
          method:"POST",data:{value:$(this).val()},
          success:function(response)
          {
            if(response==="success"){
              fetch();
            }
            else
            {
              alert(response);
            }
          }
        });
      }
    });

    function fetch()
    {
      $('#tblresult').html("<tr><td colspan='6' class='text-center'>Loading...</td></tr>");
      $.ajax({
        url:"<?=site_url('fetch-item')?>",
        method:"GET",
        success:function(response)
        {
          if(response==="")
          {
            $('#tblresult').html("<tr><td colspan='6' class='text-center'>No Record(s)</td></tr>");
          }
          else
          {
            $('#tblresult').html(response);
          }
        }
      });
    }
    var tables = $('#tblapprove').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?=site_url('approve')?>",
            "type": "GET",
            "dataSrc": function(json) {
                // Handle the data if needed
                return json.data;
            },
            "error": function(xhr, error, code) {
                console.error("AJAX Error: " + error);
                alert("Error occurred while loading data.");
            }
        },
        "columns": [{
                "data": "date"
            },
            {
              "data":"code"
            },
            {
               "data": "fullname"
            },
            {
              "data": "department"
            },
            {
                "data": "amount"
            },
            {
                "data": "purpose"
            },
            {
                "data": "release"
            },
            {
                "data": "when"
            },
            {
                "data": "action"
            }
        ]
    });

    $(document).on('click','.tag',function(){
      let confirmation = confirm("Do you want to tag this as release?");
      if(confirmation)
      {
        $.ajax({
          url:"<?=site_url('release')?>",
          method:"POST",data:{value:$(this).val()},
          success:function(response)
          {
            if(response==="success")
            {
              tables.ajax.reload();
              Swal.fire({
                    title: "Great!",
                    text: "Successfully submitted",
                    icon: "success"
                });
            }
            else
            {
              alert(response);
            }
          }
        });
      }
    });
  </script>
</body>

</html>