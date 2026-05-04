<?php
session_start();
require_once "waf_init.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$role     = $_SESSION['role_id'];
$username = $_SESSION['username'];
$email    = $_SESSION['email'] ?? '';

$uploadMessage = '';
$uploadStatus  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $file      = $_FILES['upload_file'];
    $uploadDir = 'uploads/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $maxFileSize        = 50 * 1024 * 1024;
    $allowedExtensions  = ['csv'];

    if ($file['size'] > $maxFileSize) {
        $uploadStatus  = 'error';
        $uploadMessage = 'File size exceeds 50MB limit.';
    } else {
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExtensions)) {
            $uploadStatus  = 'error';
            $uploadMessage = 'File type not allowed. Allowed: ' . implode(', ', $allowedExtensions);
        } else {
            $filePath = $uploadDir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $conn->prepare("INSERT INTO uploaded_file (file_name, file_type, file_size, upload_path) VALUES (?, ?, ?, ?)");
                $originalFileName = $file['name'];
                $fileType         = $file['type'];
                $fileSize         = $file['size'];
                $stmt->bind_param('ssis', $originalFileName, $fileType, $fileSize, $filePath );
                if ($stmt->execute()) {
                    $uploadStatus  = 'success';
                    $uploadMessage = 'File uploaded successfully.';
                } else {
                    $uploadStatus  = 'error';
                    $uploadMessage = 'Database error: ' . $stmt->error;
                    unlink($filePath);
                }
                $stmt->close();
            } else {
                $uploadStatus  = 'error';
                $uploadMessage = 'Failed to move uploaded file.';
            }
        }
    }
    header("Location: upload-files.php");
    exit();
}

$uploadedFiles = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM uploaded_file ORDER BY id DESC");
    if ($result) $uploadedFiles = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Upload Files - WAF Dashboard</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: { families: ["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"], urls: ["assets/css/fonts.min.css"] },
            active: function() { sessionStorage.fonts = true; }
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
                <a href="dashboard.php" class="logo"><img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" /></a>
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
                    <li class="nav-item"><a href="dashboard.php"><i class="fas fa-home"></i><p>Dashboard</p></a></li>
                    <li class="nav-section"><span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span><h4 class="text-section">Components</h4></li>
                    <li class="nav-item"><a href="traffic-overview.php"><i class="fas fa-chart-line"></i><p>Traffic Overview</p></a></li>
                    <li class="nav-item"><a href="security-alerts.php"><i class="fas fa-exclamation-triangle"></i><p>Security Alerts</p></a></li>
                    <li class="nav-item"><a href="blocked-attacks.php"><i class="fas fa-shield-alt"></i><p>Blocked Attacks</p></a></li>
                    <li class="nav-item active"><a href="upload-files.php"><i class="fas fa-cloud-upload-alt"></i><p>Upload Files</p></a></li>
                    <?php if ($role == 1): ?>
                        <li class="nav-item"><a href="settings.php"><i class="fas fa-cog"></i><p>Settings</p></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-panel">
        <div class="main-header">
            <div class="main-header-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="dashboard.php" class="logo"><img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" /></a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                        <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                    </div>
                    <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                </div>
            </div>
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#">
                                <div class="avatar-sm"><img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" /></div>
                                <span class="profile-username"><span class="op-7">Hi,</span> <span class="fw-bold"><?= htmlspecialchars($username) ?></span></span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li><div class="user-box"><div class="u-text"><h4><?= htmlspecialchars($username) ?></h4><p class="text-muted"><?= htmlspecialchars($email) ?></p></div></div></li>
                                    <li><div class="dropdown-divider"></div><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="page-inner">
                <div class="pt-2 pb-4">
                    <h3 class="fw-bold mb-1">Upload Files</h3>
                    <p class="text-muted">Upload a CSV file and analyze it.</p>
                </div>

                <?php if (!empty($uploadMessage)): ?>
                    <div class="alert alert-<?= $uploadStatus === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                        <?= htmlspecialchars($uploadMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Upload form -->
                <div class="card mb-4">
                    <div class="card-header"><h4 class="card-title">Upload New File</h4></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select File</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="upload_file" id="upload_file" required>
                                    <button class="btn btn-primary" type="submit"><i class="fas fa-upload me-1"></i>Upload</button>
                                </div>
                                <small class="text-muted">Max 50MB — CSV</small>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Files table -->
                <div class="card mb-4">
                    <div class="card-header"><h4 class="card-title">Uploaded Files</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($uploadedFiles)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No files uploaded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($uploadedFiles as $file): ?>
                                        <tr>
                                            <td><i class="fas fa-file me-2"></i><?= htmlspecialchars($file['file_name']) ?></td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($file['file_type']) ?></span></td>
                                            <td><?php
                                                $s = $file['file_size'];
                                                echo $s >= 1048576 ? round($s/1048576,2).' MB' : ($s >= 1024 ? round($s/1024,2).' KB' : $s.' B');
                                                ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary analyze-btn" data-file-path="<?= htmlspecialchars($file['upload_path']) ?>" data-file-name="<?= htmlspecialchars($file['file_name']) ?>">
                                                    <i class="fas fa-search me-1"></i>Analyze
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-file" data-file-id="<?= $file['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CSV Table Display -->
                <div class="card mb-4" id="analyzePanel" style="display:none;">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h4 class="card-title mb-0" id="tablePanelTitle">CSV Data</h4>
                            <button class="btn btn-sm btn-outline-secondary" id="cancelAnalyze">Close</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="fileTableContainer">
                                <p class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="footer">
            <div class="container-fluid d-flex justify-content-between">
                <div class="copyright">WAF Dashboard</div>
            </div>
        </footer>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<script>
    $('.analyze-btn').on('click', function(e) {
        e.preventDefault();
        const filePath = $(this).data('file-path');
        const fileName = $(this).data('file-name');

        $('#tablePanelTitle').text(fileName);
        $('#analyzePanel').show();
        $('#fileTableContainer').html(
            '<p class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</p>'
        );

        $.post('api/process-csv.php', { file_path: filePath }, function(html) {
            $('#fileTableContainer').html(html);
            initTablePagination();
            attachScanHandler();   // ← attach after content loads
        }).fail(function() {
            $('#fileTableContainer').html('<p class="text-danger">Error loading file</p>');
        });

        $('html, body').animate({ scrollTop: $('#analyzePanel').offset().top - 20 }, 400);
    });

    function attachScanHandler() {
        $('#fileTableContainer').off('click', '.classify-url-btn').on('click', '.classify-url-btn', function() {
            const btn     = $(this);
            const row     = btn.closest('tr');
            const resultCell = row.find('.attack-result-cell');
            const url     = btn.data('url');

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.post('api/classify-url.php', { url: url }, function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-search me-1"></i>Scan');

                if (res.error) {
                    resultCell.html('<span class="badge bg-secondary" title="' + res.error + '">Error</span>');
                    return;
                }

                if (!res.attack_types || res.attack_types.length === 0) {
                    resultCell.html('<span class="badge bg-success">Clean</span>');
                    return;
                }

                const badges = res.attack_types.map(function(a) {
                    const label     = a.label.replace('is_', '').toUpperCase();
                    const score     = Math.round(a.score * 100);
                    const badgeClass = label === 'CMDI' ? 'bg-danger'
                        : label === 'SQLI' ? 'bg-warning text-dark'
                            : 'bg-info text-dark';
                    return `<span class="badge ${badgeClass} me-1" title="Score: ${score}%">${label} ${score}%</span>`;
                }).join('');

                resultCell.html(badges);
            }, 'json').fail(function(xhr) {
                console.log('Status:', xhr.status);
                console.log('Raw response:', xhr.responseText);  // ← this tells you exactly what's wrong
                resultCell.html('<span class="badge bg-danger" title="' + xhr.responseText.substring(0, 100) + '">Failed</span>');
            });
        });
    }

    function initTablePagination() {
        const tableBody        = $('#alertsTableBody');
        const allRows          = tableBody.find('tr').clone();
        const rowsPerPageSelect = $('#rowsPerPage');
        const paginationContainer = $('#pagination');
        const noResults        = $('#noResults');

        let currentPage = 1;
        let rowsPerPage = parseInt(rowsPerPageSelect.val()) || 25;
        let filteredRows = [...allRows];

        rowsPerPageSelect.off('change').on('change', function() {
            rowsPerPage = parseInt($(this).val());
            currentPage = 1;
            updateTable();
        });

        function updateTable() {
            const totalRows  = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            tableBody.empty();

            if (totalRows === 0) {
                noResults.show();
                paginationContainer.empty();
                return;
            }

            noResults.hide();

            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex   = startIndex + rowsPerPage;
            filteredRows.slice(startIndex, endIndex).forEach(function(row) {
                tableBody.append($(row).clone());
            });

            updatePagination(totalPages);
        }

        function updatePagination(totalPages) {
            paginationContainer.empty();
            if (totalPages <= 1) return;

            const prevDisabled = currentPage === 1 ? 'disabled' : '';
            const nextDisabled = currentPage === totalPages ? 'disabled' : '';

            paginationContainer.append(
                `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" data-page="prev">Previous</a></li>`
            );
            for (let i = 1; i <= totalPages; i++) {
                paginationContainer.append(
                    `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`
                );
            }
            paginationContainer.append(
                `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" data-page="next">Next</a></li>`
            );

            paginationContainer.find('a').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page === 'prev' && currentPage > 1) currentPage--;
                else if (page === 'next' && currentPage < totalPages) currentPage++;
                else if (!isNaN(page)) currentPage = parseInt(page);
                updateTable();
            });
        }


        updateTable();
    }
    $('#cancelAnalyze').on('click', function() {
        $('#analyzePanel').hide();
    });

    $('#upload_file').on('change', function() {
        if (this.files[0] && this.files[0].size > 50 * 1024 * 1024) {
            alert('File size exceeds 50MB limit.');
            this.value = '';
        }
    });

    $('.delete-file').on('click', function(e) {
        e.stopPropagation();
        const fileId = $(this).data('file-id');
        if (!confirm('Delete this file?')) return;
        $.post('api/file_actions.php', { action: 'delete', file_id: fileId }, function(res) {
            if (res.success) location.reload();
            else alert('Error: ' + res.message);
        }, 'json');
    });

</script>
</body>
</html>