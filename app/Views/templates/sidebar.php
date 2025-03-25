<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link <?= ($title == 'Dashboard') ? '' : 'collapsed' ?>" href="<?=site_url('dashboard')?>">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->
      <li class="nav-heading">Pages</li>
      <li class="nav-item">
        <a class="nav-link <?= ($title == 'New PCF' || $title=='Manage'|| $title=='PCF Review' || $title=='Re-Apply') ? '' : 'collapsed' ?>" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Petty Cash</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse <?= ($title == 'New PCF'||$title == 'Manage'|| $title=='PCF Review' || $title=='Re-Apply') ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?=site_url('new')?>" class="<?= ($title == 'New PCF') ? 'active' : '' ?>">
              <i class="bi bi-circle"></i><span>New Request</span>
            </a>
          </li>
          <li>
            <a href="<?=site_url('manage')?>" class="<?= ($title == 'Manage') ? 'active' : '' ?>">
              <i class="bi bi-circle"></i><span>Manage</span>
            </a>
          </li>
          <?php if($title=="Re-Apply"){?>
            <li>
              <a href="javascript:void(0);" class="<?= ($title == 'Re-Apply') ? 'active' : '' ?>">
                <i class="bi bi-circle"></i><span>Re-Apply</span>
              </a>
            </li>
          <?php } ?>
          <?php if(session()->get('role')=="Department Head"||session()->get('role')=="Officer"){ ?>
          <?php 
          $user = session()->get('loggedUser');
          $approveModel = new \App\Models\approveModel();
          $review = $approveModel->WHERE('accountID',$user)->WHERE('Status',0)->countAllResults();
          ?> 
          <li>
            <a href="<?=site_url('review')?>" class="<?= ($title == 'PCF Review') ? 'active' : '' ?>">
              <i class="bi bi-circle"></i><span>For Review <span class="badge bg-primary"><?=$review?></span></span>
            </a>
          </li>
          <?php } ?>
        </ul>
      </li><!-- End Forms Nav -->
        <li class="nav-item">
          <a class="nav-link <?= ($title == 'Manage Cash') ? '' : 'collapsed' ?>" href="<?=site_url('manage-cash')?>">
            <i class="bi bi-clipboard-data"></i>
            <span>Manage Cash</span>
          </a>
        </li>
        <?php if(session()->get('role')=="Admin"){ ?>
        <li class="nav-item">
          <a class="nav-link <?= ($title == 'PCF Settings') ? '' : 'collapsed' ?>" href="<?=site_url('configure')?>">
            <i class="bi bi-gear"></i>
            <span>Configuration</span>
          </a>
        </li>
      <?php } ?>
      <li class="nav-heading">Account</li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="<?=site_url('logout')?>">
          <i class="icon-copy bi bi-power"></i>
          <span>Sign out</span>
        </a>
      </li>
    </ul>

  </aside><!-- End Sidebar-->