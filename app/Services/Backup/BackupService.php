<?php

namespace App\Services\Backup;

use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Buat backup database (Dump SQL).
     */
    public function createDatabaseBackup(User $creator): BackupLog
    {
        $startedAt = now();
        $timestamp = date('Ymd_His');
        $filename = "backup_jmm_db_{$timestamp}.sql";
        $filePath = "backups/{$filename}";
        $fullPath = storage_path("app/{$filePath}");

        $subDir = dirname($fullPath);
        if (!File::isDirectory($subDir)) {
            File::makeDirectory($subDir, 0755, true);
        }

        try {
            $sqlContent = "-- PT JAYA MUKTI MANDIRI - DATABASE BACKUP\n";
            $sqlContent .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
            $sqlContent .= "-- Created by: {$creator->name} ({$creator->email})\n\n";
            $sqlContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            $tables = [
                'roles',
                'users',
                'divisions',
                'categories',
                'banks',
                'bank_accounts',
                'company_settings',
                'disposition_sequences',
                'dispositions',
                'disposition_items',
                'disposition_recipients',
                'disposition_approvals',
                'payments',
                'attachments',
                'audit_logs',
            ];

            foreach ($tables as $table) {
                // Periksa apakah tabel ada
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    continue;
                }

                $rows = DB::table($table)->get();
                $sqlContent .= "-- Table: {$table} (" . count($rows) . " rows)\n";

                foreach ($rows as $row) {
                    $array = (array) $row;
                    $columns = array_keys($array);
                    $values = array_values($array);

                    $escapedValues = array_map(function ($val) {
                        if ($val === null) return 'NULL';
                        return "'" . addslashes((string)$val) . "'";
                    }, $values);

                    $sqlContent .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                }
                $sqlContent .= "\n";
            }

            $sqlContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";

            File::put($fullPath, $sqlContent);
            $fileSize = File::size($fullPath);

            $backup = BackupLog::create([
                'backup_type' => BackupLog::TYPE_SQL,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'status' => 'completed',
                'created_by' => $creator->id,
                'started_at' => $startedAt,
                'completed_at' => now(),
                'notes' => 'Backup SQL Database selesai dengan aman.',
                'created_at' => now(),
            ]);

            AuditLog::record('backup_database_created', $backup, [], [
                'file_name' => $filename,
                'file_size' => $fileSize,
            ]);

            return $backup;
        } catch (Exception $e) {
            return BackupLog::create([
                'backup_type' => BackupLog::TYPE_SQL,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => 0,
                'status' => 'failed',
                'created_by' => $creator->id,
                'started_at' => $startedAt,
                'completed_at' => now(),
                'notes' => 'Gagal: ' . $e->getMessage(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Buat backup berkas lampiran (ZIP Archive).
     */
    public function createAttachmentsBackup(User $creator): BackupLog
    {
        $startedAt = now();
        $timestamp = date('Ymd_His');
        $filename = "backup_jmm_attachments_{$timestamp}.zip";
        $filePath = "backups/{$filename}";
        $fullPath = storage_path("app/{$filePath}");

        try {
            $zip = new ZipArchive();
            if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Tidak dapat membuat file ZIP pada {$fullPath}");
            }

            $storagePublic = storage_path('app/public');
            $filesAdded = 0;

            if (File::isDirectory($storagePublic)) {
                $files = File::allFiles($storagePublic);
                foreach ($files as $file) {
                    $relative = substr($file->getPathname(), strlen($storagePublic) + 1);
                    $zip->addFile($file->getPathname(), $relative);
                    $filesAdded++;
                }
            }

            // Tambahkan file manifest ke dalam zip
            $manifest = [
                'system' => 'JMM Disposisi',
                'company' => 'PT Jaya Mukti Mandiri',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $creator->name,
                'total_files' => $filesAdded,
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            clearstatcache(true, $fullPath);
            $fileSize = File::exists($fullPath) ? File::size($fullPath) : 0;

            $backup = BackupLog::create([
                'backup_type' => BackupLog::TYPE_ATTACHMENTS,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'status' => 'completed',
                'created_by' => $creator->id,
                'started_at' => $startedAt,
                'completed_at' => now(),
                'notes' => "Arsip berkas lampiran berisi {$filesAdded} berkas selesai.",
                'created_at' => now(),
            ]);

            AuditLog::record('backup_attachments_created', $backup, [], [
                'file_name' => $filename,
                'file_size' => $fileSize,
            ]);

            return $backup;
        } catch (Exception $e) {
            return BackupLog::create([
                'backup_type' => BackupLog::TYPE_ATTACHMENTS,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => 0,
                'status' => 'failed',
                'created_by' => $creator->id,
                'started_at' => $startedAt,
                'completed_at' => now(),
                'notes' => 'Gagal: ' . $e->getMessage(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Buat paket backup pemulihan bencana lengkap (Full Disaster Recovery Package ZIP).
     */
    public function createFullBackup(User $creator): BackupLog
    {
        $startedAt = now();
        $timestamp = date('Ymd_His');
        $filename = "backup_jmm_full_{$timestamp}.zip";
        $filePath = "backups/{$filename}";
        $fullPath = storage_path("app/{$filePath}");

        try {
            // 1. Buat SQL dump sementara
            $dbBackup = $this->createDatabaseBackup($creator);
            $sqlFullPath = storage_path("app/{$dbBackup->file_path}");

            // 2. Gabungkan ke dalam ZIP utama
            $zip = new ZipArchive();
            if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Gagal membuat arsip ZIP pemulihan bencana.");
            }

            if (File::exists($sqlFullPath)) {
                $zip->addFile($sqlFullPath, 'database/' . $dbBackup->file_name);
            }

            $storagePublic = storage_path('app/public');
            $filesCount = 0;
            if (File::isDirectory($storagePublic)) {
                $files = File::allFiles($storagePublic);
                foreach ($files as $file) {
                    $relative = 'attachments/' . substr($file->getPathname(), strlen($storagePublic) + 1);
                    $zip->addFile($file->getPathname(), $relative);
                    $filesCount++;
                }
            }

            $manifest = [
                'backup_type' => 'full_disaster_recovery',
                'system' => 'JMM Disposisi - PT Jaya Mukti Mandiri',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $creator->name . ' (' . $creator->email . ')',
                'database_file' => 'database/' . $dbBackup->file_name,
                'attachment_files_count' => $filesCount,
                'sha256' => File::exists($sqlFullPath) ? hash_file('sha256', $sqlFullPath) : null,
            ];

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            clearstatcache(true, $fullPath);
            $fileSize = File::exists($fullPath) ? File::size($fullPath) : 0;

            $backup = BackupLog::create([
                'backup_type' => BackupLog::TYPE_ZIP,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'status' => 'completed',
                'created_by' => $creator->id,
                'started_at' => $startedAt,
                'completed_at' => now(),
                'notes' => "Paket pemulihan lengkap (Database SQL + {$filesCount} berkas lampiran) berhasil diarsipkan.",
                'created_at' => now(),
            ]);

            AuditLog::record('backup_full_package_created', $backup, [], [
                'file_name' => $filename,
                'file_size' => $fileSize,
            ]);

            return $backup;
        } catch (Exception $e) {
            return BackupLog::create([
                'backup_type' => BackupLog::TYPE_ZIP,
                'file_name' => $filename,
                'file_path' => $filePath,
                'file_size' => 0,
                'status' => 'failed',
                'created_by' => $creator->id,
                'started_at' => $startedAt,
                'completed_at' => now(),
                'notes' => 'Gagal: ' . $e->getMessage(),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Verifikasi integritas arsip backup tanpa merusak data live (Integrity Test).
     */
    public function verifyBackup(BackupLog $backup): array
    {
        $fullPath = storage_path("app/{$backup->file_path}");
        clearstatcache(true, $fullPath);

        if (!File::exists($fullPath)) {
            return [
                'valid' => false,
                'message' => 'Berkas fisik backup tidak ditemukan di penyimpanan server.',
            ];
        }

        $size = File::size($fullPath);
        if ($size === 0) {
            return [
                'valid' => false,
                'message' => 'Berkas backup kosong (0 bytes).',
            ];
        }

        if (str_ends_with($backup->file_name, '.zip')) {
            $zip = new ZipArchive();
            $res = $zip->open($fullPath);
            if ($res !== true) {
                return [
                    'valid' => false,
                    'message' => 'Struktur arsip ZIP korup atau tidak dapat dibaca.',
                ];
            }
            $numFiles = $zip->numFiles;
            $hasManifest = $zip->locateName('manifest.json') !== false;
            $zip->close();

            return [
                'valid' => true,
                'message' => "Arsip ZIP valid & utuh. Terdiri dari {$numFiles} berkas di dalamnya" . ($hasManifest ? ' beserta manifest verifikasi.' : '.'),
                'size' => $size,
                'entries' => $numFiles,
            ];
        } elseif (str_ends_with($backup->file_name, '.sql')) {
            $firstBytes = File::get($fullPath);
            $isValidSql = str_contains($firstBytes, 'JAYA MUKTI MANDIRI') || str_contains($firstBytes, 'INSERT INTO');

            return [
                'valid' => $isValidSql,
                'message' => $isValidSql ? 'Berkas dump SQL database valid dan memiliki header yang sesuai.' : 'Berkas SQL tidak memiliki pola data yang valid.',
                'size' => $size,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Berkas backup terverifikasi dengan ukuran ' . $backup->formatted_size . '.',
            'size' => $size,
        ];
    }
}
