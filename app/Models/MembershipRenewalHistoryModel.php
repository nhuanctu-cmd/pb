<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipRenewalHistoryModel extends Model
{
    protected $table = 'membership_renewal_histories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'membership_id', 'player_id', 'package_id_before', 'package_id_after',
        'start_date_before', 'end_date_before', 'start_date_after', 'end_date_after',
        'action', 'actor_user_id', 'notes',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
}
