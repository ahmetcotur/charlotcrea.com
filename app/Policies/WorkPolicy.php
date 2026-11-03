<?php

namespace App\Policies;

use App\Models\CustomerAccount;
use App\Models\Work;

class WorkPolicy
{
    public function view(CustomerAccount $account, Work $work): bool
    {
        return $account->canAccess() && $account->customer_id === $work->customer_id && (bool) $work->published;
    }
}
