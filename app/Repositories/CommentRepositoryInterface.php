<?php

namespace App\Repositories;

interface CommentRepositoryInterface
{
    public function findByUserId(string $userId);
    public function findByPermitId(string $permitId);
    public function create(array $data);

}
