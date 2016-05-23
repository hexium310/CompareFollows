<?php
@session_start();

$_SESSION = [];

$url = '/FollowsComparison/index.php';
header("Location: $url");
header('Content-Type: text/plain; charset=utf-8');
exit("Redirecting to $url ...");
