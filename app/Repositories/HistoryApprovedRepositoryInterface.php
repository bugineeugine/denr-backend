<?php

namespace App\Repositories;

interface HistoryApprovedRepositoryInterface
{

    public function create(array $data);

    public function findById(string $id);

}
