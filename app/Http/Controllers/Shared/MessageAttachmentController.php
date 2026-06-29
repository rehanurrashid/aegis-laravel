<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves message attachment files securely.
 * No public symlink required — streams directly from storage/app/public.
 * Auth-gated: only participants in the thread can download.
 */
class MessageAttachmentController extends Controller
{
    public function download(Request $request, Message $message, int $index): StreamedResponse|Response
    {
        $user = $request->user();

        // Verify the user is a participant in this thread
        $participantIds = json_decode($message->thread?->participant_ids ?? '[]', true) ?: [];
        if (!in_array($user->id, $participantIds, true)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $attachments = $message->attachments ?? [];
        $attachment  = $attachments[$index] ?? null;

        if (!$attachment || empty($attachment['path'])) {
            abort(404, 'Attachment not found.');
        }

        $path = $attachment['path'];

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found in storage.');
        }

        $name = $attachment['name'] ?? basename($path);
        $mime = $attachment['mime'] ?? 'application/octet-stream';

        return Storage::disk('public')->download($path, $name, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . addslashes($name) . '"',
        ]);
    }
}
