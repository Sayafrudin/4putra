<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

trait LogsActivity
{
    protected function logActivity(Request $request, string $action, string $description, ?string $module = null, ?array $metadata = null): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'module' => $module,
            'ip_address' => $request->ip(),
            'metadata' => $metadata ? json_encode($metadata) : null,
        ]);
    }

    /**
     * Catat perubahan data dengan detail old/new values dan preview gambar.
     */
    protected function logDataChange(Request $request, string $action, string $description, ?string $module, $oldData = null, $newData = null, ?array $imagePreviews = null): void
    {
        $metadata = [];

        if ($oldData !== null) {
            $metadata['old_values'] = $oldData instanceof \Illuminate\Database\Eloquent\Model ? $oldData->getOriginal() : $oldData;
        }

        if ($newData !== null) {
            $metadata['new_values'] = $newData instanceof \Illuminate\Database\Eloquent\Model ? $newData->getAttributes() : $newData;
        }

        if (! empty($imagePreviews)) {
            $metadata['image_previews'] = $imagePreviews;
        }

        $this->logActivity($request, $action, $description, $module, ! empty($metadata) ? $metadata : null);
    }
}
