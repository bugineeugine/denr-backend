<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PermitRepositoryInterface;
use App\Models\Permit;
use Illuminate\Support\Facades\File;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

use Illuminate\Support\Str;
class PermitController extends Controller
{
    protected $permits;

    public function __construct(PermitRepositoryInterface $permits){
        $this->permits = $permits;
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
            $permit_no = 'PERMIT-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
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
            $permitUrl = url('/permit/' . $permit_no);
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
            $permits = $this->permits->findPermitById($permitId);
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


}
