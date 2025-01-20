<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link " href="<?=site_url('dashboard')?>">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Petty Cash</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?=site_url('new')?>">
              <i class="bi bi-circle"></i><span>New Request</span>
            </a>
          </li>
          <li>
            <a href="<?=site_url('manage')?>">
              <i class="bi bi-circle"></i><span>Manage</span>
            </a>
          </li>
          <?php if(session()->get('role')=="Special-user"||session()->get('role')=="Admin"){ ?>
          <li>
            <a href="<?=site_url('review')?>">
              <i class="bi bi-circle"></i><span>For Review</span>
            </a>
          </li>
          <?php } ?>
        </ul>
      </li><!-- End Forms Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-cash-coin"></i><span>Expenses</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="tables-general.html">
              <i class="bi bi-circle"></i><span>Transactions</span>
            </a>
          </li>
          <li>
            <a href="tables-data.html">
              <i class="bi bi-circle"></i><span>Categorize Expenses</span>
            </a>
          </li>
        </ul>
      </li><!-- End Tables Nav -->

      <li class="nav-heading">Pages</li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="users-profile.html">
          <i class="bi bi-briefcase"></i>
          <span>Cash Reconciliation</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="pages-faq.html">
          <i class="bi bi-clipboard-data"></i>
          <span>Manage Cash</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="pages-contact.html">
          <i class="bi bi-bar-chart"></i>
          <span>Report</span>
        </a>
      </li>
      <?php if(session()->get('role')=="Admin"){ ?>
        <li class="nav-item">
          <a class="nav-link collapsed" href="<?=site_url('configure')?>">
            <i class="bi bi-gear"></i>
            <span>Configuration</span>
          </a>
        </li>
      <?php } ?>
    </ul>

  </aside><!-- End Sidebar-->