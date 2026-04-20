<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PermitRepositoryInterface;
use App\Models\Permit;
use Illuminate\Support\Facades\File;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use App\Models\HistoryApproved;
use Illuminate\Support\Str;
use App\Providers\PHPMailerService;
use App\Models\AppNotification;
class PermitController extends Controller
{
    protected $permits;

    public function __construct(PermitRepositoryInterface $permits){
        $this->permits = $permits;
    }

      public function send(PHPMailerService $mailer)
    {
        $success = $mailer->send(
            'bugineeugine06@gmail.com',
            'Laravel PHPMailer Test',
            '<h2>Hello!</h2><p>Email sent from Laravel using PHPMailer.</p>'
        );

        return $success
            ? response()->json(['message' => 'Email sent'])
            : response()->json(['message' => 'Failed to send email'], 500);
    }
    public function index(){
        try{
            $permits = $this->permits->getAllPermits();
              return response()->json([
                'message' => 'Retrieve successfully!',
                'data' =>$permits
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Could not retrieve permits.',
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
    public function create(Request $request){
        try{


            $user = auth()->user();
            logger()->info('AUTH USER', ['user' => auth()->user()]);
            $userId = $user['id'];
            $data = $request->except([
            'requestLetter', 'certificateBarangay', 'orCr', 'driverLicense', 'otherDocuments'
            ]);

            $nextId = Permit::count() + 1;
            $permit_no = 'APP-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            $data['permit_no'] = $permit_no;
            $data['created_by'] = $userId;
            $folder = public_path('storage/qrcodes');
            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0777, true, true);
            }
        $documentsFolder = public_path('storage/documents');


        if (!File::exists($documentsFolder)) {
            File::makeDirectory($documentsFolder, 0777, true);
        }


         $fileFields = [
                'requestLetter',
                'certificateBarangay',
                'orCr',
                'driverLicense',
                'otherDocuments',
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                    $file->move($documentsFolder, $filename);

                    $data[$field] = $filename;

                }
            }
            $permitUrl = config('app.frontend_url') . '/permit/' . $permit_no;
            $fileName = $permit_no . '.png';
            $filePath = $folder . '/' . $fileName;

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($permitUrl)
                ->size(200)
                ->margin(10)
                ->build();
            $result->saveToFile($filePath);
            $data['qrcode'] =  $fileName;
            $permits = $this->permits->create($data);

            AppNotification::create([
                'user_id' => $userId,
                'type' => 'permit.submitted',
                'title' => "Permit {$permit_no} submitted",
                'message' => "Your permit application {$permit_no} has been submitted for review.",
                'link' => '/permits',
                'severity' => 'info',
            ]);

            return response()->json([
                'message' => 'Created successfully!',
                'data' => $permits
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                'status'=>500
            ], 500);
        }

    }
    public function findAndUpdateById(Request $request,string $permitId){
        try{
            $data = $request->all();

            $permit = $this->permits->findAndUpdatePermitById($permitId, $data);

            if (!$permit) {
                return response()->json([
                    'message' => 'Permit not found'
                ], 404);
            }
            return response()->json([
                'message' => 'Updated successfully!',
                'data' => $permit
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function findAndDeleteById(string $permitId){
        try{

            $permitId = $this->permits->findAndDeletePermitById($permitId);


            if (!$permitId) {
                return response()->json([
                    'message' => 'Permit not found'
                ], 404);
            }
            $getPermit = $permitId['permit'];
            $permitNo = $getPermit['permit_no'];
            $filePath = public_path('storage/qrcodes/' . $permitNo . '.png');

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            return response()->json([
                'message' => 'Deleted successfully!',
                'data' => null
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function getPermitByUserId(string $userId){
        try{

            $permits = $this->permits->getPermitByUserId($userId);


            return response()->json([
             'message' => 'Retrieve successfully!',
                'data' => $permits
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }
      public function findPermitById(string $permitId){
        try{
            $permit = $this->permits->findPermitById($permitId);
            if (!$permit) {
                return response()->json([
                    'message' => 'Permit not found'
                ], 404);
            }
            return response()->json([
             'message' => 'Retrieve successfully!',
                'data' => $permit
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
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

            $citizenCharter = $this->permits->getPermitBySteps($steps);
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
    public function historyApprovedByPermitId(string $permitId){
      try{

           $history = HistoryApproved::query()
            ->join('permits', 'history_approved.permit_id', '=', 'permits.id')
            ->join('users', 'history_approved.approved_by', '=', 'users.id')
            ->where('history_approved.permit_id', $permitId)
            ->select(
                'history_approved.*',
                'users.name as approver_name',
                'users.email',
                'permits.permit_no',
                'permits.status'
            )
            ->orderByDesc('history_approved.created_at')
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


    public function notifyExpired(string $permitId, PHPMailerService $mailer){
        try {
            $permit = Permit::with('creator')->find($permitId);

            if (!$permit) {
                return response()->json(['message' => 'Permit not found'], 404);
            }

            $permit_no   = $permit->permit_no;
            $expiry_date = $permit->expiry_date ?? 'N/A';
            $email       = $permit->creator->email;
            $name        = $permit->creator->name;

            $subject = "Notice of Expiration – Permit Application No. {$permit_no}";
           $body = "
                <p>Dear <strong>{$name}</strong>,</p>

                <p>
                    We wish to inform you that your permit with application number
                    <strong>{$permit_no}</strong> has <strong>expired</strong> as of
                    <strong>{$expiry_date}</strong>.
                </p>

                <p>
                    If you wish to continue the activities covered by this permit, please visit the
                    <strong>DENR-CENRO</strong> office at your earliest convenience to apply for a
                    renewal or a new permit application.
                </p>

                <p>
                    Should you have any questions or concerns, do not hesitate to contact our office.
                </p>

                <br>

                <p>Thank you.</p>

                <p>
                    <strong>
                        Department of Environment and Natural Resources (DENR)<br>
                        Community Environment and Natural Resources Office (CENRO)<br>
                        Brgy. Duhat, Santa Cruz, Laguna
                    </strong>
                </p>
            ";

            $success = $mailer->send($email, $subject, $body);

            if (!$success) {
                return response()->json(['message' => 'Failed to send email'], 500);
            }

            return response()->json(['message' => 'Expiration notice sent successfully'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function renew(string $permitId){
        try {
            $existing = Permit::find($permitId);
            if (!$existing) {
                return response()->json(['message' => 'Permit not found'], 404);
            }

            $user = auth()->user();
            $nextId = Permit::count() + 1;
            $newPermitNo = 'APP-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $folder = public_path('storage/qrcodes');
            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0777, true, true);
            }
            $permitUrl = config('app.frontend_url') . '/permit/' . $newPermitNo;
            $fileName = $newPermitNo . '.png';
            $filePath = $folder . '/' . $fileName;
            try {
                $result = Builder::create()
                    ->writer(new PngWriter())
                    ->data($permitUrl)
                    ->size(200)
                    ->margin(10)
                    ->build();
                $result->saveToFile($filePath);
            } catch (\Exception $qrEx) {
                // GD extension missing — reuse the existing permit's QR
                $fileName = $existing->qrcode;
            }

            $issued = now();
            $expiry = now()->addYear();

            $newData = $existing->only([
                'permit_type','typeForestProduct','estimatedVolumeQuantity',
                'typeConveyancePlateNumber','consignee','dateOfTransport','landOwner',
                'contactNumber','species','lng','lat','requestLetter',
                'certificateBarangay','orCr','driverLicense','otherDocuments'
            ]);

            $newData['permit_no']    = $newPermitNo;
            $newData['qrcode']       = $fileName;
            $newData['created_by']   = $user['id'] ?? $existing->created_by;
            $newData['status']       = 'Pending';
            $newData['steps']        = 0;
            $newData['status_step']  = 'Forward to PENR/CENR Officer/Deputy CENR Officer';
            $newData['issued_date']  = $issued->format('m/d/Y');
            $newData['expiry_date']  = $expiry->format('m/d/Y');

            $renewed = Permit::create($newData);

            return response()->json([
                'message' => 'Permit renewal submitted successfully!',
                'data' => $renewed,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function findAndUpdatePermitById(Request $request,string $petmitId,PHPMailerService $mailer){
        try{

            $data = $request->all();
            $user = auth()->user();
            $findPrmitById = $this->permits->findPermitById($petmitId);
            if (!$findPrmitById) {
                return response()->json([
                    'message' => 'permit not found'
                ], 404);
            }

            $steps = $findPrmitById['steps'];
            $action = $findPrmitById['status_step'];


             if($steps == 0){
                $action = 'Forward toChief RPS (CENRO)/Chief TSD (Implementing PENRO)';
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
                $action = 'Inspect the forest products in the area, and prepare Inspection Report, and Certificate of Verification (COV) and affix initial duplicate copy of COV';
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


            $data['status_step'] = $action;
            if($steps != 8){
                $data['steps'] = $steps + 1;
            }

            $permit_no = $findPrmitById["permit_no"];
            if($steps == 8){
                $data['steps']  = 9;
                $data['status'] = 'Approved';
                $action = 'Done';
                 $subject = "Notice of Approval Permit Application No. {$permit_no}";
           $body = "
                    <p>Good day,</p>

                    <p>
                        This is to formally inform you that your permit application with
                        application number <strong>{$permit_no}</strong> has been
                        <strong>approved</strong> and is now marked as <strong>Completed</strong>.
                    </p>

                    <p>
                        Please print the attached document from the website for the required signature.
                        Once you have received this email, you may proceed to the office of
                        <strong>DENR-CENRO</strong> at your convenience for the processing and release
                        of your approved documents.
                    </p>

                    <br>

                    <p>Thank you.</p>

                    <p>
                    <strong>
                        Department of Environment and Natural Resources (DENR)<br>
                        Community Environment and Natural Resources Office (CENRO)<br>
                        Brgy. Duhat, Santa Cruz, Laguna
                    </strong>
                </p>
                ";

                      $success = $mailer->send(
                    $findPrmitById["creator"]["email"],
                    $subject,
                    $body
                );
                    if(!$success){
                    return response()->json([
                        'error' => 'Something went wrong',
                        'message' =>"'Something went wrong'"
                    ], 500);
                    }

            }


            $getPrmit = $this->permits->findAndUpdatePermitById($findPrmitById['id'], $data);

            if (!empty($findPrmitById['created_by'])) {
                if ($steps == 8) {
                    AppNotification::create([
                        'user_id' => $findPrmitById['created_by'],
                        'type' => 'permit.approved',
                        'title' => "Permit {$permit_no} approved",
                        'message' => "Your permit {$permit_no} has been approved and is now ready for release.",
                        'link' => '/permits',
                        'severity' => 'success',
                    ]);
                } else {
                    AppNotification::create([
                        'user_id' => $findPrmitById['created_by'],
                        'type' => 'permit.progress',
                        'title' => "Permit {$permit_no} progressed",
                        'message' => "Step update: {$action}",
                        'link' => '/permits',
                        'severity' => 'info',
                    ]);
                }
            }

            HistoryApproved::create([
                'action'=> $action,
                'permit_id' => $findPrmitById['id'],
                'approved_by'=>$user['id'],
                'steps' =>$data['steps']
            ]);

            return response()->json([
                'message' => 'Updated successfully!',
                'data' => $getPrmit
            ], 200);
        }  catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }

    }









}
