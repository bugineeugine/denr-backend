<?php

namespace App\Repositories\Implementations;

use App\Models\HistoryApproved;
use App\Repositories\HistoryApprovedRepositoryInterface;

class HistoryApprovedRepository implements HistoryApprovedRepositoryInterface
{

    public function create(array $data)
    {
        return HistoryApproved::create($data);
    }




}
