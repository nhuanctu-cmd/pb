<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseController extends Controller
{
    protected $helpers = ['app', 'setting', 'form', 'url', 'text', 'html'];

    protected $viewData = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Set locale from session or default
        $locale = session()->get('locale') ?? service('request')->getLocale() ?? 'en';
        service('language')->setLocale($locale);

        // Set timezone
        $this->setTimezone();

        // Global view data
        $this->viewData['app_name'] = lang('App.app_name');
        $this->viewData['current_locale'] = $locale;
        $this->viewData['is_superadmin'] = is_superadmin();
        $this->viewData['current_user'] = user();
        $this->viewData['current_tenant_id'] = current_tenant_id();
        $this->viewData['current_branch_id'] = current_branch_id();
    }

    protected function setTimezone(): void
    {
        $timezone = setting('app_timezone') ?? 'Asia/Ho_Chi_Minh';
        date_default_timezone_set($timezone);
    }

    protected function setViewData(string $key, $value): void
    {
        $this->viewData[$key] = $value;
    }

    protected function render(string $view, array $data = []): string
    {
        return view($view, array_merge($this->viewData, $data));
    }
}
