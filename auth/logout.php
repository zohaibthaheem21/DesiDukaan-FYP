<?php
/**
 * DesiDukaan - Logout
 */

require_once '../config/config.php';

// Clean up session from database
if (isset($_SESSION['session_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE id = ?");
        $stmt->execute([$_SESSION['session_id']]);
    } catch (PDOException $e) {
        // Log error but continue with logout
    }
}

// Destroy session
session_destroy();

// Redirect to home page
redirect(SITE_URL . '/index.php');
?>
