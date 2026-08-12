<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerRatingProviderLinkModel extends Model
{
    protected $table = 'player_rating_provider_links';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['player_id', 'provider_id', 'external_player_id', 'verification_status', 'consent_status', 'authorization_reference', 'linked_at', 'last_synced_at', 'sync_state', 'metadata'];
}
