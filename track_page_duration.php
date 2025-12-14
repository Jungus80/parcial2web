<?php
require_once 'clases/Tracker.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');
    error_log("Track Page Duration - Raw POST data: " . $rawData);
    $input = json_decode($rawData, true);
    $metId = $input['met_id'] ?? 0;
    $permanence = $input['permanence'] ?? 0;

    error_log("Track Page Duration - Processed data: met_id=" . $metId . ", permanence=" . $permanence);

    if ($metId > 0 && $permanence >= 0) {
        $tracker = new Tracker();
        $permanenceInSeconds = round($permanence / 1000);
        $success = $tracker->updatePageVisitTime($metId, $permanenceInSeconds);
        if ($success) {
            error_log("Page permanence update successful for metId: " . $metId . " with permanence: " . $permanenceInSeconds . "s");
            echo json_encode(['status' => 'success']);
        } else {
            error_log("Page permanence update failed for metId: " . $metId . " with permanence: " . $permanenceInSeconds . "s");
            echo json_encode(['status' => 'error', 'message' => 'Failed to update page permanence']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
