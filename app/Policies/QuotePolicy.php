<?php

namespace App\Policies;

use App\Models\CustomerAccount;
use App\Models\Quote;

class QuotePolicy
{
    public function view(CustomerAccount $account, Quote $quote): bool
    {
        return $account->canAccess() && $account->customer_id === $quote->customer_id && (bool) $quote->published && $quote->status !== 'draft';
    }
}
