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

  public function findById(string $id)
    {
        return HistoryApproved::with('creator:id,name,email')->where('petmit_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
    }


}
