<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

trait FileUploadTrait
{
    /**
     * Upload a file and delete the old one if it exists.
     *
     * @param UploadedFile|null $file The new file instance
     * @param string $folder The folder name in 'storage/app/public'
     * @param string|null $oldPath The path of the existing file to delete
     * @return string|null Returns the new file path or null
     */
    public function uploadFile($file, $folder, $oldPath = null)
    {
        // If a new file is provided
        if ($file) {
            // 1. Delete the old file if it exists
            if ($oldPath) {
                $this->deleteFile($oldPath);
            }

            // 2. Store the new file and return the path
            return $file->store($folder, 'public');
        }

        // If no new file, return null (or you can handle this logic in controller)
        return null;
    }

    /**
     * Delete a file from storage.
     *
     * @param string|null $path
     * @return void
     */
    public function deleteFile($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
