<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ViolationRepositoryInterface;
use App\Services\NotificationService;
use App\Providers\PHPMailerService;
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
            $existing = $this->violations->findViolationById($id);
            if (!$existing) {
                return response()->json(['message' => 'Violation not found'], 404);
            }

            $data = $request->only([
                'status', 'severity', 'violation_type', 'description',
                'location', 'violator_name', 'contact_number',
            ]);

            // Auto-stamp resolved_at when transitioning to Resolved
            if (
                isset($data['status']) &&
                $data['status'] === 'Resolved' &&
                $existing->status !== 'Resolved'
            ) {
                $data['resolved_at'] = now()->toDateString();
            }

            // Clear resolved_at if it's reopened
            if (
                isset($data['status']) &&
                $data['status'] !== 'Resolved' &&
                $existing->status === 'Resolved'
            ) {
                $data['resolved_at'] = null;
            }

            $violation = $this->violations->findAndUpdateViolationById($id, $data);

            // Auto-restore permit to Approved when violation is resolved/dismissed
            // (only if no other open violations remain on that permit)
            $permitReactivated = false;
            if (
                isset($data['status']) &&
                in_array($data['status'], ['Resolved', 'Dismissed']) &&
                $existing->permit_id
            ) {
                $stillOpen = \App\Models\Violation::where('permit_id', $existing->permit_id)
                    ->where('id', '!=', $id)
                    ->whereIn('status', ['Open', 'Investigating'])
                    ->exists();
                if (!$stillOpen) {
                    $permit = \App\Models\Permit::find($existing->permit_id);
                    if ($permit && $permit->status === 'Suspended') {
                        $permit->update(['status' => 'Approved']);
                        $permitReactivated = true;
                    }
                }
            }

            // Notify the permit owner about status change
            if (
                isset($data['status']) &&
                $data['status'] !== $existing->status &&
                $existing->permit_id &&
                $existing->permit
            ) {
                $permitOwnerId = $existing->permit->created_by ?? null;
                if ($permitOwnerId) {
                    $permitNo = $existing->permit->permit_no;

                    if ($data['status'] === 'Resolved') {
                        $title   = "Violation resolved on {$permitNo}";
                        $message = $permitReactivated
                            ? "Good news! The violation on your permit {$permitNo} has been marked as Resolved. Your permit is now reactivated and ready to use."
                            : "The violation on your permit {$permitNo} has been marked as Resolved.";
                        $severity = 'success';
                    } elseif ($data['status'] === 'Dismissed') {
                        $title   = "Violation dismissed on {$permitNo}";
                        $message = $permitReactivated
                            ? "The violation on your permit {$permitNo} has been dismissed. Your permit is now reactivated and ready to use."
                            : "The violation on your permit {$permitNo} has been dismissed.";
                        $severity = 'success';
                    } elseif ($data['status'] === 'Investigating') {
                        $title    = "Violation on {$permitNo} under investigation";
                        $message  = "An officer is now investigating the violation recorded on your permit {$permitNo}.";
                        $severity = 'info';
                    } else {
                        $title    = "Violation status updated: {$data['status']}";
                        $message  = "The violation on your permit {$permitNo} is now marked as {$data['status']}.";
                        $severity = 'info';
                    }

                    NotificationService::notifyUser($permitOwnerId, [
                        'type' => 'violation.status_changed',
                        'title' => $title,
                        'message' => $message,
                        'link' => '/permits',
                        'severity' => $severity,
                    ]);
                }
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

    public function reportPublic(Request $request, string $permitNo, PHPMailerService $mailer)
    {
        try {
            $permit = \App\Models\Permit::with('creator')->where('permit_no', $permitNo)->first();
            if (!$permit) {
                return response()->json(['message' => 'Permit not found'], 404);
            }

            $user = auth()->user();
            $data = $request->only([
                'violator_name', 'contact_number', 'vehicle_plate', 'location', 'lat', 'lng',
                'violation_type', 'severity', 'description',
            ]);

            $data['permit_id']     = $permit->id;
            $data['date_recorded'] = now()->toDateString();
            $data['status']        = 'Open';
            $data['recorded_by']   = $user['id'] ?? $permit->created_by;

            if (!empty($permit->lat) && empty($data['lat'])) $data['lat'] = $permit->lat;
            if (!empty($permit->lng) && empty($data['lng'])) $data['lng'] = $permit->lng;

            $violation = $this->violations->create($data);

            // Auto-suspend the permit so it can't be used while under investigation
            if (!in_array($permit->status, ['Suspended', 'Expired', 'Rejected'])) {
                $permit->update(['status' => 'Suspended']);
            }

            // Notify the permit owner (applicant) — their permit got flagged
            NotificationService::notifyUser($permit->created_by, [
                'type'     => 'violation.reported',
                'title'    => "Violation reported on {$permit->permit_no}",
                'message'  => "A {$data['severity']} violation ({$data['violation_type']}) was recorded against your permit.",
                'link'     => '/permits',
                'severity' => 'critical',
            ]);

            // Email the permit owner (applicant)
            if (!empty($permit->creator->email)) {
                $subject = "Violation Reported on Permit {$permit->permit_no}";
                $body = "
                    <p>Dear <strong>{$permit->creator->name}</strong>,</p>
                    <p>
                        We are writing to inform you that a violation has been reported against your permit
                        <strong>{$permit->permit_no}</strong>.
                    </p>
                    <p>
                        <strong>Violation type:</strong> {$data['violation_type']}<br>
                        <strong>Date recorded:</strong> {$data['date_recorded']}
                    </p>
                    <p>
                        <strong>Important:</strong> Your permit has been <strong>suspended</strong> while
                        this case is reviewed. It cannot be used or downloaded until the violation is
                        resolved or dismissed by an officer.
                    </p>
                    <p>
                        Please visit the DENR-CENRO office immediately with a valid ID and a copy of
                        your permit, along with any supporting documents related to the incident.
                    </p>
                    <br>
                    <p>Thank you,</p>
                    <p>
                        <strong>
                            Department of Environment and Natural Resources (DENR)<br>
                            Community Environment and Natural Resources Office (CENRO)<br>
                            Brgy. Duhat, Santa Cruz, Laguna<br>
                            Phone: (049) 501-1234 · Email: cenro.santacruz@denr.gov.ph
                        </strong>
                    </p>
                ";
                $mailer->send($permit->creator->email, $subject, $body);
            }

            // Notify staff/admin so they can review/investigate
            NotificationService::notifyStaff([
                'type'     => 'violation.new',
                'title'    => "New violation on {$permit->permit_no}",
                'message'  => "{$data['violation_type']} ({$data['severity']}) recorded against permit {$permit->permit_no}.",
                'link'     => '/violations',
                'severity' => $data['severity'] === 'Critical' ? 'critical' : 'warning',
            ]);

            return response()->json([
                'message' => 'Violation reported successfully!',
                'data'    => $violation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listPublicByPermitNo(string $permitNo)
    {
        try {
            $permit = \App\Models\Permit::where('permit_no', $permitNo)->first();
            if (!$permit) {
                return response()->json(['message' => 'Permit not found'], 404);
            }
            $data = $this->violations->getViolationsByPermitId($permit->id);
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
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
