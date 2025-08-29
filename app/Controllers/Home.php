<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        helper('Proxy');
        if (proxy_allowed() && isset($_GET['url'])) {
            return proxy_request($_GET['url'], $this->request, $this->response);
        }
        return view('home');
    }

    public function test(): string
    {
        return view('test');
    }
}
