<?php
session_start();
require_once "waf_init.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$email    = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Settings - WAF Dashboard</title>
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
    <style>
        .section-card { scroll-margin-top: 20px; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .rule-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid #f0f0f0; }
        .rule-toggle-row:last-child { border-bottom: none; }
        .badge-role-admin    { background:#dc3545; }
        .badge-role-analyst  { background:#fd7e14; }
        .badge-role-viewer   { background:#6c757d; }
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
                    <li class="nav-item"><a href="dashboard.php"><i class="fas fa-home"></i><p>Dashboard</p></a></li>
                    <li class="nav-section"><span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span><h4 class="text-section">Components</h4></li>
                    <li class="nav-item"><a href="traffic-overview.php"><i class="fas fa-chart-line"></i><p>Traffic Overview</p></a></li>
                    <li class="nav-item"><a href="security-alerts.php"><i class="fas fa-exclamation-triangle"></i><p>Security Alerts</p></a></li>
                    <li class="nav-item"><a href="blocked-attacks.php"><i class="fas fa-shield-alt"></i><p>Blocked Attacks</p></a></li>
                    <li class="nav-item"><a href="upload-files.php"><i class="fas fa-cloud-upload-alt"></i><p>Upload Files</p></a></li>
                    <li class="nav-item active"><a href="settings.php"><i class="fas fa-cog"></i><p>Settings</p></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-panel">
        <!-- Header -->
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
                    <h3 class="fw-bold mb-1">Settings</h3>
                    <p class="text-muted">Configure WAF protection rules and preferences</p>
                </div>

                <!-- Toast notifications -->
                <div class="toast-container" id="toastContainer"></div>

                <div class="row">
                    <!-- Sidebar Nav -->
                    <div class="col-md-3">
                        <div style="position:sticky;top:20px;">
                            <div class="list-group">
                                <a href="#rules"       class="list-group-item list-group-item-action active settings-nav-link" data-section="rules"><i class="fas fa-list-check me-2"></i>Detection Rules</a>
                                <a href="#ip-block"    class="list-group-item list-group-item-action settings-nav-link"        data-section="ip-block"><i class="fas fa-ban me-2"></i>IP Block</a>
                                <a href="#user-mgmt"   class="list-group-item list-group-item-action settings-nav-link"        data-section="user-mgmt"><i class="fas fa-users me-2"></i>User Management</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9">

                        <!-- ── 1. DETECTION RULES ──────────────────────────────── -->
                        <div id="rules" class="card mb-4 section-card">
                            <div class="card-header"><div class="card-title">Detection Rules</div></div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Enable or disable individual attack detection rules.</p>

                                <div class="rule-toggle-row">
                                    <div>
                                        <strong><i class="fas fa-database text-danger me-2"></i>SQL Injection (SQLi)</strong>
                                        <p class="text-muted small mb-0">Detects SQL injection patterns in requests</p>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input rule-toggle" type="checkbox" id="rule-sqli" data-type="is_sqli" style="width:2.5em;height:1.3em">
                                    </div>
                                </div>

                                <div class="rule-toggle-row">
                                    <div>
                                        <strong><i class="fas fa-code text-warning me-2"></i>Cross-Site Scripting (XSS)</strong>
                                        <p class="text-muted small mb-0">Detects XSS payloads in URL and form inputs</p>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input rule-toggle" type="checkbox" id="rule-xss" data-type="is_xss" style="width:2.5em;height:1.3em">
                                    </div>
                                </div>

                                <div class="rule-toggle-row">
                                    <div>
                                        <strong><i class="fas fa-terminal text-danger me-2"></i>Command Injection (CMDi)</strong>
                                        <p class="text-muted small mb-0">Detects OS command injection attempts</p>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input rule-toggle" type="checkbox" id="rule-cmdi" data-type="is_cmdi" style="width:2.5em;height:1.3em">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── 3. IP BLOCK ─────────────────────────────────────── -->
                        <div id="ip-block" class="card mb-4 section-card">
                            <div class="card-header"><div class="card-title">IP Block</div></div>
                            <div class="card-body">

                                <!-- Add IP form -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <input type="text" id="newIp" class="form-control" placeholder="e.g. 192.168.1.1">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" id="blockReason" class="form-control" placeholder="Reason (optional)">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" id="blockDuration" class="form-control" placeholder="Hours" value="24" min="1">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="blockPermanent">
                                            <label class="form-check-label small" for="blockPermanent">Perm</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-danger w-100" id="addBlockBtn">
                                            <i class="fas fa-ban me-1"></i>Block IP
                                        </button>
                                    </div>
                                </div>

                                <!-- Blocked IPs table -->
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped" id="blockedIpTable">
                                        <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Reason</th>
                                            <th>Blocked At</th>
                                            <th>Expires</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody id="blockedIpBody">
                                        <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ── 4. USER MANAGEMENT ──────────────────────────────── -->
                        <div id="user-mgmt" class="card mb-4 section-card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="card-title mb-0">User Management</div>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addUserForm">
                                        <i class="fas fa-plus me-1"></i>Add User
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">

                                <!-- Add user form (collapsed by default) -->
                                <div class="collapse mb-4" id="addUserForm">
                                    <div class="card card-body bg-light border mb-3">
                                        <h6 class="mb-3">New User</h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="text" id="newUsername" class="form-control" placeholder="Username">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="email" id="newEmail" class="form-control" placeholder="Email">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="password" id="newPassword" class="form-control" placeholder="Password (min 6 chars)">
                                            </div>
                                            <div class="col-md-4">
                                                <select id="newRole" class="form-select">
                                                    <option value="">Select Role</option>
                                                    <option value="1">Admin</option>
                                                    <option value="2">Analyst</option>
                                                    <option value="3">Viewer</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-success w-100" id="addUserBtn">
                                                    <i class="fas fa-user-plus me-1"></i>Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Users table -->
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped" id="usersTable">
                                        <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Created</th>
                                            <th>Last Login</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody id="usersBody">
                                        <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div><!-- end col-md-9 -->
                </div><!-- end row -->
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
    const API = './api/settings_actions.php';

    // ── Helpers ──────────────────────────────────────────────────────────────────
    function apiCall(action, extra = {}) {
        return fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...extra })
        }).then(r => r.json());
    }

    function toast(message, type = 'success') {
        const id  = 'toast_' + Date.now();
        const bg  = type === 'success' ? 'bg-success' : 'bg-danger';
        const html = `
        <div id="${id}" class="toast align-items-center text-white ${bg} border-0 mb-2" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
        $('#toastContainer').append(html);
        const el = new bootstrap.Toast(document.getElementById(id), { delay: 4000 });
        el.show();
        setTimeout(() => $('#' + id).remove(), 5000);
    }

    // ── 2. Detection Rules ───────────────────────────────────────────────────────
    function loadRules() {
        apiCall('get_rules').then(res => {
            if (!res.success) return;
            res.data.forEach(rule => {
                const typeMap = { is_sqli: 'sqli', is_xss: 'xss', is_cmdi: 'cmdi' };
                const id = '#rule-' + typeMap[rule.type];
                $(id).prop('checked', rule.is_active == 1);
            });
        });
    }

    $('.rule-toggle').on('change', function() {
        const type   = $(this).data('type');
        const active = $(this).is(':checked') ? 1 : 0;
        apiCall('toggle_rule', { type, active }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (!res.success) $(this).prop('checked', !active); // revert on fail
        });
    });

    // ── 3. IP Block ──────────────────────────────────────────────────────────────
    function loadBlockedIps() {
        apiCall('get_blocked_ips').then(res => {
            const tbody = $('#blockedIpBody');
            if (!res.success || !res.data.length) {
                tbody.html('<tr><td colspan="6" class="text-center text-muted">No blocked IPs</td></tr>');
                return;
            }
            tbody.html(res.data.map(row => {
                const statusBadge = row.status === 'Permanent' ? 'danger' :
                    row.status === 'Active'    ? 'success' : 'secondary';
                return `<tr>
                <td><code>${row.ip}</code></td>
                <td>${row.reason || '—'}</td>
                <td>${row.blocked_at}</td>
                <td>${row.expires_at || '<span class="badge bg-danger">Never</span>'}</td>
                <td><span class="badge bg-${statusBadge}">${row.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-success unblock-btn" data-ip="${row.ip}">
                        <i class="fas fa-unlock me-1"></i>Unblock
                    </button>
                </td>
            </tr>`;
            }).join(''));
        });
    }

    $('#addBlockBtn').on('click', function() {
        const ip          = $('#newIp').val().trim();
        const reason      = $('#blockReason').val().trim() || 'Manual block via settings';
        const duration    = parseInt($('#blockDuration').val()) || 24;
        const is_permanent= $('#blockPermanent').is(':checked') ? 1 : 0;

        if (!ip) { toast('Please enter an IP address', 'error'); return; }

        apiCall('block_ip', { ip, reason, duration, permanent: is_permanent }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                $('#newIp, #blockReason').val('');
                $('#blockDuration').val('24');
                $('#blockPermanent').prop('checked', false);
                loadBlockedIps();
            }
        });
    });

    $(document).on('click', '.unblock-btn', function() {
        const ip = $(this).data('ip');
        if (!confirm(`Unblock IP ${ip}?`)) return;
        apiCall('unblock_ip', { ip }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) loadBlockedIps();
        });
    });

    // Toggle permanent checkbox disables duration input
    $('#blockPermanent').on('change', function() {
        $('#blockDuration').prop('disabled', $(this).is(':checked'));
    });

    // ── 4. User Management ───────────────────────────────────────────────────────
    function loadUsers() {
        apiCall('get_users').then(res => {
            const tbody = $('#usersBody');
            if (!res.success || !res.data.length) {
                tbody.html('<tr><td colspan="7" class="text-center text-muted">No users found</td></tr>');
                return;
            }
            tbody.html(res.data.map(u => {
                const roleColors  = { admin: 'danger', analyst: 'warning', viewer: 'secondary' };
                const roleColor   = roleColors[u.role] || 'secondary';
                const statusBadge = u.is_active == 1 ? 'success' : 'secondary';
                const statusText  = u.is_active == 1 ? 'Active'  : 'Inactive';
                const toggleLabel = u.is_active == 1 ? 'Deactivate' : 'Activate';
                const toggleActive= u.is_active == 1 ? 0 : 1;
                return `<tr>
                <td><strong>${u.username}</strong></td>
                <td>${u.email}</td>
                <td>
                    <select class="form-select form-select-sm change-role-select"
                            data-uid="${u.id}" style="width:110px">
                        <option value="1" ${u.role_id == 1 ? 'selected' : ''}>Admin</option>
                        <option value="2" ${u.role_id == 2 ? 'selected' : ''}>Analyst</option>
                        <option value="3" ${u.role_id == 3 ? 'selected' : ''}>Viewer</option>
                    </select>
                </td>
                <td>${u.created_at ? u.created_at.split(' ')[0] : '—'}</td>
                <td>${u.last_login ? u.last_login.split(' ')[0] : '<span class="text-muted">Never</span>'}</td>
                <td><span class="badge bg-${statusBadge}">${statusText}</span></td>
                <td class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary toggle-user-btn"
                            data-uid="${u.id}" data-active="${toggleActive}"
                            title="${toggleLabel}">
                        <i class="fas fa-${u.is_active == 1 ? 'user-slash' : 'user-check'}"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-user-btn"
                            data-uid="${u.id}" data-username="${u.username}"
                            title="Delete user">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
            }).join(''));
        });
    }

    $('#addUserBtn').on('click', function() {
        const username = $('#newUsername').val().trim();
        const email    = $('#newEmail').val().trim();
        const password = $('#newPassword').val();
        const role_id  = $('#newRole').val();

        apiCall('add_user', { username, email, password, role_id: parseInt(role_id) }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                $('#newUsername, #newEmail, #newPassword').val('');
                $('#newRole').val('');
                loadUsers();
            }
        });
    });

    $(document).on('click', '.toggle-user-btn', function() {
        const user_id   = $(this).data('uid');
        const is_active = $(this).data('active');
        apiCall('toggle_user', { user_id, is_active }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) loadUsers();
        });
    });

    $(document).on('change', '.change-role-select', function() {
        const user_id = $(this).data('uid');
        const role_id = $(this).val();
        apiCall('change_role', { user_id, role_id: parseInt(role_id) }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) loadUsers();
        });
    });

    $(document).on('click', '.delete-user-btn', function() {
        const user_id  = $(this).data('uid');
        const username = $(this).data('username');
        if (!confirm(`Delete user "${username}"? This cannot be undone.`)) return;
        apiCall('delete_user', { user_id }).then(res => {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) loadUsers();
        });
    });

    document.querySelectorAll('.settings-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            const el = document.getElementById(this.dataset.section);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    $(document).ready(function() {
        loadRules();
        loadBlockedIps();
        loadUsers();
    });
</script>
</body>
</html>