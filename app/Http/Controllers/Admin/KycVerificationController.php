<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminEmail;
use App\Models\EmailLog;
use App\Models\KycVerification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KycVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = KycVerification::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $kycVerifications = $query->latest()->paginate(15);

        return view('admin.kyc.index', compact('kycVerifications'));
    }

    public function show(KycVerification $kycVerification): View
    {
        $kycVerification->load(['user', 'verifier']);

        return view('admin.kyc.show', compact('kycVerification'));
    }

    public function approve(KycVerification $kycVerification, AuditService $auditService): RedirectResponse
    {
        $oldStatus = $kycVerification->status;
        $wasApproved = $oldStatus === 'approved';

        $user = $kycVerification->user;
        $profile = $user?->sellerProfile;
        $granted = false;

        DB::transaction(function () use ($kycVerification, $user, $profile, &$granted): void {
            $kycVerification->update([
                'status'      => 'approved',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            if ($profile) {
                $profile->update([
                    'status'              => 'approved',
                    'verification_status' => 'verified',
                ]);
                $granted = true;
            } elseif ($user && $user->isSeller()) {
                // A seller who registered directly via /register has no
                // seller profile yet, so the old approval would change
                // nothing and product creation would stay blocked. Create
                // the approved profile now so approval actually grants
                // product-creation access.
                $created = $user->sellerProfile()->create([
                    'store_name'          => $user->name."'s Store",
                    'status'              => 'approved',
                    'verification_status' => 'verified',
                ]);
                $granted = (bool) $created;
            }
        });

        $auditService->log(
            'kyc.approved',
            $kycVerification,
            ['status' => $oldStatus, 'user_id' => $kycVerification->user_id],
            [
                'status'      => 'approved',
                'verified_by' => auth()->id(),
                'granted'     => $granted ? 'seller_product_creation' : false,
            ]
        );

        // Notify and email only on a real transition (never when re-approving
        // an already-approved verification) and only for the seller flow.
        if (! $wasApproved && $user && $user->isSeller()) {
            $this->notifySellerApproved($user);
            $this->queueApprovalEmail($user);
        }

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC verification approved successfully.');
    }

    /**
     * Post an in-app congratulations notification, guarded so repeated
     * approvals never produce duplicates.
     */
    private function notifySellerApproved(User $user): void
    {
        $title = 'Congratulations! 🎉 Your seller account has been approved.';

        $exists = UserNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('title', $title)
            ->exists();

        if ($exists) {
            return;
        }

        UserNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'success',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => [
                'name'       => $user->name,
                'store_name' => $user->sellerProfile?->store_name,
                'message'    => 'Your seller account has been approved. You can now add products and start selling on '.config('app.name').'.',
            ],
            'title' => $title,
            'icon'  => 'badge-check',
            'link'  => route('seller.dashboard'),
        ]);
    }

    /**
     * Queue the approval email through the existing admin email pipeline
     * (EmailLog + SendAdminEmail/Resend). The job is the only place that
     * marks delivery as sent — never a fake success.
     */
    private function queueApprovalEmail(User $user): void
    {
        $subject = 'Congratulations! Your Account Has Been Approved';

        if (EmailLog::query()->where('user_id', $user->id)->where('subject', $subject)->exists()) {
            return;
        }

        $appName = (string) config('app.name');
        $body = "Hi {$user->name},"."\n\n"
            ."Congratulations! Your seller account has been approved on {$appName}."."\n\n"
            .'You can now add and publish products, manage inventory and pricing, and track orders and payouts.'."\n"
            .'If you have any questions, reply to this email and our team will help you.'."\n\n"
            ."Happy selling!\n"
            ."The {$appName} Team";

        $log = EmailLog::create([
            'user_id'         => $user->id,
            'recipient_email' => $user->email,
            'recipient_name'  => $user->name,
            'subject'         => $subject,
            'message'         => $body,
            'body_type'       => 'text',
            'status'          => 'requested',
        ]);

        SendAdminEmail::dispatch($log);
    }

    public function reject(Request $request, KycVerification $kycVerification): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $kycVerification->update([
            'status'      => 'rejected',
            'admin_notes' => $validated['admin_notes'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC verification rejected.');
    }
}
