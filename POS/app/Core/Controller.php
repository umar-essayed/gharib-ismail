<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}
