<?php
require_once 'includes/session.php';

$session = SessionManager::getInstance();
$session->logout();

header('Location: login.php');
exit();
?>
