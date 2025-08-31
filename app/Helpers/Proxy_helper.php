<?php

if (!function_exists('create_error_html')) {
  function create_error_html($title, $message): string
  {
    return '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><h1>' . 
           htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>' . 
           htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
  }
}

if (!function_exists('proxy_allowed')) {
  function proxy_allowed(): bool
  {
    $allow = false;
    if (isset($_SERVER['HTTP_REFERER'])) {
      $ref = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
      if ($ref && (strpos($ref, 'blogkan.com') !== false || strpos($ref, 'ai-gazoukan.com') !== false || strpos($ref, 'cloudfront.net') !== false)) {
        $allow = true;
      }
    }
    
    if (isset($_GET['token']) && $_GET['token'] === '23uQf8FIcG_U0Nnr') {
      $allow = true;
    } else if (isset($_SERVER['QUERY_STRING'])) {
      parse_str($_SERVER['QUERY_STRING'], $query_params);
      if (isset($query_params['token']) && $query_params['token'] === '23uQf8FIcG_U0Nnr') {
        $allow = true;
      }
    }
    
    return $allow;
  }
}

if (!function_exists('proxy_request')) {
  function proxy_request($targetUrl, $request, $response)
  {
    if (strtoupper($request->getMethod()) !== 'GET') {
      $response->setStatusCode(400);
      return 'Only GET requests are allowed.';
    }
    
    if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
      $response->setStatusCode(400);
      return 'Invalid URL';
    }
    $headers = [];
    foreach ($request->headers() as $name => $value) {
      if (strtolower($name) === 'host') continue;
      $headers[] = "$name: " . $value->getValue();
    }
    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $response_raw = curl_exec($ch);
    if ($response_raw === false) {
      $response->setStatusCode(502);
      $err = curl_error($ch);
      curl_close($ch);
      return create_error_html('取得エラー', $err);
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $response_headers = substr($response_raw, 0, $header_size);
    $response_body = substr($response_raw, $header_size);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code < 200 || $http_code >= 300) {
      $response->setStatusCode($http_code);
      return create_error_html('レスポンスエラー', 'HTTP status: ' . $http_code);
    }
    $lines = explode("\r\n", $response_headers);
    $content_type_set = false;
    $status_code_set = false;
    foreach ($lines as $line) {
      // 残っている\rを除去
      $line = trim($line, "\r");
      
      if (stripos($line, 'Transfer-Encoding:') === 0) continue;
      if (stripos($line, 'Content-Length:') === 0) continue;
      if (preg_match('/^HTTP\//', $line)) {
        // ステータスコードが未設定の場合のみ設定
        if (!$status_code_set && preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $line, $m)) {
          $response->setStatusCode((int)$m[1]);
          $status_code_set = true;
        }
      } elseif ($line !== '') {
        $parts = explode(': ', $line, 2);
        if (count($parts) === 2) {
          $header_name = trim($parts[0]);
          $header_value = trim($parts[1]);
          if (strtolower($header_name) === 'content-type') {
            $content_type_set = true;
            $response->setContentType($header_value);
          } elseif (strtolower($header_name) === 'set-cookie') {
            // Set-Cookieは複数行対応
            $response->addHeader($header_name, $header_value);
          } else {
            $response->setHeader($header_name, $header_value);
          }
        }
      }
    }
    // Content-Typeが明示的に設定されていない場合のみデフォルト値を設定
    if (!$content_type_set) {
      $response->setContentType('application/octet-stream');
    }
    return $response->setBody($response_body);
  }
}
