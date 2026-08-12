<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class PublicPortal extends BaseController
{
    public function ranking() { return redirect()->to('/#national-ranking'); }
    public function players() { return redirect()->to('/#player-search'); }
    public function matches() { return redirect()->to('/live-scores'); }
    public function clubs() { return redirect()->to('/#club-ranking'); }
    public function calendar() { return redirect()->to('/tournaments'); }
    public function live() { return redirect()->to('/live-scores'); }
    public function verify() { return redirect()->to('/#verify'); }
}
