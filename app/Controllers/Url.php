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
    
    $is_allowed = proxy_allowed();
    
    if ($is_allowed && $url) {
      // GETクエリからwidth, heightを取得
      $width = $this->request->getVar('w');
      $height = $this->request->getVar('h');
      
      // 両方とも0以上の場合のみwidthとheightを渡す
      if ($width !== null && $height !== null && $width >= 0 && $height >= 0) {
        return proxy_request($url, $this->request, $this->response, (int)$width, (int)$height);
      } else {
        return proxy_request($url, $this->request, $this->response);
      }
    }

    $error_msg = 'Invalid or not allowed.';
    if (!$url) {
      $error_msg .= ' (No URL provided)';
    } elseif (!$is_allowed) {
      $error_msg .= ' (Access denied)';
    }
    
    return $error_msg;
  }
}
