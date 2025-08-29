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
    
    // デバッグ情報を追加
    $is_allowed = proxy_allowed();
    $referer = $_SERVER['HTTP_REFERER'] ?? 'No referer';
    $token = $_GET['token'] ?? 'No token';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'No user agent';
    
    // 本番環境でのデバッグ
    if (!$is_allowed) {
      log_message('error', "Proxy not allowed - URL: {$decoded_url}, Referer: {$referer}, Token: {$token}, UA: {$user_agent}");
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
