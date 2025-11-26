<?php

namespace App\Repositories;

interface CitizenCharterRepositoryInterface
{

    public function create(array $data);
    public function getCitizenCharterByUserById(string $id);
    public function getCitizenCharter();
    public function getCitizenCharterBySteps(array $steps = []);
    public function findAndUpdateCitizenCharterById(string $citizenCharterId,array $data);
    public function findCitizenCharterById(string $citizenCharterId);


}
