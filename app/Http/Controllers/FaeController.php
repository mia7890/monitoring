<?php

namespace App\Http\Controllers;

use App\Mail\ContactApprovedMail;
use App\Mail\ContactDirectMail;
use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Task;
use App\Services\SmtpConnectivity;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FaeController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::where('is_active', true)
            ->orderBy('department_name', 'asc')
            ->get();

        $faeList = FaeUser::with(['tasks', 'department'])
            ->approved()
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($fae) {
                $tasks = $fae->tasks;
                $fae->total_tasks = $tasks->count();
                $fae->completed_tasks = $tasks->where('status', 'Completed')->count();
                $fae->avg_progress = $fae->total_tasks > 0 ? (float)$tasks->avg('progress') : 0;
                $fae->department_name = $fae->department?->department_name ?? 'Unassigned';
                return $fae;
            });

        $pendingList = FaeUser::with('department')
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('fae.index', compact('faeList', 'pendingList', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fae_code' => 'nullable|string|max:100|unique:fae_users,fae_code',
            'email' => 'nullable|email|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $name = trim($request->input('name'));
        $faeCode = trim($request->input('fae_code'))
            ? strtoupper(trim($request->input('fae_code')))
            : $this->generateUniqueCode();

        $profileImagePath = null;
        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $profileImagePath = UploadService::storeFile($request->file('profile_image'), 'fae_');
        }

        FaeUser::create([
            'name' => $name,
            'fae_code' => $faeCode,
            'status' => 'approved',
            'email' => $request->input('email') ?: null,
            'department_id' => $request->input('department_id') ?: null,
            'phone' => $request->input('phone') ?: null,
            'profile_image' => $profileImagePath,
        ]);

        return redirect()->route('fae.index')->with('success', "Contact '{$name}' ({$faeCode}) added successfully!");
    }

    public function update(Request $request, FaeUser $fae)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fae_code' => 'required|string|max:100|unique:fae_users,fae_code,' . $fae->id,
            'email' => 'nullable|email|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $name = trim($request->input('name'));
        $faeCode = strtoupper(trim($request->input('fae_code')));

        $data = [
            'name' => $name,
            'fae_code' => $faeCode,
            'email' => $request->input('email') ?: null,
            'department_id' => $request->input('department_id') ?: null,
            'phone' => $request->input('phone') ?: null,
        ];

        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $data['profile_image'] = UploadService::storeFile(
                $request->file('profile_image'),
                'fae_',
                $fae->profile_image
            );
        }

        $fae->update($data);

        return redirect()->route('fae.index')->with('success', "Contact '{$name}' details updated successfully!");
    }

    public function approve(FaeUser $fae)
    {
        if ($fae->isApproved()) {
            return back()->with('error', "Contact '{$fae->name}' is already approved.");
        }

        $accessCode = $fae->fae_code ?: $this->generateUniqueCode();

        $fae->update([
            'status' => 'approved',
            'fae_code' => $accessCode,
        ]);

        $emailMsg = '';
        if ($fae->email) {
            $smtpFailure = SmtpConnectivity::failureReason();
            if ($smtpFailure !== null) {
                Log::warning('Contact approval email skipped: ' . $smtpFailure);
                $emailMsg = " Warning: Email delivery skipped ({$smtpFailure}). Access Code is {$accessCode}.";
            } else {
                try {
                    Mail::to($fae->email)->send(new ContactApprovedMail($fae->name, $accessCode));
                    $emailMsg = " Access Code sent via email to {$fae->email}.";
                } catch (\Throwable $e) {
                    Log::error('Failed to send contact approval email: ' . $e->getMessage());
                    $emailMsg = " Warning: Email sending failed (" . $e->getMessage() . "). Access Code is {$accessCode}.";
                }
            }
        }

        return redirect()->route('fae.index')->with('success', "Registration for '{$fae->name}' approved! Assigned Access Code: {$accessCode}.{$emailMsg}");
    }

    public function reject(FaeUser $fae)
    {
        $name = $fae->name;
        $fae->update(['status' => 'rejected']);

        return redirect()->route('fae.index')->with('success', "Registration for '{$name}' was rejected.");
    }

    public function destroy(FaeUser $fae)
    {
        $name = $fae->name;
        Task::where('fae_id', $fae->id)->update(['fae_id' => null]);

        if ($fae->profile_image) {
            UploadService::delete($fae->profile_image);
        }

        $fae->delete();

        return redirect()->route('fae.index')->with('success', "Contact '{$name}' removed successfully.");
    }

    public function sendEmail(Request $request, FaeUser $fae)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if (empty($fae->email)) {
            return back()->with('error', "Contact '{$fae->name}' does not have an email address configured.");
        }

        $subject = trim($request->input('subject'));
        $body = trim($request->input('message'));

        $smtpFailure = SmtpConnectivity::failureReason();
        if ($smtpFailure !== null) {
            Log::warning("Direct email skipped for {$fae->email}: {$smtpFailure}");
            return back()->with('error', "Email delivery is unavailable: {$smtpFailure}");
        }

        try {
            Mail::to($fae->email)->send(new ContactDirectMail($fae->name, $subject, $body));
            return back()->with('success', "Email sent successfully to {$fae->name} ({$fae->email})!");
        } catch (\Throwable $e) {
            Log::error("Failed to send direct email to {$fae->email}: " . $e->getMessage());
            return back()->with('error', "Failed to send email: " . $e->getMessage());
        }
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'CTC-' . strtoupper(Str::random(5));
        } while (FaeUser::where('fae_code', $code)->exists());

        return $code;
    }
}
