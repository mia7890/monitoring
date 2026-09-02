<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    /**
     * File extensions accepted for task report attachments.
     */
    public const ATTACHMENT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'txt', 'zip'];

    /**
     * Store an uploaded file on the private local disk and delete the
     * previously stored file (if any). Returns the storage-relative path
     * that is persisted in the database.
     */
    public static function storeFile(UploadedFile $file, string $prefix, ?string $oldPath = null): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $prefix === ''
            ? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . bin2hex(random_bytes(8)) . '.' . $extension
            : $prefix . bin2hex(random_bytes(12)) . '.' . $extension;

        $storedPath = $file->storeAs('uploads', $filename, ['disk' => 'local']);

        if ($oldPath && $storedPath !== $oldPath) {
            self::delete($oldPath);
        }

        return $storedPath;
    }

    /**
     * Safely delete a stored file from the private disk if it exists.
     */
    public static function delete(?string $path): void
    {
        if (!$path || !self::isValidRelativePath($path)) {
            return;
        }

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Resolve a stored relative path to an absolute file path.
     *
     * New uploads live on the private local disk (storage/app/uploads).
     * Legacy uploads that predate this change live in public/uploads; they
     * are only served after authentication, which is strictly safer than
     * the previous public access.
     */
    public static function resolve(string $path): ?string
    {
        if (!self::isValidRelativePath($path)) {
            return null;
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        $publicPath = public_path($path);
        if (is_file($publicPath)) {
            return $publicPath;
        }

        return null;
    }

    /**
     * Build the authenticated download URL for a stored path.
     */
    public static function url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return route('files.show', ['path' => $path]);
    }

    private static function isValidRelativePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return !str_contains($normalized, '..')
            && !str_starts_with($normalized, '/')
            && preg_match('/^[a-zA-Z0-9_\/.\- ]+$/', $normalized) === 1;
    }
}