<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;

class LivestreamController extends BaseController
{
    public function index()
    {
        $tenantId = (int) session('tenant_id');
        return view('player/livestream/index', [
            'channels' => $tenantId ? service('livestreamService')->publicChannels($tenantId) : [],
        ]);
    }
}
