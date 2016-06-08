<?php
@session_start();

$_SESSION = [];

$url = '/CompareFollows/index.php';
header("Location: $url");
header('Content-Type: text/plain; charset=utf-8');
exit("Redirecting to $url ...");
