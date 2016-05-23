<?php
require_once 'TwistOAuth.phar';
require_once 'settings.php';

@session_start();

if (!isset($_SESSION['logined'])) {
    $url = '/FollowsComparison/index.php';
    header("Location: $url");
    header('Content-Type: text/plain; charset=utf-8');
    exit("Redirecting to $url ...");
}

$date = date('Y-m-d H:i:s');
$user = json_decode(json_encode($_SESSION['to']->get('account/verify_credentials', ['include_entities' => false, 'skip_status' => true])), true);
$friends = json_decode(json_encode($_SESSION['to']->get('friends/ids')), true);
var_dump($friends['ids']);
// exit();
$followers = json_decode(json_encode($_SESSION['to']->get('followers/ids')), true);

try {
    $pdo = new PDO(
        sprintf('mysql:dbname=%s;host=%s;charset=utf8', Settings::DB_NAME, Settings::DB_HOST),
        Settings::DB_USERNAME,
        Settings::DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $stmt_date = $pdo->prepare('INSERT INTO snapshots (created_at, userid, screenname, follow, follower, tweet) VALUES (?,?,?,?,?,?)');
    $stmt_date->execute([$date, $user['id'], $user['screen_name'], $user['friends_count'], $user['followers_count'], $user['statuses_count']]);
    $stmt_friends = $pdo->prepare('INSERT INTO friends VALUES (?,?,?)');
    foreach ($friends['ids'] as $friend) {
        $stmt_friends->execute([$date, $user['id'], $friend]);
    }
    $stmt_followers = $pdo->prepare('INSERT INTO followers VALUES (?,?,?)');
    foreach ($followers['ids'] as $follower) {
        $stmt_followers->execute([$date, $user['id'], $follower]);
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

$url = '/FollowsComparison/main.php';
header("Location: $url");
header('Content-Type: text/plain; charset=utf-8');
