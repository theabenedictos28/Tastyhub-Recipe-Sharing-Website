<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php?logout=1"); // Redirect with logout message
exit;
?>
