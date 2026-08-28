<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\SeoService;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function view(string $template, array $data = [], int $status = 200): string
    {
        http_response_code($status);

        return View::render($template, $data);
    }

    /** Set the page's SEO metadata, then render. */
    protected function page(string $template, array $seo, array $data = []): string
    {
        SeoService::set($seo);

        return $this->view($template, $data);
    }

    protected function redirect(string $url, int $status = 302): never
    {
        Response::redirect($url, $status);
    }

    protected function back(string $fallback = '/'): never
    {
        Response::back($fallback);
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function withErrors(array $errors, ?array $old = null): never
    {
        Session::flashErrors($errors);
        Session::flashOld($old ?? $this->request->all());

        $this->back();
    }

    protected function success(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function error(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function abort(int $status, string $message = ''): never
    {
        throw new \App\Core\HttpException($status, $message);
    }
}
