<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\MediaService;

class MediaController extends BaseController
{
    protected MediaService $mediaService;

    public function __construct()
    {
        $this->mediaService = new MediaService();
    }

    public function index()
    {
        $tenantId = current_tenant_id();
        $type     = $this->request->getGet('type') ?: null;

        $this->viewData['pageTitle']       = lang('App.menu_media');
        $this->viewData['pageDescription'] = lang('App.media_subtitle');
        $this->viewData['media']           = $tenantId
            ? $this->mediaService->getByTenant($tenantId, $type)
            : [];
        $this->viewData['type']            = $type;

        return $this->render('admin/media/index', $this->viewData);
    }

    public function upload()
    {
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', lang('App.media_invalid_file'));
        }

        $tenantId = current_tenant_id();
        $userId   = user_id();

        if (! $tenantId) {
            return redirect()->back()->with('error', lang('App.forbidden'));
        }

        $result = $this->mediaService->upload($file, $tenantId, $userId);

        if (! $result) {
            return redirect()->back()->with('error', lang('App.media_upload_failed'));
        }

        log_audit([
            'table' => 'media_files',
            'data'  => ['file_name' => $result->file_name, 'file_path' => $result->file_path],
        ]);

        return redirect()->back()->with('success', lang('App.media_upload_success'));
    }

    public function delete(int $id)
    {
        $this->mediaService->delete($id);
        return redirect()->back()->with('success', lang('App.deleted_success'));
    }
}
