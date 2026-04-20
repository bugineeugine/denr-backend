<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ViolationRepositoryInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ViolationController extends Controller
{
    protected $violations;

    public function __construct(ViolationRepositoryInterface $violations)
    {
        $this->violations = $violations;
    }

    public function index()
    {
        try {
            $data = $this->violations->getAllViolations();
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not retrieve violations.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $data = $request->except(['evidence']);
            $data['recorded_by'] = $user['id'];

            if (empty($data['date_recorded'])) {
                $data['date_recorded'] = now()->toDateString();
            }

            if ($request->hasFile('evidence')) {
                $folder = public_path('storage/violations');
                if (!File::exists($folder)) {
                    File::makeDirectory($folder, 0777, true, true);
                }
                $file = $request->file('evidence');
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move($folder, $filename);
                $data['evidence'] = $filename;
            }

            $violation = $this->violations->create($data);

            return response()->json([
                'message' => 'Violation recorded successfully!',
                'data' => $violation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $violation = $this->violations->findViolationById($id);
            if (!$violation) {
                return response()->json(['message' => 'Violation not found'], 404);
            }
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $violation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $data = $request->all();
            $violation = $this->violations->findAndUpdateViolationById($id, $data);
            if (!$violation) {
                return response()->json(['message' => 'Violation not found'], 404);
            }
            return response()->json([
                'message' => 'Updated successfully!',
                'data' => $violation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $violation = $this->violations->findAndDeleteViolationById($id);
            if (!$violation) {
                return response()->json(['message' => 'Violation not found'], 404);
            }
            return response()->json([
                'message' => 'Deleted successfully!',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function byPermit(string $permitId)
    {
        try {
            $data = $this->violations->getViolationsByPermitId($permitId);
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reports(Request $request)
    {
        try {
            $from = $request->query('from');
            $to = $request->query('to');
            $data = $this->violations->getReports($from, $to);
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
