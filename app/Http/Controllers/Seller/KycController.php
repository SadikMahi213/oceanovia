<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KycController extends Controller
{
    public function index(): View
    {
        $kyc = KycVerification::with('verifier')
            ->ofUser(auth()->id())
            ->latest('id')
            ->first();

        return view('seller.kyc', compact('kyc'));
    }

    public function store(Request $request): RedirectResponse
    {
        $latest = KycVerification::ofUser(auth()->id())->latest('id')->first();

        if ($latest && $latest->status === 'approved') {
            return redirect()->route('seller.kyc.index')
                ->with('error', 'Your account is already verified and approved.');
        }

        $validated = $request->validate([
            'document_type'   => ['required', Rule::in(['passport', 'drivers_license', 'national_id', 'business_license'])],
            'document_number' => ['required', 'string', 'max:100'],
            'document_front'  => ['required', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'document_back'   => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'selfie'          => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $front  = $request->file('document_front')->store('seller-kyc', 'public');
        $back   = $request->hasFile('document_back') ? $request->file('document_back')->store('seller-kyc', 'public') : null;
        $selfie = $request->hasFile('selfie') ? $request->file('selfie')->store('seller-kyc', 'public') : null;

        $data = [
            'document_type'   => $validated['document_type'],
            'document_number' => $validated['document_number'],
            'document_front'  => $front,
            'document_back'   => $back,
            'selfie'          => $selfie,
            'status'          => 'pending',
            'verified_by'     => null,
            'verified_at'     => null,
            'admin_notes'     => null,
        ];

        if ($latest) {
            // Re-submission (rejected or previously pending) reviews in place
            // so a seller never collects duplicate review rows.
            $this->deleteOldFiles([$latest->document_front, $latest->document_back, $latest->selfie], [$front, $back, $selfie]);
            $latest->update($data);
        } else {
            KycVerification::create(array_merge(['user_id' => auth()->id()], $data));
        }

        return redirect()->route('seller.kyc.index')
            ->with('success', 'Your KYC documents have been submitted for review.');
    }

    private function deleteOldFiles(array $old, array $new): void
    {
        foreach ($old as $path) {
            if ($path && ! in_array($path, $new, true)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}