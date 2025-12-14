<?php
require_once 'clases/Tracker.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');
    error_log("Track Duration - Raw POST data: " . $rawData);
    $input = json_decode($rawData, true);
    $obsId = $input['obs_id'] ?? 0;
    $permanence = $input['permanence'] ?? 0;

    error_log("Track Duration - Processed data: obs_id=" . $obsId . ", permanence=" . $permanence);

    if ($obsId > 0 && $permanence >= 0) {
        $tracker = new Tracker();
        // Ensure permanence is in seconds if required, current JS will send ms
        // Convert ms to seconds: round($permanence / 1000)
        $permanenceInSeconds = round($permanence / 1000);
        $success = $tracker->updateProductViewPermanence($obsId, $permanenceInSeconds);
        if ($success) {
            error_log("Permanence update successful for obsId: " . $obsId . " with permanence: " . $permanenceInSeconds . "s");
            echo json_encode(['status' => 'success']);
        } else {
            error_log("Permanence update failed for obsId: " . $obsId . " with permanence: " . $permanenceInSeconds . "s");
            echo json_encode(['status' => 'error', 'message' => 'Failed to update permanence']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
