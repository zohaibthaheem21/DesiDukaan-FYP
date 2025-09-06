<?php
/**
 * Language Setting Endpoint
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $language = $_POST['language'];

    // Validate language
    $supported_languages = ['en', 'sd'];
    if (in_array($language, $supported_languages)) {
        $_SESSION['language'] = $language;
        echo json_encode(['success' => true, 'message' => 'Language updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
