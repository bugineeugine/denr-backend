<?php

namespace App\Repositories\Implementations;

use App\Models\CitizenCharter;
use App\Repositories\CitizenCharterRepositoryInterface;

class CitizenCharterRepository implements CitizenCharterRepositoryInterface
{


    public function create(array $data)
    {
        return CitizenCharter::create($data);
    }
    public function getCitizenCharterByUserById(string $userId)
    {
        return CitizenCharter::where('created_by', $userId)->get();
    }
       public function getCitizenCharter()
    {
          return CitizenCharter::with('creator')->get();

    }
    public function getCitizenCharterBySteps(array $steps = [])
    {
        $query = CitizenCharter::with('creator');

        if (!empty($steps)) {
            $query->whereIn('steps', $steps);
        }

        return $query->get();
    }
     public function findAndUpdateCitizenCharterById(string $citizenCharterId , array $data){
        $citizenCharter = CitizenCharter::find($citizenCharterId);

        if (!$citizenCharter) {
            return null;
        }

        $citizenCharter->update($data);
        return $citizenCharter;
    }

      public function findCitizenCharterById(string $citizenCharterId ){
        $citizenCharter = CitizenCharter::find($citizenCharterId);

        if (!$citizenCharter) {
            return null;
        }


        return $citizenCharter;
    }
}
