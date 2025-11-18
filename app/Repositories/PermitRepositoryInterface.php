<?php

namespace App\Repositories;

interface PermitRepositoryInterface
{
    public function getAllPermits();
    public function getAllPermitsDashobard();
    public function create(array $data);
    public function findPermitById(string $permitId);
    public function findAndUpdatePermitById(string $permitId,array $data);
    public function findAndDeletePermitById(string $userId);
    public function getAllPermitsDashboardByUser(string $userId);
    public function getPermitByUserId(string $userId);


}
