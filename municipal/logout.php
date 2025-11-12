<?php
session_start();

// Clear municipal session
unset($_SESSION['municipal_logged_in']);
unset($_SESSION['municipal_email']);
session_destroy();

// Redirect to main index
header('Location: ../index.php');
exit;
