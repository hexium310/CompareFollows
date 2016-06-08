<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/classes/TwistOAuth.phar';

@session_start();

$dotenv = new Dotenv\Dotenv(dirname(dirname(__DIR__)));
$dotenv->load();

if (!isset($_SESSION['logined'])) {
    $url = '/CompareFollows/index.php';
    header("Location: $url");
    header('Content-Type: text/plain; charset=utf-8');
    exit("Redirecting to $url ...");
}

$dates[0] = filter_input(INPUT_POST, 'date1');
$dates[1] = filter_input(INPUT_POST, 'date2');
sort($dates);

try {
    $pdo = new PDO(
        sprintf('mysql:dbname=%s;host=%s;charset=utf8', $_ENV['DB_NAME'], $_ENV['DB_HOST']),
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $stmt = $pdo->prepare('SELECT screenname, follow, follower, tweet  FROM snapshots WHERE created_at = ? AND userid = ?');
    foreach($dates as $key => $date) {
        $stmt->execute([$date, $_SESSION['userid']]);
        $datas[$key] = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // following
    $stmt_friends = $pdo->prepare('
        SELECT userid FROM friends fr1
        WHERE fr1.created_at = ? AND fr1.myid = ?
        AND NOT EXISTS (SELECT * FROM friends fr2 WHERE fr1.userid = fr2.userid AND fr2.created_at = ? AND fr2.myid = ?)
    ');
    // リムり
    $stmt_friends->execute([$dates[0], $_SESSION['userid'], $dates[1], $_SESSION['userid']]);
    $friends['removed'] = $stmt_friends->fetchAll(PDO::FETCH_COLUMN, 0);
    // 新規フォロー
    $stmt_friends->execute([$dates[1], $_SESSION['userid'], $dates[0], $_SESSION['userid']]);
    $friends['added'] = $stmt_friends->fetchAll(PDO::FETCH_COLUMN, 0);

    foreach ($friends as $key => $friend) {
        if (empty($friend)) {
            continue;
        }
        $users = json_decode(json_encode($_SESSION['to']->get('users/lookup', ['user_id' => implode(',', $friend), 'include_entities' => false])), true);
        $stmt_users = $pdo->prepare('
        INSERT INTO users VALUE (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE screenname = ?, name = ?, updated_at = ?
        ');
        foreach ($users as $value) {
            $input_parameters = [
                $value['id'],
                $value['screen_name'],
                $value['name'],
                date('Y-m-d H:i:s'),
                $value['screen_name'],
                $value['name'],
                date('Y-m-d H:i:s'),
            ];
            $stmt_users->execute($input_parameters);
        }
        $friends_details[$key] = array_merge([], $users);
    }

    // followers
    $stmt_followers = $pdo->prepare('
        SELECT userid FROM followers fo1
        WHERE fo1.created_at = ? AND fo1.myid = ?
        AND NOT EXISTS (SELECT * FROM followers fo2 WHERE fo1.userid = fo2.userid AND fo2.created_at = ? AND fo2.myid = ?)
    ');
    // リムられ
    $stmt_followers->execute([$dates[0], $_SESSION['userid'], $dates[1], $_SESSION['userid']]);
    $followers['removed'] = $stmt_followers->fetchAll(PDO::FETCH_COLUMN, 0);
    // 新規フォロワー
    $stmt_followers->execute([$dates[1], $_SESSION['userid'], $dates[0], $_SESSION['userid']]);
    $followers['added'] = $stmt_followers->fetchAll(PDO::FETCH_COLUMN, 0);

    foreach ($followers as $key => $follower) {
        if (empty($follower)) {
            continue;
        }
        $users = json_decode(json_encode($_SESSION['to']->get('users/lookup', ['user_id' => implode(',', $follower), 'include_entities' => false])), true);
        $stmt_users = $pdo->prepare('
            INSERT INTO users VALUE (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE screenname = ?, name = ?, updated_at = ?
        ');
        foreach ($users as $value) {
            $input_parameters = [
                $value['id'],
                $value['screen_name'],
                $value['name'],
                date('Y-m-d H:i:s'),
                $value['screen_name'],
                $value['name'],
                date('Y-m-d H:i:s'),
            ];
            $stmt_users->execute($input_parameters);
        }
        $followers_details[$key] = array_merge([], $users);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    var_dump($e);
}

header('Content-Type: text/html; charset=utf-8');

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>比較</title>
        <link href="/CompareFollows/css/bootstrap.min.css" rel="stylesheet">
        <script src="/CompareFollows/js/jquery-1.12.3.min.js"></script>
        <script src="/CompareFollows/js/bootstrap.min.js"></script>
    </head>
    <body>
        <div class="container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="col-md-3">date</th>
                        <th class="col-md-3">screen name</th>
                        <th class="col-md-2">tweet count</th>
                        <th class="col-md-2">following count</th>
                        <th class="col-md-2">follower count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th><?= $dates[0]; ?></th>
                        <td><?= $datas[0]['screenname']; ?></td>
                        <td><?= $datas[0]['tweet']; ?></td>
                        <td><?= $datas[0]['follow']; ?></td>
                        <td><?= $datas[0]['follower']; ?></td>
                    </tr>
                    <tr>
                        <th><?= $dates[1]; ?></th>
                        <td><?= $datas[1]['screenname']; ?></td>
                        <td><?= $datas[1]['tweet']; ?></td>
                        <td><?= $datas[1]['follow']; ?></td>
                        <td><?= $datas[1]['follower']; ?></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <div class="panel-group" id="accordion">
                <div class="list-group">
                    <a href="#following" class="list-group-item" data-toggle="collapse" data-parent="#accordion">Follow</a>
                    <div id="following" class="panel-collapse collapse">
                        <div class="list-group-item">
                            <?php if(!empty($friends_details['removed'])): ?>
                            減ったフォロー
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="col-md-6">screen name</th><th class="col-md-6">name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($friends_details['removed'] as $friend): ?>
                                    <tr>
                                        <td><?= $friend['screen_name']; ?></td><td><?= $friend['name']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                            <?php if(!empty($friends_details['added'])): ?>
                            増えたフォロー
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="col-md-6">screen name</th><th class="col-md-6">name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($friends_details['added'] as $friend): ?>
                                    <tr>
                                        <td><?= $friend['screen_name']; ?></td><td><?= $friend['name']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="list-group">
                    <a href="#follower" class="list-group-item" data-toggle="collapse" data-parent="#accordion">Follower</a>
                    <div id="follower" class="panel-collapse collapse">
                        <div class="list-group-item">
                            <?php if(!empty($followers_details['removed'])): ?>
                            減ったフォロワー
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="col-md-6">screen name</th><th class="col-md-6">name</th><th>following</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($followers_details['removed'] as $follower): ?>
                                    <tr>
                                        <td><?= $follower['screen_name']; ?></td><td><?= $follower['name']; ?></td><td><?= $follower['following'] ? 'YES' : 'NO'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                            <?php if(!empty($followers_details['added'])): ?>
                            増えたフォロワー
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="col-md-6">screen name</th><th class="col-md-6">name</th><th>following</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($followers_details['added'] as $follower): ?>
                                    <tr>
                                        <td><?= $follower['screen_name']; ?></td><td><?= $follower['name']; ?></td><td><?= $follower['following'] ? 'YES' : 'NO'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <br><br><br>
            <a href="/CompareFollows/main.php">main</a>
        </div>
    </body>
</html>
