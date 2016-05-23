<?php
require_once 'TwistOAuth.phar';
require_once 'settings.php';

@session_start();

function redirect_to_main_page() {
    $url = '/FollowsComparison/main.php';
    header("Location: $url");
    header('Content-Type: text/plain; charset=utf-8');
    exit("Redirecting to $url ...");
}

if (!isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/plain; charset=utf-8', true, 400);
    exit('Required: HTTP Host Header');
}

if (isset($_SESSION['logined'])) {
    redirect_to_main_page();
}

try {
    if (!isset($_SESSION['to'])) { // First Access
        $_SESSION['to'] = new TwistOAuth(Settings::TO_CK, Settings::TO_CS);
        $_SESSION['to'] = $_SESSION['to']->renewWithRequestToken('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        header("Location: {$_SESSION['to']->getAuthenticateUrl()}");
        header('Content-Type: text/plain; charset=utf-8');
        exit("Redirecting to {$_SESSION['to']->getAuthenticateUrl()} ...");
    } else { // Redirected From Twitter
        $_SESSION['to'] = $_SESSION['to']->renewWithAccessToken(filter_input(INPUT_GET, 'oauth_verifier'));
        $user = json_decode(json_encode($_SESSION['to']->get(
            'account/verify_credentials', ['include_entities' => false, 'skip_status' => true,])), true
        );
        $_SESSION['userid'] = $user['id'];
        $_SESSION['logined'] = true;

        session_regenerate_id(true);

        redirect_to_main_page();
    }
} catch (TwistException $e) {
    $_SESSION = [];

    header('Content-Type: text/plain; charset=utf-8', true, $e->getCode() ?: 500);
    exit($e->getMessage());
}
