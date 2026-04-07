<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FinancialRecordAttachmentPreviewController extends Controller
{
    public function preview(Request $request, FinancialRecord $record, Media $media)
    {
        $this->authorizeRecordAccess($record);
        $media = $this->resolveMediaForRecord($record, $media);

        $mime = (string) ($media->mime_type ?? '');
        $fileUrl = route('financial-records.attachments.file', [
            'record' => $record->id,
            'media' => $media->id,
        ]);

        $type = 'download';
        if (str_starts_with($mime, 'image/')) {
            $type = 'image';
        } elseif ($mime === 'application/pdf') {
            $type = 'pdf';
        } elseif (in_array($mime, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true)) {
            $type = 'office';
        }

        $appUrl = (string) config('app.url');
        $isLikelyPublic = $appUrl !== '' && ! str_contains($appUrl, '127.0.0.1') && ! str_contains($appUrl, 'localhost');

        $embedUrl = $fileUrl;
        $officeViewerUrl = null;

        if ($type === 'office') {
            if ($isLikelyPublic) {
                $officeViewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($fileUrl);
                $embedUrl = $officeViewerUrl;
            } else {
                $embedUrl = null;
            }
        }

        return view('attachments.preview', [
            'record' => $record,
            'media' => $media,
            'mime' => $mime,
            'type' => $type,
            'fileUrl' => $fileUrl,
            'embedUrl' => $embedUrl,
            'isLikelyPublic' => $isLikelyPublic,
            'officeViewerUrl' => $officeViewerUrl,
        ]);
    }

    public function file(Request $request, FinancialRecord $record, Media $media)
    {
        $this->authorizeRecordAccess($record);
        $media = $this->resolveMediaForRecord($record, $media);

        $path = $media->getPath();

        if (! $path || ! is_file($path)) {
            abort(404);
        }

        $mime = (string) ($media->mime_type ?? 'application/octet-stream');
        $filename = ($media->file_name ?: 'file');

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    protected function authorizeRecordAccess(FinancialRecord $record): void
    {
        $user = $this->getUser($record);

        if (! $user) {
            abort(403);
        }

        $isPrivileged = $user->hasAnyRole(['super_admin', 'admin', 'Admin', 'Super admin', 'editor', 'Editor']);

        if (! $isPrivileged && $user->hasRole('user')) {
            if ((int) $user->department_id !== (int) $record->department_id) {
                abort(403);
            }
        }

        if ($user->can('View:FinancialRecord') || $user->can('ViewAny:FinancialRecord') || $isPrivileged) {
            return;
        }

        abort(403);
    }

    protected function resolveMediaForRecord(FinancialRecord $record, Media $media): Media
    {
        $recordMedia = $record->media()
            ->whereKey($media->getKey())
            ->where('collection_name', 'financial-record-attachments')
            ->first();

        if (! $recordMedia) {
            abort(404);
        }

        return $recordMedia;
    }

    protected function getUser(FinancialRecord $record)
    {
        return auth()->user();
    }
}

