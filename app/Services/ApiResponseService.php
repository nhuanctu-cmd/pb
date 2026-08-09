<?php

namespace App\Services;

use CodeIgniter\HTTP\ResponseInterface;

class ApiResponseService
{
    protected int $statusCode = ResponseInterface::HTTP_OK;
    protected string $message = 'Thành công';
    protected $data = null;
    protected array $errors = [];
    protected array $meta = [];
    protected string $language = 'en';

    public function __construct()
    {
        $this->language = service('request')->getLocale() ?? 'en';
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setData($data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function setPagination(int $total, int $perPage, int $currentPage): self
    {
        $this->meta = array_merge($this->meta, [
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'last_page'    => (int) ceil($total / max($perPage, 1)),
            ],
        ]);
        return $this;
    }

    public function respond()
    {
        $response = [
            'status'  => $this->statusCode,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        return service('response')
            ->setStatusCode($this->statusCode)
            ->setJSON($response);
    }

    public function success($data = null, ?string $message = null, int $code = ResponseInterface::HTTP_OK)
    {
        $message ??= lang('App.success');
        return $this->setStatusCode($code)
                    ->setMessage($message)
                    ->setData($data)
                    ->respond();
    }

    public function created($data = null, ?string $message = null)
    {
        $message ??= lang('App.created_success');
        return $this->success($data, $message, ResponseInterface::HTTP_CREATED);
    }

    public function updated($data = null, ?string $message = null)
    {
        $message ??= lang('App.updated_success');
        return $this->success($data, $message, ResponseInterface::HTTP_OK);
    }

    public function deleted(?string $message = null)
    {
        $message ??= lang('App.deleted_success');
        return $this->success(null, $message, ResponseInterface::HTTP_OK);
    }

    public function error(?string $message = null, int $code = ResponseInterface::HTTP_BAD_REQUEST, array $errors = [])
    {
        $message ??= lang('App.error');
        return $this->setStatusCode($code)
                    ->setMessage($message)
                    ->setErrors($errors)
                    ->respond();
    }

    public function notFound(?string $message = null)
    {
        $message ??= lang('App.not_found');
        return $this->error($message, ResponseInterface::HTTP_NOT_FOUND);
    }

    public function unauthorized(?string $message = null)
    {
        $message ??= lang('App.unauthorized');
        return $this->error($message, ResponseInterface::HTTP_UNAUTHORIZED);
    }

    public function forbidden(?string $message = null)
    {
        $message ??= lang('App.forbidden');
        return $this->error($message, ResponseInterface::HTTP_FORBIDDEN);
    }

    public function validationError(array $errors, ?string $message = null)
    {
        $message ??= lang('App.validation_error');
        return $this->error($message, ResponseInterface::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
