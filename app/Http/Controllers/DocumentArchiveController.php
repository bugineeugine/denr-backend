<?php

namespace App\Http\Controllers;

use App\Models\Permit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DocumentArchiveController extends Controller
{
    private function ensureAdmin()
    {
        $user = auth()->user();
        if (($user['role'] ?? null) !== 'admin') {
            abort(response()->json(['message' => 'Only admins may access the document archive.'], 403));
        }
    }

    private const CATEGORIES = [
        'requestLetter'       => 'Request Letter',
        'certificateBarangay' => 'Barangay Certificate',
        'orCr'                => 'OR / CR',
        'driverLicense'       => 'Driver\'s License',
        'otherDocuments'      => 'Other Documents',
    ];

    public function index(Request $request)
    {
        $this->ensureAdmin();
        try {
            $folder = public_path('storage/documents');
            $documents = [];

            $permits = Permit::with('creator:id,name,email')
                ->orderByDesc('created_at')
                ->get();

            foreach ($permits as $permit) {
                foreach (self::CATEGORIES as $field => $label) {
                    $filename = $permit->{$field} ?? null;
                    if (!$filename) continue;

                    $path = $folder . DIRECTORY_SEPARATOR . $filename;
                    $size = File::exists($path) ? filesize($path) : 0;
                    $mime = File::exists($path) ? mime_content_type($path) : 'application/octet-stream';

                    $documents[] = [
                        'permit_id'      => $permit->id,
                        'permit_no'      => $permit->permit_no,
                        'permit_status'  => $permit->status,
                        'category'       => $label,
                        'category_key'   => $field,
                        'filename'       => $filename,
                        'size'           => $size,
                        'mime'           => $mime,
                        'uploaded_by'    => $permit->creator?->name,
                        'uploaded_email' => $permit->creator?->email,
                        'uploaded_at'    => $permit->created_at?->toIso8601String(),
                        'exists'         => File::exists($path),
                    ];
                }
            }

            // Optional filters
            if ($request->filled('category')) {
                $cat = $request->query('category');
                $documents = array_values(array_filter($documents, fn ($d) => $d['category'] === $cat));
            }
            if ($request->filled('status')) {
                $status = $request->query('status');
                $documents = array_values(array_filter($documents, fn ($d) => $d['permit_status'] === $status));
            }
            if ($request->filled('q')) {
                $q = strtolower($request->query('q'));
                $documents = array_values(array_filter($documents, function ($d) use ($q) {
                    return str_contains(strtolower($d['permit_no']), $q)
                        || str_contains(strtolower($d['filename']), $q)
                        || str_contains(strtolower($d['uploaded_by'] ?? ''), $q)
                        || str_contains(strtolower($d['category']), $q);
                }));
            }

            return response()->json([
                'message' => 'Retrieve successfully!',
                'data'    => $documents,
                'meta'    => [
                    'total' => count($documents),
                    'categories' => array_values(self::CATEGORIES),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
