<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center">
        <img src="assets/img/fastcat.png" alt="">
        <span class="d-none d-lg-block" style="font-size:20px;">&nbsp;Petty Cash</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <li class="nav-item dropdown">
          <?php 
          $user = session()->get('loggedUser');
          $approveModel = new \App\Models\approveModel();
          $review = $approveModel->WHERE('accountID',$user)->WHERE('Status',0)->countAllResults();
          ?> 
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number"><?=$review ?></span>
          </a><!-- End Notification Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              You have <?=$review ?> new notifications
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <?php
              $user = session()->get('loggedUser');
              $db = db_connect();
              $builder = $db->table('tblapprove a');
              $builder->select('a.DateReceived,b.Purpose,b.Amount');
              $builder->join('tblrequest b','b.requestID=a.requestID','LEFT');
              $builder->WHERE('a.Status',0)->WHERE('a.accountID',$user);
              $review = $builder->get()->getResult();
              foreach($review as $row)
              {
              ?>
            <li class="notification-item">
              <i class="bi bi-info-circle text-primary"></i>
              <div>
                <h6>Requesting of approval with sum of <?php echo number_format($row->Amount,2) ?> for <?php echo substr($row->Purpose,0,20) ?>..</h6>
                <p><?php echo date('Y-M-d',strtotime($row->DateReceived)) ?></p>
              </div>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <?php
              } 
            ?>
          </ul><!-- End Notification Dropdown Items -->

        </li><!-- End Notification Nav -->
        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="assets/img/fastcat.png" width="30" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?= session()->get('fullname') ?></span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?= session()->get('fullname') ?></h6>
              <span><?= session()->get('role') ?></span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.html">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="<?=site_url('logout')?>">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->