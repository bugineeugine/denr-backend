<?php

namespace App\Repositories;

interface ViolationRepositoryInterface
{
    public function getAllViolations();
    public function create(array $data);
    public function findViolationById(string $id);
    public function findAndUpdateViolationById(string $id, array $data);
    public function findAndDeleteViolationById(string $id);
    public function getViolationsByPermitId(string $permitId);
    public function getViolationStats();
    public function getTopViolatorLocations(int $limit = 5);
    public function getReports(?string $from = null, ?string $to = null);
}
