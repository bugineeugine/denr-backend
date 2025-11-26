<?php

namespace App\Http\Controllers;
use App\Models\CitizenCharter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Repositories\CitizenCharterRepositoryInterface;
use Illuminate\Support\Str;
use App\Models\HistoryApproved;

class CitizenCharterController extends Controller
{    protected $citizenCharter;

    public function __construct(CitizenCharterRepositoryInterface $citizenCharter){
        $this->citizenCharter = $citizenCharter;
    }
        public function create(Request $request){
        try{
            $user = auth()->user();
            logger()->info('AUTH USER', ['user' => auth()->user()]);
            $userId = $user['id'];
            $data = $request->except([
            'requestLetter', 'barangayCertification', 'treeCuttingPermit', 'orCr', 'transportAgreement', 'spa'
            ]);

            $nextId = CitizenCharter::count() + 1;
            $citizen_no = 'CITIZEN-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
             $data['citizen_no'] =  $citizen_no;
            $data['created_by'] = $userId;

            $documentsFolder = public_path('storage/documents');


        if (!File::exists($documentsFolder)) {
            File::makeDirectory($documentsFolder, 0777, true);
        }


         $fileFields = [
                'requestLetter',
                'barangayCertification',
                'treeCuttingPermit',
                'orCr',
                'transportAgreement',
                'spa'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                    $file->move($documentsFolder, $filename);

                    $data[$field] = $filename;

                }
            }

            $citizenCharter = $this->citizenCharter->create($data);
            return response()->json([
                'message' => 'Created successfully!',
                'data' => $citizenCharter
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }

    }


    public function getCitizenCharterByUserById(){
            try{
                $user = auth()->user();
                $userId = $user['id'];
                $citizenCharter = $this->citizenCharter->getCitizenCharterByUserById($userId);
                 return response()->json([
                    'message' => 'Retrieve successfully!',
                    'data' => $citizenCharter
                ], 200);
            }catch(\Exception $e){
                return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
            }
    }
     public function getCitizenCharter(){
        try{

            $citizenCharter = $this->citizenCharter->getCitizenCharter();
                return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $citizenCharter
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }
    }

      public function getCitizenCharterForApproval(){
        try{
            $user = auth()->user();
            $position = $user['position'];
            $positionSteps = [
                    'Clerk'        => [0,8],
                    'Deputy CENR'  => [1],
                    'Chief'        => [2, 6],
                    'Accountant'   => [3],
                    'Cashier'      => [4],
                    'Inspector'    => [5],
                    'CENR PENR'    => [7],
                ];
            $steps = $positionSteps[$position] ?? [];

            $citizenCharter = $this->citizenCharter->getCitizenCharterBySteps($steps);
                return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $citizenCharter
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }
    }

    public function findAndUpdateById(Request $request,string $citizenCharterId){
        try{

            $data = $request->all();
            $user = auth()->user();
            $findCitizenCharter = $this->citizenCharter->findCitizenCharterById($citizenCharterId);
            if (!$findCitizenCharter) {
                return response()->json([
                    'message' => 'citizenCharter not found'
                ], 404);
            }

            $steps = $findCitizenCharter['steps'];
            $action = $findCitizenCharter['status'];


             if($steps == 0){
                $action = 'Forward toChief RPS (CENRO)/Chief TSD (Imp';
            }

            if($steps == 1){
                $action = 'Assign a team to conduct verification';
            }

            if($steps == 2){
                $action = 'Prepare and approve Order of Payment ';
            }

             if($steps == 3){
                $action = 'Accept payment and issue Official Receipt to the client';
            }

            if($steps == 4){
                $action = 'nspect the forest products in the area, and prepare Inspection Report, and Certificate of Verification (COV) and affix initial duplicate copy of COV';
            }

            if($steps == 5){
                $action = 'Review inspection report and affix initial on the duplicate copy of COV. Forward to the PENR/CENR Officer for approval.';
            }

             if($steps == 6){
                $action = 'Receive and review report. Sign and approve COV. ';
            }

            if($steps == 7){
                $action = 'Record and release approved COV.';
            }


            $data['status'] = $action;
            if($steps != 8){
                $data['steps'] = $steps + 1;
            }

            if($steps == 8){
                $data['steps']  = 9;
                 $action = 'Done';
            }


            $citizenCharter = $this->citizenCharter->findAndUpdateCitizenCharterById($citizenCharterId, $data);

            HistoryApproved::create([
                'action'=> $action,
                'citizenCharterId' => $citizenCharterId,
                'approved_by'=>$user['id']
            ]);

            return response()->json([
                'message' => 'Updated successfully!',
                'data' => $citizenCharter
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }


    public function historyApproved(){
        try{
            $user = auth()->user();
            $history = HistoryApproved::join('users', 'history_approved.approved_by', '=', 'users.id')
            ->join('citizen_charter', 'history_approved.citizenCharterId', '=', 'citizen_charter.id')
            ->where('history_approved.approved_by', $user['id'])
            ->select(
                'history_approved.*',
                'users.name as approver_name',
                'citizen_charter.citizen_no',
                'citizen_charter.status'
            )
             ->orderBy('history_approved.created_at', 'desc')
            ->get();

                return response()->json([
                'message' => 'Updated successfully!',
                'data' => $history
            ], 200);
         }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
