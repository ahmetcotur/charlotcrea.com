<?php

namespace App\Services;

class CustomerPortalLabels
{
    public static function status(string $status): string
    {
        return __(['draft' => 'Draft', 'sent' => 'Awaiting your response', 'accepted' => 'Accepted', 'declined' => 'Declined', 'expired' => 'Expired', 'planned' => 'Planned', 'in_progress' => 'In progress', 'on_hold' => 'On hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'requested' => 'Requested', 'in_review' => 'In review', 'approved' => 'Approved', 'rejected' => 'Rejected'][$status] ?? $status);
    }
}
