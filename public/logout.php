<?php
require_once '../src/config.php';
require_once '../src/helpers.php';
require_once '../src/auth.php';

$auth = new Auth();
$result = $auth->logout();

flash_message($result['message'], 'success');
redirect('index.php');
