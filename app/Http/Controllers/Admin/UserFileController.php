<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserFileController extends Controller
{
    public function removeFile(Request $request)
    {
        // ── 1. Validate incoming data ────────────────────────────────────
        $request->validate([
            'id'            => 'required|integer|exists:users,id',
            'field'         => 'required|string|max:100',
            'filename'      => 'required|string|max:500',
            'download_link' => 'nullable|string|max:1000',
        ]);

        $user         = User::findOrFail($request->id);
        $field        = $request->field;
        $targetName   = $request->filename;       // "Resume.pdf"
        $targetPath   = $request->download_link;  // storage path (may be null)
        $disk         = config('voyager.storage.disk', 'public');
        $raw          = $user->{$field};

        // ── 2. Parse the current field value ────────────────────────────
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            // ── JSON array of file objects ───────────────────────────────
            $pathToDelete = null;
            $remaining    = [];

            foreach ($decoded as $file) {
                $name = is_array($file)
                    ? ($file['original_name'] ?? basename($file['download_link'] ?? ''))
                    : basename($file);

                $path = is_array($file)
                    ? ($file['download_link'] ?? '')
                    : $file;

                // Match by original_name first, fall back to download_link
                $isTarget = ($name === $targetName)
                         || (!empty($targetPath) && $path === $targetPath);

                if ($isTarget) {
                    $pathToDelete = $path; // remember path to delete from disk
                } else {
                    $remaining[] = $file;  // keep everything else untouched
                }
            }

            // Delete the physical file from storage
            if ($pathToDelete && Storage::disk($disk)->exists($pathToDelete)) {
                Storage::disk($disk)->delete($pathToDelete);
            }

            // Re-encode remaining files (or null if all removed)
            $newValue = count($remaining) > 0
                ? json_encode(array_values($remaining))
                : null;

        } else {
            // ── Plain string path (single file) ──────────────────────────
            $pathToDelete = $raw;

            $isTarget = (basename($raw) === $targetName)
                     || ($raw === $targetPath)
                     || ($raw === $targetName);

            if ($isTarget) {
                if (Storage::disk($disk)->exists($pathToDelete)) {
                    Storage::disk($disk)->delete($pathToDelete);
                }
                $newValue = null;
            } else {
                // Not a match — do nothing, return an error
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in the stored value.',
                ], 404);
            }
        }

        // ── 3. Persist the updated value ─────────────────────────────────
        $user->{$field} = $newValue;
        $user->save();

        // ── 4. Return the new JSON value so the JS can update the
        //       hidden _existing input without a page reload ──────────────
        return response()->json([
            'success'   => true,
            'message'   => 'File removed successfully.',
            'new_value' => $newValue, // null or updated JSON string
        ]);
    }
}
