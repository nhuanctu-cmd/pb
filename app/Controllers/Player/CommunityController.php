<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class CommunityController extends BaseController
{
    private $service;
    public function __construct() { $this->service = service('communityService'); }

    public function index()
    {
        $tenantId = (int) session('tenant_id'); $posts = $tenantId ? $this->service->feed($tenantId) : [];
        $comments = []; foreach ($posts as $post) $comments[(int) $post->id] = $this->service->comments((int) $post->id, $tenantId);
        return view('player/community/index', ['posts' => $posts, 'comments' => $comments]);
    }

    public function store()
    {
        $result = $this->service->createPost($this->currentPlayerId(), $this->request->getPost(), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function comment(int $postId)
    {
        $result = $this->service->comment($postId, $this->currentPlayerId(), (string) $this->request->getPost('body'), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function react(int $postId)
    {
        $result = $this->service->react($postId, $this->currentPlayerId(), (string) $this->request->getPost('reaction', 'like'), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    private function currentPlayerId(): int { $player = (new PlayerModel())->findPlayerByUser((int) session('user_id'), (int) session('tenant_id')); return (int) ($player->id ?? 0); }
}
