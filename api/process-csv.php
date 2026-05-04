<?php
session_start();
require_once "../waf_init.php";

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    die('Unauthorized');
}

// Validate and sanitize the file path
$filePath = $_POST['file_path'] ?? '';
$uploadDir = realpath('../uploads/');
$realPath  = realpath('../' . $filePath);
$urlColumnIndex= 'url';

if (!$realPath || strpos($realPath, $uploadDir) !== 0 || !file_exists($realPath)) {
    die('<p class="text-danger">Error: Invalid or missing file path.</p>');
}

$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
if (!in_array($ext, ['csv'])) {
    die('<p class="text-danger">Error: Only CSV files can be previewed.</p>');
}

if (($handle = fopen($realPath, "r")) === FALSE) {
    die('<p class="text-danger">Error: Could not open the file.</p>');
}
?>

<div class="table-responsive">
    <table id="alertsTable" class="table table-striped table-hover w-100 mb-0">
        <?php
        // Header row
        // Header — add two columns at the end
        if (($headers = fgetcsv($handle, 0, ",", '"', "\\")) !== FALSE) {
            foreach ($headers as $i => $header) {
                if (stripos($header, 'url') !== false) {
                    $urlColumnIndex = $i;
                    break;
                }
            }
            echo '<thead class="table-dark"><tr>';
            foreach ($headers as $header) {
                echo '<th class="text-nowrap">' . htmlspecialchars($header) . '</th>';
            }
            echo '<th class="text-nowrap">Scan</th>';
            echo '<th class="text-nowrap">Attack Result</th>';
            echo '</tr></thead>';
        }

        // Rows — two separate <td>s at the end
        echo '<tbody id="alertsTableBody">';
        $rowCount = 0;
        while (($data = fgetcsv($handle, 0, ",", '"', "\\")) !== FALSE) {
            echo '<tr>';
            foreach ($data as $i => $cell) {
                if ($i === $urlColumnIndex) {
                    $decoded = urldecode($cell);
                    echo '<td title="' . htmlspecialchars($decoded) . '">' . htmlspecialchars($decoded) . '</td>';
                } else {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
            }

            $rawUrl = $urlColumnIndex !== null ? ($data[$urlColumnIndex] ?? '') : '';

            // Scan button column
            echo '<td>
            <button class="btn btn-sm btn-outline-warning classify-url-btn" data-url="' . htmlspecialchars($rawUrl) . '">
                <i class="fas fa-search me-1"></i>Scan
            </button>
          </td>';

            // Result column — starts empty, filled by JS
            echo '<td class="attack-result-cell">
            <span class="text-muted" style="font-size:0.8rem;">—</span>
          </td>';

            echo '</tr>';
            $rowCount++;
        }
        echo '</tbody>';
        fclose($handle);
        ?>
    </table>
</div>

<div id="noResults" class="text-center text-muted py-3" style="display:none;">No results found.</div>

<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
    <div>
        <label for="rowsPerPage" class="me-1">Show:</label>
        <select id="rowsPerPage" class="form-select form-select-sm d-inline-block" style="width:auto;">
            <option value="5" selected>5</option>
            <option value="10" >10</option>
            <option value="25">25</option>
            <option value="100">100</option>
        </select>
        <span class="ms-1">rows per page</span>
    </div>
    <nav><ul id="pagination" class="pagination pagination-sm mb-0"></ul></nav>
</div>

<p class="text-muted mt-2">
    <small><strong><?= $rowCount ?> total rows</strong></small>
</p>