<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Services\ActivityLogger;

class BackupController extends Controller
{
    private function ensureAdmin()
    {
        $user = auth()->user();
        if (($user['role'] ?? null) !== 'admin') {
            abort(response()->json(['message' => 'Only admins may access backups.'], 403));
        }
    }

    private function backupPath(string $file = ''): string
    {
        $base = storage_path('app/backups');
        if (!File::exists($base)) {
            File::makeDirectory($base, 0775, true);
        }
        return $file ? $base . DIRECTORY_SEPARATOR . $file : $base;
    }

    public function index()
    {
        $this->ensureAdmin();
        try {
            $folder = $this->backupPath();
            $files = collect(File::files($folder))
                ->map(fn ($f) => [
                    'name' => $f->getFilename(),
                    'size' => $f->getSize(),
                    'created_at' => date('c', $f->getMTime()),
                ])
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $files,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        $this->ensureAdmin();
        try {
            $db = config('database.connections.mysql');
            $filename = 'backup_' . date('Y-m-d_His') . '.sql';
            $filepath = $this->backupPath($filename);

            // Find mysqldump - allow override via env
            $dumpCmd = env('MYSQLDUMP_PATH', 'mysqldump');

            $passwordArg = $db['password'] ? '-p' . escapeshellarg($db['password']) : '';
            // Redirect output to file directly via shell - avoids Symfony Process buffering
            // and the Winsock issue that arises when piping stdout back through PHP.
            $shellCmd = sprintf(
                '"%s" --protocol=tcp -h %s -P %s -u %s %s --single-transaction --routines --triggers --default-character-set=utf8mb4 %s > %s 2>&1',
                $dumpCmd,
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                $passwordArg,
                escapeshellarg($db['database']),
                escapeshellarg($filepath)
            );

            $output = [];
            $exitCode = 0;
            exec($shellCmd, $output, $exitCode);

            if ($exitCode !== 0) {
                $errorMsg = implode("\n", $output);
                if (File::exists($filepath)) {
                    $errorMsg = $errorMsg ?: File::get($filepath);
                    File::delete($filepath);
                }
                throw new \Exception("mysqldump failed (exit {$exitCode}): " . $errorMsg);
            }
            ActivityLogger::logRaw('backup.created', 'system.backup', $filename, $filename);

            return response()->json([
                'message' => "Backup created: {$filename}",
                'data' => [
                    'name' => $filename,
                    'size' => filesize($filepath),
                    'created_at' => date('c', filemtime($filepath)),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Backup failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function download(string $name)
    {
        $this->ensureAdmin();
        $safe = basename($name);
        $path = $this->backupPath($safe);
        if (!File::exists($path)) {
            return response()->json(['message' => 'Backup not found'], 404);
        }
        return response()->download($path, $safe, ['Content-Type' => 'application/sql']);
    }

    public function destroy(string $name)
    {
        $this->ensureAdmin();
        $safe = basename($name);
        $path = $this->backupPath($safe);
        if (!File::exists($path)) {
            return response()->json(['message' => 'Backup not found'], 404);
        }
        File::delete($path);
        ActivityLogger::logRaw('backup.deleted', 'system.backup', $safe, $safe);
        return response()->json(['message' => 'Backup deleted'], 200);
    }

    public function restore(string $name)
    {
        $this->ensureAdmin();
        try {
            $safe = basename($name);
            $path = $this->backupPath($safe);
            if (!File::exists($path)) {
                return response()->json(['message' => 'Backup not found'], 404);
            }

            $db = config('database.connections.mysql');
            $mysqlCmd = env('MYSQL_PATH', 'mysql');

            $passwordArg = $db['password'] ? '-p' . escapeshellarg($db['password']) : '';
            // Pipe the file directly via shell stdin redirect (< file) — avoids the
            // Winsock 10106 issue that occurs when PHP streams the input itself.
            $shellCmd = sprintf(
                '"%s" --protocol=tcp -h %s -P %s -u %s %s --default-character-set=utf8mb4 %s < %s 2>&1',
                $mysqlCmd,
                escapeshellarg($db['host']),
                escapeshellarg((string) $db['port']),
                escapeshellarg($db['username']),
                $passwordArg,
                escapeshellarg($db['database']),
                escapeshellarg($path)
            );

            $output = [];
            $exitCode = 0;
            exec($shellCmd, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new \Exception("mysql restore failed (exit {$exitCode}): " . implode("\n", $output));
            }

            ActivityLogger::logRaw('backup.restored', 'system.backup', $safe, $safe);
            return response()->json(['message' => "Database restored from {$safe}"], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Restore failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
