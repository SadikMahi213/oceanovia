<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminEmail;
use App\Models\EmailLog;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailSenderController extends Controller
{
    public function index(): View
    {
        $logs = EmailLog::with('user')->latest()->paginate(20);

        return view('admin.emails.index', compact('logs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'recipients' => ['required', 'string', 'max:10000'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:50000'],
            'body_type' => ['required', 'in:text,html'],
        ]);

        $recipients = $this->parseRecipients($request->string('recipients'));

        if (empty($recipients)) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'At least one valid recipient email address is required.']);
        }

        $subject = trim((string) $request->string('subject'));
        $body = trim((string) $request->string('message'));
        $bodyType = $request->string('body_type');
        $batchId = (string) Str::uuid();

        foreach ($recipients as $email) {
            $log = EmailLog::create([
                'user_id' => $request->user()->id,
                'recipient_email' => $email,
                'subject' => $subject,
                'message' => $body,
                'body_type' => $bodyType,
                'status' => 'requested',
                'batch_id' => $batchId,
            ]);

            // All mail is delivered through the queue so a large list of
            // recipients never blocks the HTTP request.
            SendAdminEmail::dispatch($log);
        }

        // Reuse the existing audit/activity system.
        app(AuditService::class)->log('email.sent', null, null, [
            'batch_id' => $batchId,
            'recipient_count' => count($recipients),
            'subject' => $subject,
            'body_type' => $bodyType,
        ]);

        $notice = count($recipients) === 1
            ? 'Email queued for delivery.'
            : count($recipients).' emails queued for delivery.';

        return redirect()->route('admin.emails.index')->with('success', $notice);
    }

    /**
     * Split raw input on commas, newlines, spaces and semicolons, trim each
     * value, reject malformed addresses and remove duplicates.
     *
     * @return list<string>
     */
    private function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        $seen = [];
        $recipients = [];

        foreach ($parts as $part) {
            $email = trim($part);
            if ($email === '') {
                continue;
            }

            $validator = Validator::make(['email' => $email], [
                'email' => ['required', 'email:rfc,strict'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'recipients' => "{$email} is not a valid email address.",
                ]);
            }

            $key = strtolower($email);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $recipients[] = $email;
            }
        }

        return $recipients;
    }
}
