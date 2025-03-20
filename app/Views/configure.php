
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Online Petty Cash - System Configuration</title>
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
          <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true"><span class="bi bi-person-check"></span>&nbsp;Approvers</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false"><span class="bi bi-shield-exclamation"></span>&nbsp;Access</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="department-tab" data-bs-toggle="tab" data-bs-target="#departments" type="button" role="tab" aria-controls="departments" aria-selected="false"><span class="bx bx-building-house"></span>&nbsp;Department</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="false"><span class="bi bi-server"></span>&nbsp;System Logs</button>
        </li>
      </ul>
      <div class="tab-content pt-2" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
          <div class="card">
            <div class="card-body">
              <br/>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal"><span class="bi bi-plus-circle"></span>&nbsp;Assign User</button>
              <div class="table-responsive">
                <table class="table table-striped" id="tblassign" style="font-size:12px;"> 
                  <thead>
                    <th>Date</th>
                    <th>Fullname</th>
                    <th>Username</th>
                    <th>Level</th>
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
          <div class="card">
            <div class="card-body">
              <br/>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal"><span class="bi bi-plus-circle"></span>&nbsp;Create Account</button>
              <div class="table-responsive">
                <table class="table table-striped" id="tblaccount" style="font-size:12px;"> 
                  <thead>
                    <th>Date</th>
                    <th>Username</th>
                    <th>Fullname</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane fade" id="departments" role="tabpanel" aria-labelledby="departments-tab">
          <div class="row g-3">
            <div class="col-lg-8">
              <div class="card">
                <div class="card-body">
                  <div class="card-title">Department</div>
                  <div class="table-responsive">
                    <table class="table table-striped" id="tbldepartment" style="font-size:12px;"> 
                      <thead>
                        <th>#</th>
                        <th>Department Name</th>
                        <th>Date Created</th>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="card">
                <div class="card-body">
                  <div class="card-title">New Entry</div>
                  <form class="row g-3" method="POST" id="frmDepartment">
                      <?= csrf_field(); ?>
                      <div class="col-lg-12">
                        <label>New Department</label>
                        <input type="text" class="form-control" name="department_name" required/>
                        <div id="department_name-error" class="error-message text-danger text-sm"></div>
                      </div>
                      <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary form-control">Add Entry</button>
                      </div>
                    </form>
                </div>
              </div>
            </div>           
          </div>
        </div> 
        <div class="tab-pane fade" id="logs" role="tabpanel" aria-labelledby="contact-tab">
          <div class="card">
            <div class="card-body">
              <br/>
              <div class="table-responsive">
                <table class="table table-striped" id="tbllogs" style="font-size:12px;"> 
                  <thead>
                    <th>Date</th>
                    <th>Fullname</th>
                    <th>Activity</th>
                  </thead>
                  <tbody>
                  <?php foreach($log as $row):?>
                    <tr>
                      <td><?php echo $row->Date ?></td>
                      <td><?php echo $row->Fullname ?></td>
                      <td><?php echo $row->Activity ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
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
  <div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" class="row g-3" id="frmAccount">
            <?= csrf_field(); ?>
            <div class="col-lg-12 form-group">
              <label>Fullname</label>
              <input type="text" class="form-control" name="fullname" required/>
              <div id="fullname-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12 form-group">
              <div class="row g-3">
                <div class="col-lg-6">
                  <label>Username</label>
                  <input type="text" class="form-control" name="username" required/>
                  <div id="username-error" class="error-message text-danger text-sm"></div>
                </div>
                <div class="col-lg-6">
                  <label>System Role</label>
                  <select class="form-control" name="role" required>
                    <option value="">Choose</option>
                    <option>Admin</option>
                    <option>Department Head</option>
                    <option>Officer</option>
                    <option>User</option>
                  </select>
                  <div id="role-error" class="error-message text-danger text-sm"></div>
                </div>
              </div>
            </div>
            <div class="col-lg-12 form-group">
              <label>Department</label>
              <select class="form-control" name="department" required>
                <option value="">Choose</option>
                <?php foreach($department as $row): ?>
                  <option value="<?php echo $row['departmentID'] ?>"><?php echo $row['departmentName'] ?></option>
                <?php endforeach; ?>
              </select>
              <div id="department-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12 form-group">
              <button type="submit" class="form-control btn btn-primary">Register</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div><!-- End Basic Modal-->
  <!-- assign modal -->
  <div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">New Assignment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" class="row g-3" id="frmAssign">
            <?= csrf_field(); ?>
            <div class="col-lg-12 form-group">
              <label>Choose user account</label>
              <select class="form-control" name="user" required>
                <option value="">Choose</option>
                <?php foreach($account as $row): ?>
                <option value="<?php echo $row['accountID'] ?>"><?php echo $row['Fullname'] ?></option>
                <?php endforeach; ?>
              </select>
              <div id="user-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12 form-group">
              <label>Choose account level</label>
              <select class="form-control" name="level" required>
                <option value="">Choose</option>
                <option>Verifier</option>
                <option>Prior Approver</option>
                <option>Final Approver</option>
              </select>
              <div id="level-error" class="error-message text-danger text-sm"></div>
            </div>
            <div class="col-lg-12 form-group">
              <button type="submit" class="form-control btn btn-primary">Register</button>
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
    $('#tbllogs').DataTable();
    var department = $('#tbldepartment').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?=site_url('fetch-department')?>",
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
        "searching":true,
        "columns": [{
                "data": "id"
            },
            {
                "data": "department"
            },
            {
                "data": "date"
            }
        ]
    });

    $('#frmDepartment').on('submit',function(e){
      e.preventDefault();
      let data = $(this).serialize();
      $.ajax({
            url: "<?=site_url('save-department')?>",
            method: "POST",
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#frmDepartment')[0].reset();
                    department.ajax.reload();
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

    var tables = $('#tblassign').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?=site_url('fetch-assign')?>",
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
        "searching":true,
        "columns": [{
                "data": "date"
            },
            {
                "data": "fullname"
            },
            {
              "data": "username"
            },
            {
                "data": "role"
            },
            {
                "data": "action"
            }
        ]
    });
    var tblUser = $('#tblaccount').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?=site_url('fetch-user')?>",
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
        "searching":true,
        "columns": [{
                "data": "date"
            },
            {
                "data": "username"
            },
            {
              "data": "fullname"
            },
            {
                "data": "department"
            },
            {
                "data": "role"
            },
            {
                "data": "status"
            },
            {
                "data": "action"
            }
        ]
    });
    $('#frmAccount').on('submit', function(e) {
        e.preventDefault();
        $('.error-message').html('');
        let data = $(this).serialize();
        $.ajax({
            url: "<?=site_url('save-user')?>",
            method: "POST",
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#frmAccount')[0].reset();
                    $('#assignModal').modal('hide');
                    tblUser.ajax.reload();
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

    $('#frmAssign').on('submit', function(e) {
        e.preventDefault();
        $('.error-message').html('');
        let data = $(this).serialize();
        $.ajax({
            url: "<?=site_url('save-assign')?>",
            method: "POST",
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#frmAssign')[0].reset();
                    $('#addAccountModal').modal('hide');
                    tables.ajax.reload();
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
    $(document).on('click','.delete',function(){
      let confirmation = confirm("Do you want to remove the assigned user?");
      if(confirmation)
      {
        $.ajax({
          url:"<?=site_url('remove-assignment')?>",
          method:"POST",data:{value:$(this).val()},
          success:function(response)
          {
            if(response.success)
            {
              tables.ajax.reload();
              Swal.fire({
                    title: "Great!",
                    text: "Successfully removed",
                    icon: "success"
                });
            }
            else
            {
              Swal.fire({
                        title: "Warning",
                        text: response,
                        icon: "warning"
                    });
            }
          }
        });
      }
    });
    $(document).on('click','.remove',function(){
      let confirmation = confirm("Do you want to remove the selected user account?");
      if(confirmation)
      {
        $.ajax({
          url:"<?=site_url('remove-user')?>",
          method:"POST",data:{value:$(this).val()},
          success:function(response)
          {
            if(response.success)
            {
              tblUser.ajax.reload();
              Swal.fire({
                    title: "Great!",
                    text: "Successfully removed",
                    icon: "success"
                });
            }
            else
            {
              Swal.fire({
                        title: "Warning",
                        text: response,
                        icon: "warning"
                    });
            }
          }
        });
      }
    });
  </script>
</body>

</html>