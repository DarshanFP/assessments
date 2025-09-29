<?php
function logActivityToFile($message, $type = "info") {
    $logFile = realpath(dirname(__FILE__) . '/../logs/activity.log');
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;

    // Check if the resolved path is correct
    if (!$logFile) {
        error_log("Logger Error: Failed to resolve the path to activity.log. Check the path.");
        $logFile = dirname(__FILE__) . '/../logs/activity.log'; // Fallback to relative path
    }

    // Check if the logs directory exists
    if (!is_dir(dirname($logFile))) {
        error_log("Logger Error: Logs directory does not exist: " . dirname($logFile));
        return;
    }

    // Check if the file is writable
    if (is_writable($logFile)) {
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    } else {
        error_log("Logger Error: Activity log file is not writable: $logFile");
    }
}
