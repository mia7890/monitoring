<?php

namespace App\Http\Controllers;

use App\Models\FaeUser;
use App\Services\MonitoringAuth;
use App\Services\UploadService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);

        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        if (!$isAdmin && !$currentFaeId) {
            return back()->with('error', 'You must be logged in to update your profile picture.');
        }

        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $file = $request->file('profile_image');

            $oldPath = $isAdmin
                ? session('monitoring_admin_profile_image')
                : FaeUser::findOrFail($currentFaeId)->profile_image;

            $newPath = UploadService::storeFile(
                $file,
                $isAdmin ? 'admin_' : 'fae_' . $currentFaeId . '_',
                $oldPath
            );

            if ($isAdmin) {
                session(['monitoring_admin_profile_image' => $newPath]);
            } else {
                FaeUser::where('id', $currentFaeId)->update(['profile_image' => $newPath]);
            }
        }

        return back()->with('success', 'Profile picture updated successfully!');
    }
}