<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function approve(KycVerification $kycVerification): RedirectResponse
    {
        $kycVerification->update([
            'status'      => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC verification approved successfully.');
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
