<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Sales extends BaseController
{
    private const PRODUCTS = [
        'court-management' => 'court',
        'club'             => 'club',
        'tournament'       => 'tournament',
        'ranking-pro'      => 'ranking',
        'association'      => 'association',
        'api'              => 'api',
    ];

    public function index(): string
    {
        return $this->salesView('sales/home', ['pageTitle' => lang('Sales.solutions_title')]);
    }

    public function product(string $slug): string
    {
        $product = self::PRODUCTS[$slug] ?? null;
        if ($product === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Product not found');
        }

        return $this->salesView('sales/product', [
            'product' => $product,
            'pageTitle' => lang('Sales.product_' . $product . '_name'),
        ]);
    }

    public function pricing(): string
    {
        return $this->salesView('sales/pricing', ['pageTitle' => lang('Sales.pricing_title')]);
    }

    public function developers(): string
    {
        return $this->salesView('sales/developers', ['pageTitle' => lang('Sales.developers_title')]);
    }

    public function rankingPro(): string
    {
        return $this->salesView('sales/ranking_pro', ['pageTitle' => lang('Sales.ranking_pro_title')]);
    }

    public function demo(): string|RedirectResponse
    {
        if ($this->request->getMethod() === 'post') {
            $name = trim((string) $this->request->getPost('name'));
            $organization = trim((string) $this->request->getPost('organization'));
            $email = trim((string) $this->request->getPost('email'));

            if ($name === '' || $organization === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', lang('Sales.demo_required'));
            }

            // MVP lead flow: the payload is ready for a CRM adapter when one is connected.
            log_message('info', 'Sales demo request: {payload}', [
                'payload' => json_encode([
                    'name' => $name,
                    'organization' => $organization,
                    'email' => $email,
                    'phone' => trim((string) $this->request->getPost('phone')),
                    'use_case' => trim((string) $this->request->getPost('use_case')),
                ], JSON_UNESCAPED_UNICODE),
            ]);

            return redirect()->to(site_url('demo'))->with('success', lang('Sales.demo_success'));
        }

        return $this->salesView('sales/demo', ['pageTitle' => lang('Sales.demo_title')]);
    }

    private function salesView(string $view, array $data = []): string
    {
        return $this->render($view, array_merge([
            'salesLocale' => service('language')->getLocale(),
        ], $data));
    }
}
