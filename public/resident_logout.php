<?php
require_once __DIR__ . '/../config/resident_auth.php';

unset($_SESSION['resident_id'], $_SESSION['resident_name'], $_SESSION['resident_email']);

header('Location: index.php');
exit;
