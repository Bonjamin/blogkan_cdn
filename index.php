<?php
// リファラーのホスト名チェック
$allow = false;
if (isset($_SERVER['HTTP_REFERER'])) {
  $ref = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
  if ($ref && (strpos($ref, 'blogkan.com') !== false || strpos($ref, 'ai-gazoukan.com') !== false)) {
    $allow = true;
  }
}

// urlパラメータがある場合はプロキシとして動作
if ($allow && isset($_GET['url'])) {
  // GETリクエストのみ許可
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(400);
    echo 'Only GET requests are allowed.';
    exit;
  }
  $targetUrl = $_GET['url'];
  // 入力バリデーション（任意: 必要に応じて制限）
  if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo 'Invalid URL';
    exit;
  }

  // クライアントのリクエストヘッダーを取得
  $headers = [];
  foreach (getallheaders() as $name => $value) {
    // Hostヘッダーは転送しない
    if (strtolower($name) === 'host') continue;
    $headers[] = "$name: $value";
  }

  $ch = curl_init($targetUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // レスポンスを取得
  curl_setopt($ch, CURLOPT_HEADER, true); // ヘッダーも取得
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

  $response = curl_exec($ch);
  if ($response === false) {
    http_response_code(502);
    $err = curl_error($ch);
    curl_close($ch);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><h1>取得エラー</h1><p>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    exit;
  }

  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $response_headers = substr($response, 0, $header_size);
  $response_body = substr($response, $header_size);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($http_code < 200 || $http_code >= 300) {
    http_response_code($http_code);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><h1>レスポンスエラー</h1><p>HTTP status: ' . $http_code . '</p></body></html>';
    exit;
  }

  // レスポンスヘッダーを出力
  $lines = explode("\r\n", $response_headers);
  foreach ($lines as $line) {
    if (stripos($line, 'Transfer-Encoding:') === 0) continue;
    if (stripos($line, 'Content-Length:') === 0) continue;
    if (preg_match('/^HTTP\//', $line)) {
      // ステータスライン
      if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $line, $m)) {
        http_response_code((int)$m[1]);
      }
    } elseif ($line !== '') {
      header($line, false);
    }
  }
  echo $response_body;
  exit;
}

?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>CDN Proxy Test</title>
</head>
<body>
  <h1>CDN Proxy テスト</h1>
  <form method="get" action="">
    <label>取得したいURL: <input type="text" name="url" size="60" value="https://www.example.com/"></label>
    <button type="submit">取得</button>
  </form>
</body>
</html>