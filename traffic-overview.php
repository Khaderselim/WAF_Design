<?php
session_start();

// Database connection
require_once "db_connection.php";

// Initialize WAF - detects and blocks attacks
require_once "waf_init.php";
include "Class/Traffic.php";
require_once "export_csv.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Handle CSV export request
if (isset($_POST['export_csv'])) {
    exportTrafficLogCSV($conn, "traffic_log", "Traffic_Log_Export_");
}

$traffic = new Traffic();
$Incom =  $traffic->getTraffic();
$allowed = $traffic->getallowedtraffic();
$blocked = $traffic->getblockedtraffic();
$block_rate = $traffic->Blockrate();
$topurl = $traffic->topurl();
$hourlyTraffic = $traffic->getHourlyTraffic();
$username = $_SESSION['username'];
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$role = $_SESSION['role_id'] ;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Traffic Overview - WAF Dashboard</title>
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
                    <li class="nav-item active">
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
                    <li class="nav-item">
                        <a  href="blocked-attacks.php">
                            <i class="fas fa-shield-alt"></i>
                            <p>Blocked Attacks</p>
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
                            <input type="text" placeholder="Search ..." class="form-control" />
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
                <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4 justify-content-between">
                    <div>
                        <h3 class="fw-bold mb-3">Traffic Overview</h3>
                        <p class="text-muted">Real-time traffic analysis and statistics</p>
                    </div>
                    <form method="POST" style="display: inline; align-self: center;">
                        <button type="submit" name="export_csv" value="1" class="btn btn-primary btn-sm">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </form>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                                            <i class="fas fa-arrow-up"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Incoming Requests</p>
                                            <h4 class="card-title"><?php echo $Incom->rowCount()?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-success bubble-shadow-small">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Allowed</p>
                                            <h4 class="card-title"><?php echo $allowed->rowCount()?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-danger bubble-shadow-small">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Blocked</p>
                                            <h4 class="card-title"><?php echo $blocked->rowCount()?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-info bubble-shadow-small">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Block Rate</p>
                                            <h4 class="card-title"><?php echo $block_rate." %"?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Hourly Traffic Distribution</div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="trafficChart" style="width: 100%; height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="card-title">Top Requested URLs</div>

                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>URL</th>
                                            <th>Hits</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php while ($row = $topurl->fetch()){ ?>
                                        <tr>
                                            <td><?php echo $row['url']?></td>
                                            <td><?php echo $row['count']?></td>
                                        </tr>
                                    <?php } ?>

                                    </tbody>
                                </table>
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
    // Get hourly traffic data from PHP
    var hourlyData = <?php echo json_encode($hourlyTraffic); ?>;
    
    var trafficChart = document.getElementById("trafficChart").getContext("2d");
    var myTrafficChart = new Chart(trafficChart, {
        type: "line",
        data: {
            labels: hourlyData.labels,
            datasets: [
                {
                    label: "Requests",
                    data: hourlyData.data,
                    borderColor: "#1d7af3",
                    backgroundColor: "rgba(29, 122, 243, 0.1)",
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
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

