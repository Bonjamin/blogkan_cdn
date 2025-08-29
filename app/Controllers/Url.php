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
    
    if (proxy_allowed() && $decoded_url) {
      return proxy_request($decoded_url, $this->request, $this->response);
    }
    return 'Invalid or not allowed.';
  }
}
