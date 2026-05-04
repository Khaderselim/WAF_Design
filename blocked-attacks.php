<?php
session_start();

// Initialize WAF - detects and blocks attacks
require_once "waf_init.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include "Class/Block.php";
$block = new Block();
$total_block = $block->getTotalBlockedIp();
$today = $block->getBlockedToday();
$rate = $block->getBlockRate();
$toblock = $block->getAlertToBlock();
$username = $_SESSION['username'];
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$role = $_SESSION['role_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Blocked Attacks - WAF Dashboard</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ["assets/css/fonts.min.css"],
            },
            active: function () {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    
    <style>
        /* Truncate URL in table */
        .url-truncate {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
            <div class="logo-header" data-background-color="dark">
                <a href="dashboard.php" class="logo">
                    <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
                </a>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                    <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                </div>
                <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
            </div>
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
            <div class="sidebar-content">
                <ul class="nav nav-collapse">
                    <li class="nav-item">
                        <a  href="dashboard.php" class="collapsed">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                        <h4 class="text-section">Components</h4>
                    </li>
                    <li class="nav-item">
                        <a  href="traffic-overview.php">
                            <i class="fas fa-chart-line"></i>
                            <p>Traffic Overview</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a  href="security-alerts.php">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Security Alerts</p>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a  href="blocked-attacks.php">
                            <i class="fas fa-shield-alt"></i>
                            <p>Blocked Attacks</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a  href="upload-files.php">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Upload Files</p>
                        </a>
                    </li>

                    <?php if($role == 1){ ?>
                        <li class="nav-item">
                            <a  href="settings.php">
                                <i class="fas fa-cog"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
    <!-- End Sidebar -->

    <div class="main-panel">
        <div class="main-header">
            <div class="main-header-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="dashboard.php" class="logo">
                        <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                        <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                    </div>
                    <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                </div>
            </div>
            <!-- Navbar Header -->
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="submit" class="btn btn-search pe-1"><i class="fa fa-search search-icon"></i></button>
                            </div>
                            <input type="text" placeholder="Search attacks..." class="form-control" />
                        </div>
                    </nav>

                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                                </div>
                                <span class="profile-username">
                                    <span class="op-7">Hi,</span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($username); ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg">
                                                <img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
                                            </div>
                                            <div class="u-text">
                                                <h4><?php echo htmlspecialchars($username); ?></h4>
                                                <p class="text-muted"><?php echo htmlspecialchars($email); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">My Profile</a>
                                        <a class="dropdown-item" href="#">Account Setting</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="logout.php">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="page-inner">
                <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                    <div>
                        <h3 class="fw-bold mb-3">Blocked Attacks</h3>
                        <p class="text-muted">Successfully prevented attacks and intrusions</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-success bubble-shadow-small">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Total Blocked</p>
                                            <h4 class="card-title"><?php echo $total_block ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-danger bubble-shadow-small">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Today</p>
                                            <h4 class="card-title"><?php echo $today ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-info bubble-shadow-small">
                                            <i class="fas fa-chart-pie"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Block Rate</p>
                                            <h4 class="card-title"><?php echo $rate." %"?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="card-title">Attack Management</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="blockTable" class="display table table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Attack Type</th>
                                        <th>Source IP</th>
                                        <th>Target URL</th>
                                        <th>Status</th>
                                        <?php if($role != 3) {?>

                                        <th style="width: 15%;">Action</th>
                                        <?php } ?>
                                    </tr>
                                    </thead>
                                    <tbody id="blockTableBody">
                                    <?php foreach ($toblock as $row) { ?>
                                    <tr data-traffic-id="<?php echo $row["id"] ?>" data-source-ip="<?php echo $row["source_ip"] ?>">
                                        <td><?php echo $row["timestamp"] ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo ($row["type"] === 'SQL Injection') ? 'danger' : 
                                                     (($row["type"] === 'XSS') ? 'warning' : 
                                                     (($row["type"] === 'Command Injection') ? 'danger' : 'info'));
                                            ?>">
                                                <?php echo $row["type"] ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row["source_ip"] ?></td>
                                        <td><span class="url-truncate" title="<?php echo htmlspecialchars($row["url"]) ?>"><?php echo htmlspecialchars(substr($row["url"], 0, 50)) . (strlen($row["url"]) > 50 ? '...' : '') ?></span></td>
                                        <td>
                                            <span class="badge block-status badge-<?php 
                                                echo $row["blocked"] ? 'success' : 'danger';
                                            ?>">
                                                <?php echo $row["blocked"] ? 'Blocked' : 'Not Blocked'; ?>
                                            </span>
                                        </td>
                                        <?php if($role != 3) {?>

                                        <td>
                                            <div class="form-button-action d-flex justify-content-center gap-2">
                                                <?php if (!$row["blocked"]) { ?>
                                                <button type="button" class="btn btn-sm btn-link btn-primary block-btn" 
                                                        data-bs-toggle="tooltip" title="Block Attack" 
                                                        data-traffic-id="<?php echo $row["id"] ?>"
                                                        data-source-ip="<?php echo $row["source_ip"] ?>">
                                                    <i class="fa fa-lock"></i>
                                                </button>
                                                <?php } else { ?>
                                                <button type="button" class="btn btn-sm btn-link btn-warning unblock-btn" 
                                                        data-bs-toggle="tooltip" title="Unblock Attack" 
                                                        data-traffic-id="<?php echo $row["id"] ?>"
                                                        data-source-ip="<?php echo $row["source_ip"] ?>">
                                                    <i class="fa fa-unlock"></i>
                                                </button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center" style="margin-top: 20px;">
                                <div>
                                    <label for="blockRowsPerPage">Show:</label>
                                    <select id="blockRowsPerPage" class="form-select d-inline-block" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span>rows</span>
                                </div>
                                <nav>
                                    <ul id="blockPagination" class="pagination mb-0">
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="footer">
            <div class="container-fluid d-flex justify-content-between">
                <nav class="pull-left">
                    <ul class="nav">
                        <li class="nav-item"><a class="nav-link" href="#">ThemeKita</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Help</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Licenses</a></li>
                    </ul>
                </nav>
                <div class="copyright">2024, made with <i class="fa fa-heart heart text-danger"></i> by ThemeKita</div>
            </div>
        </footer>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/plugin/chart.js/chart.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="assets/js/demo.js"></script>

<script>
$(document).ready(function() {
    const tableBody = $('#blockTableBody');
    const allRows = tableBody.find('tr').clone();
    const rowsPerPageSelect = $('#blockRowsPerPage');
    const paginationContainer = $('#blockPagination');
    const noResults = $('#noBlockResults');
    
    let currentPage = 1;
    let rowsPerPage = 25;
    let filteredRows = [...allRows];

    // Initialize tooltips and event listeners on page load
    function initializeElements() {
        $('[data-bs-toggle="tooltip"]').tooltip();
        attachEventListeners();
    }

    // Search functionality

    // Rows per page functionality
    rowsPerPageSelect.on('change', function() {
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        updateTable();
    });

    // Update table display
    function updateTable() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);

        // Clear table body
        tableBody.empty();

        if (totalRows === 0) {
            noResults.show();
            paginationContainer.empty();
            return;
        }

        noResults.hide();

        // Display rows for current page
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        const rowsToDisplay = filteredRows.slice(startIndex, endIndex);

        rowsToDisplay.forEach(function(row) {
            tableBody.append($(row).clone());
        });

        // Reinitialize event listeners for new rows
        initializeElements();

        // Update pagination
        updatePagination(totalPages);
    }

    // Update pagination controls
    function updatePagination(totalPages) {
        paginationContainer.empty();

        if (totalPages <= 1) return;

        // Previous button
        const prevBtn = $('<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="prev">Previous</a></li>');
        paginationContainer.append(prevBtn);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = $('<li class="page-item ' + (i === currentPage ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
            paginationContainer.append(pageBtn);
        }

        // Next button
        const nextBtn = $('<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="next">Next</a></li>');
        paginationContainer.append(nextBtn);

        // Pagination click handler
        paginationContainer.find('a').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');

            if (page === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (page === 'next' && currentPage < totalPages) {
                currentPage++;
            } else if (typeof page === 'number' || !isNaN(page)) {
                currentPage = parseInt(page);
            }

            updateTable();
        });
    }

    // Attach event listeners to action buttons - using event delegation
    function attachEventListeners() {
        // Remove previous listeners to avoid duplicates
        tableBody.off('click', '.block-btn');
        tableBody.off('click', '.unblock-btn');
        
        // Block button
        tableBody.on('click', '.block-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const trafficId = $(this).data('traffic-id');
            const sourceIp = $(this).data('source-ip');
            console.log('Block clicked:', trafficId, sourceIp);
            blockAttack(trafficId, sourceIp, $(this).closest('tr'));
        });

        // Unblock button
        tableBody.on('click', '.unblock-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const trafficId = $(this).data('traffic-id');
            const sourceIp = $(this).data('source-ip');
            console.log('Unblock clicked:', trafficId, sourceIp);
            unblockAttack(trafficId, sourceIp, $(this).closest('tr'));
        });
    }

    // Block attack via AJAX
    function blockAttack(trafficId, sourceIp, row) {
        $.ajax({
            type: 'POST',
            url: './api/block_actions.php',
            data: {
                action: 'block',
                traffic_id: trafficId,
                source_ip: sourceIp
            },
            dataType: 'json',
            success: function(response) {
                console.log('Block response:', response);
                if (response.success) {
                    // Update the badge in the row
                    const badge = row.find('.block-status');
                    const actionBtns = row.find('.form-button-action');

                    badge.removeClass('badge-danger').addClass('badge-success');
                    badge.text('Blocked');

                    // Replace button with unblock button
                    actionBtns.html(`
                        <button type="button" class="btn btn-sm btn-link btn-warning unblock-btn" 
                                data-bs-toggle="tooltip" title="Unblock Attack" 
                                data-traffic-id="${trafficId}"
                                data-source-ip="${sourceIp}">
                            <i class="fa fa-unlock"></i>
                        </button>
                    `);

                    // Show success message
                    showNotification(response.message, 'success');

                    // Reinitialize tooltips
                    attachEventListeners();
                    $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                    $('[data-bs-toggle="tooltip"]').tooltip();
                } else {
                    showNotification(response.message || 'Failed to block attack', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                showNotification('Failed to block attack. Server error.', 'error');
            }
        });
    }

    // Unblock attack via AJAX
    function unblockAttack(trafficId, sourceIp, row) {
        $.ajax({
            type: 'POST',
            url: './api/block_actions.php',
            data: {
                action: 'unblock',
                traffic_id: trafficId,
                source_ip: sourceIp
            },
            dataType: 'json',
            success: function(response) {
                console.log('Unblock response:', response);
                if (response.success) {
                    // Update the badge in the row
                    const badge = row.find('.block-status');
                    const actionBtns = row.find('.form-button-action');

                    badge.removeClass('badge-success').addClass('badge-danger');
                    badge.text('Not Blocked');

                    // Replace button with block button
                    actionBtns.html(`
                        <button type="button" class="btn btn-sm btn-link btn-primary block-btn" 
                                data-bs-toggle="tooltip" title="Block Attack" 
                                data-traffic-id="${trafficId}"
                                data-source-ip="${sourceIp}">
                            <i class="fa fa-lock"></i>
                        </button>
                    `);

                    // Show success message
                    showNotification(response.message, 'success');

                    // Reinitialize tooltips
                    attachEventListeners();
                    $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                    $('[data-bs-toggle="tooltip"]').tooltip();
                } else {
                    showNotification(response.message || 'Failed to unblock attack', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                showNotification('Failed to unblock attack. Server error.', 'error');
            }
        });
    }

    // Show notification message
    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        
        const alertElement = $(alertHtml);
        $('body').prepend(alertElement);
        
        setTimeout(function() {
            alertElement.alert('close');
        }, 4000);
    }

    // Initial table load
    updateTable();
});
</script>

<script>
    var attackChart = document.getElementById("attackChart").getContext("2d");
    new Chart(attackChart, {
        type: "doughnut",
        data: {
            labels: ["SQL Injection", "XSS", "Path Traversal", "Command Injection", "Others"],
            datasets: [
                {
                    data: [350, 280, 220, 150, 234],
                    backgroundColor: ["#f3545d", "#fdaf4b", "#ffaa00", "#ff6b6b", "#ffc0cb"],
                    borderWidth: 0,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        fontColor: "rgb(154, 154, 154)",
                        fontSize: 11,
                        padding: 20,
                    }
                }
            }
        }
    });

    var blockedChart = document.getElementById("blockedChart").getContext("2d");
    new Chart(blockedChart, {
        type: "bar",
        data: {
            labels: ["00:00", "04:00", "08:00", "12:00", "16:00", "20:00"],
            datasets: [
                {
                    label: "Blocked Attacks",
                    data: [12, 19, 25, 35, 28, 15],
                    backgroundColor: "#2dce89",
                    borderWidth: 0,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        fontColor: "rgb(154, 154, 154)",
                        fontSize: 12
                    }
                }
            }
        }
    });
</script>
</body>
</html>

