<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // リファラーのホスト名チェック
        $allow = false;
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if ($ref && (strpos($ref, 'blogkan.com') !== false || strpos($ref, 'ai-gazoukan.com') !== false)) {
                $allow = true;
            }
        }
        if (isset($_GET['token']) && $_GET['token'] === '23uQf8FIcG_U0Nnr') {
            $allow = true;
        }

        // urlパラメータがある場合はプロキシとして動作
        if ($allow && isset($_GET['url'])) {
            return $this->proxyRequest($_GET['url']);
        }

        // 通常のページ表示
        return view('home');
    }

    public function test(): string
    {
        return view('test');
    }

    private function proxyRequest($targetUrl)
    {
        // GETリクエストのみ許可
        if (strtoupper($this->request->getMethod()) !== 'GET') {
            $this->response->setStatusCode(400);
            return 'Only GET requests are allowed.';
        }

        // 入力バリデーション
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $this->response->setStatusCode(400);
            return 'Invalid URL';
        }

        // クライアントのリクエストヘッダーを取得
        $headers = [];
        foreach ($this->request->headers() as $name => $value) {
            // Hostヘッダーは転送しない
            if (strtolower($name) === 'host') continue;
            $headers[] = "$name: " . $value->getValue();
        }

        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // レスポンスを取得
        curl_setopt($ch, CURLOPT_HEADER, true); // ヘッダーも取得
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $response = curl_exec($ch);
        if ($response === false) {
            $this->response->setStatusCode(502);
            $err = curl_error($ch);
            curl_close($ch);
            return '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><h1>取得エラー</h1><p>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_headers = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code < 200 || $http_code >= 300) {
            $this->response->setStatusCode($http_code);
            return '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><h1>レスポンスエラー</h1><p>HTTP status: ' . $http_code . '</p></body></html>';
        }

        // レスポンスヘッダーを出力
        $lines = explode("\r\n", $response_headers);
        foreach ($lines as $line) {
            if (stripos($line, 'Transfer-Encoding:') === 0) continue;
            if (stripos($line, 'Content-Length:') === 0) continue;
            if (preg_match('/^HTTP\//', $line)) {
                // ステータスライン
                if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $line, $m)) {
                    $this->response->setStatusCode((int)$m[1]);
                }
            } elseif ($line !== '') {
                $parts = explode(': ', $line, 2);
                if (count($parts) === 2) {
                    $this->response->setHeader($parts[0], $parts[1]);
                }
            }
        }

        return $this->response->setBody($response_body);
    }
}
