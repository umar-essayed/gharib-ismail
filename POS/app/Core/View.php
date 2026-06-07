<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $viewPath = base_path('app/Views/' . $view . '.php');
        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = base_path('app/Views/' . $layout . '.php');
        require $layoutPath;
    }

    public static function partial(string $view, array $data = []): void
    {
        $viewPath = base_path('app/Views/' . $view . '.php');
        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}
