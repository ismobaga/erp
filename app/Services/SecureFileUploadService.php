<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SecureFileUploadService
{
    /**
     * Validate and store an uploaded file securely.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $modelType The type of model this attachment belongs to
     * @param int $modelId The ID of the model
     * @param int $uploadedById The ID of the user uploading the file
     * @param int $companyId The company ID for scoping
     * @return Attachment The created attachment record
     *
     * @throws ValidationException
     */
    public function storeFile(
        UploadedFile $file,
        string $modelType,
        int $modelId,
        int $uploadedById,
        int $companyId,
    ): Attachment {
        $this->validateFile($file);

        $fileName = $this->generateSecureFileName($file);
        $relativePath = $this->getStoragePath($modelType, $fileName);
        $disk = (string) config('erp.documents.disk', 'local');

        // Store the file with secure path
        Storage::disk($disk)->put($relativePath, $file->getContent());

        // Create attachment record
        return Attachment::create([
            'company_id' => $companyId,
            'attachable_type' => $modelType,
            'attachable_id' => $modelId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'mime_type' => $this->getSecureMimeType($file),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $uploadedById,
            'category' => $this->categorizeFile($file),
        ]);
    }

    /**
     * Validate uploaded file for security issues.
     *
     * @throws ValidationException
     */
    private function validateFile(UploadedFile $file): void
    {
        $maxSize = (int) config('erp.documents.max_upload_kb', 10240) * 1024;
        $allowedExtensions = config('erp.documents.allowed_extensions', [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'csv',
            'jpg',
            'jpeg',
            'png',
            'zip',
            'txt'
        ]);

        // Check file size
        if ($file->getSize() > $maxSize) {
            $maxMb = $maxSize / 1024 / 1024;
            throw ValidationException::withMessages([
                'file' => "Le fichier dépasse la taille maximale autorisée ({$maxMb} MB).",
            ]);
        }

        // Validate extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => "L'extension de fichier '{$extension}' n'est pas autorisée.",
            ]);
        }

        // Validate MIME type using fileinfo
        $detectedMimeType = $this->getFileMimeType($file);
        if (!$this->isAllowedMimeType($detectedMimeType)) {
            throw ValidationException::withMessages([
                'file' => "Le type de fichier détecté '{$detectedMimeType}' n'est pas autorisé.",
            ]);
        }

        // Additional checks for specific file types
        $this->validateFileSignature($file, $extension);
    }

    /**
     * Detect actual MIME type from file content.
     */
    private function getFileMimeType(UploadedFile $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        return (string) $mimeType;
    }

    /**
     * Get secure MIME type (from file content, not client header).
     */
    private function getSecureMimeType(UploadedFile $file): string
    {
        return $this->getFileMimeType($file);
    }

    /**
     * Validate file signature (magic bytes) to prevent disguised files.
     */
    private function validateFileSignature(UploadedFile $file, string $extension): void
    {
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            throw ValidationException::withMessages(['file' => 'Impossible de lire le fichier.']);
        }

        $header = fread($handle, 8);
        fclose($handle);

        $signatures = [
            'pdf' => '\x25\x50\x44\x46',  // %PDF
            'jpg' => '\xFF\xD8\xFF',      // JPG magic bytes
            'jpeg' => '\xFF\xD8\xFF',      // JPEG magic bytes
            'png' => '\x89\x50\x4E\x47',  // PNG magic bytes
            'zip' => '\x50\x4B\x03\x04',  // ZIP magic bytes
        ];

        if (isset($signatures[$extension])) {
            if (strpos($header, $signatures[$extension]) === false) {
                throw ValidationException::withMessages([
                    'file' => 'Le fichier n\'a pas une signature valide pour son extension.',
                ]);
            }
        }
    }

    /**
     * Check if MIME type is allowed.
     */
    private function isAllowedMimeType(string $mimeType): bool
    {
        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/x-zip-compressed',
        ];

        return in_array($mimeType, $allowed, true);
    }

    /**
     * Generate a secure filename to prevent directory traversal.
     */
    private function generateSecureFileName(UploadedFile $file): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        // Remove special characters and limit length
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $baseName);
        $baseName = substr($baseName, 0, 100);

        // Add timestamp and random hash to prevent collisions
        return sprintf('%s_%d_%s.%s', $baseName, time(), bin2hex(random_bytes(4)), $extension);
    }

    /**
     * Get the storage path based on model type.
     */
    private function getStoragePath(string $modelType, string $fileName): string
    {
        $baseDirectory = trim((string) config('erp.documents.directory', 'attachments'), '/');
        $modelPath = strtolower(str_replace('\\', '/', class_basename($modelType)));

        return "{$baseDirectory}/{$modelPath}/{$fileName}";
    }

    /**
     * Categorize file based on extension.
     */
    private function categorizeFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'pdf', 'doc', 'docx', 'txt' => 'document',
            'xls', 'xlsx', 'csv' => 'spreadsheet',
            'jpg', 'jpeg', 'png' => 'image',
            'zip' => 'archive',
            default => 'other',
        };
    }
}
