<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Url extends BaseController
{
  public function index($url = null)
  {
    helper('Proxy');
    
    // 全てのURIセグメントを取得して結合
    $uri = $this->request->getUri();
    $segments = $uri->getSegments();
    
    // 'url' セグメントを除いて、残りを結合
    if (count($segments) > 1) {
      array_shift($segments); // 'url' を削除
      $url = implode('/', $segments);
      
      // https: や http: の場合、// を追加
      if (preg_match('/^https?:$/', $segments[0])) {
        $url = $segments[0] . '//' . implode('/', array_slice($segments, 1));
      }
    }
    
    $decoded_url = $url ? urldecode($url) : null;
    
    // デバッグ情報を詳細に記録
    $referer = $_SERVER['HTTP_REFERER'] ?? 'NOT_SET';
    $token = $_GET['token'] ?? 'NOT_SET';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'NOT_SET';
    $host = $_SERVER['HTTP_HOST'] ?? 'NOT_SET';
    $request_uri = $_SERVER['REQUEST_URI'] ?? 'NOT_SET';
    
    // 全HTTPヘッダーを記録
    $all_headers = [];
    foreach ($_SERVER as $key => $value) {
      if (strpos($key, 'HTTP_') === 0) {
        $all_headers[] = "$key: $value";
      }
    }
    $headers_string = implode("\n", $all_headers);
    
    // proxy_allowed() を呼ぶ前の状態をログ
    log_message('debug', "=== PROXY DEBUG START ===");
    log_message('debug', "Requested URL: {$decoded_url}");
    log_message('debug', "HTTP_REFERER: {$referer}");
    log_message('debug', "Token: {$token}");
    log_message('debug', "HTTP_HOST: {$host}");
    log_message('debug', "REQUEST_URI: {$request_uri}");
    log_message('debug', "User Agent: {$user_agent}");
    log_message('debug', "All HTTP Headers:\n{$headers_string}");
    
    // リファラーの詳細解析
    if ($referer !== 'NOT_SET') {
      $parsed_referer = parse_url($referer);
      log_message('debug', "Parsed referer host: " . ($parsed_referer['host'] ?? 'PARSE_FAILED'));
      log_message('debug', "Referer scheme: " . ($parsed_referer['scheme'] ?? 'NO_SCHEME'));
      
      // blogkan.com チェック
      if (isset($parsed_referer['host'])) {
        $has_blogkan = strpos($parsed_referer['host'], 'blogkan.com') !== false;
        $has_ai_gazoukan = strpos($parsed_referer['host'], 'ai-gazoukan.com') !== false;
        log_message('debug', "Contains blogkan.com: " . ($has_blogkan ? 'YES' : 'NO'));
        log_message('debug', "Contains ai-gazoukan.com: " . ($has_ai_gazoukan ? 'YES' : 'NO'));
      }
    }
    
    $is_allowed = proxy_allowed();
    log_message('debug', "proxy_allowed() result: " . ($is_allowed ? 'TRUE' : 'FALSE'));
    log_message('debug', "=== PROXY DEBUG END ===");
    
    if (!$is_allowed) {
      log_message('error', "ACCESS DENIED - URL: {$decoded_url}, Referer: {$referer}, Token: {$token}");
    }
    
    if ($is_allowed && $decoded_url) {
      return proxy_request($decoded_url, $this->request, $this->response);
    }
    
    // より詳細なエラーメッセージ
    $error_msg = 'Invalid or not allowed.';
    if (!$decoded_url) {
      $error_msg .= ' (No URL provided)';
    } elseif (!$is_allowed) {
      $error_msg .= ' (Access denied - check referer or token)';
    }
    
    return $error_msg;
  }
}
