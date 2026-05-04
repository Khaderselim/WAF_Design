<?php

/**
 * Export traffic log data to CSV file
 * 
 * @param mysqli $connection Database connection
 * @param string $tableName The table to export from
 * @param string $filename The name of the export file
 * @return void Outputs CSV file for download
 */
function exportTrafficLogCSV($connection, $tableName = "traffic_log", $filename = "Traffic_Log_Export_") {
    // Check if connection is valid
    if (!$connection || !$connection instanceof mysqli) {
        echo "Error: Database connection is not valid.";
        exit;
    }
    
    // Fetch records from the database table
    $Output = "";
    $strSQL = "SELECT * FROM " . $tableName;
    $sql = mysqli_query($connection, $strSQL);
    
    // If the database query encounters an error, display the error message
    if (mysqli_error($connection)) {
        echo "Error: " . mysqli_error($connection);
        exit;
    }
    
    // Determine the number of data columns in the table
    $columns_total = mysqli_num_fields($sql);
    
    // Get the name of the data columns for the header row
    for ($i = 0; $i < $columns_total; $i++) {
        $Heading = mysqli_fetch_field_direct($sql, $i);
        $Output .= '"' . $Heading->name . '",';
    }
    $Output .= "\n";
    
    // Loop through each record in the table and read the data value from each column
    while ($row = mysqli_fetch_array($sql)) {
        for ($i = 0; $i < $columns_total; $i++) {
            $Output .= '"' . $row["$i"] . '",';
        }
        $Output .= "\n";
    }
    
    // Create the export file with timestamp
    $TimeNow = date("YmdHis");
    $filenameWithTimestamp = $filename . $TimeNow . ".csv";
    
    // Set headers for CSV download
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename=' . $filenameWithTimestamp);
    
    // Output the CSV data
    echo $Output;
    exit;
}
?>