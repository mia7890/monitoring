<?php

namespace App\Http\Controllers;

use App\Models\FaeUser;
use App\Models\Task;
use App\Services\CalendarDataBuilder;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $role = MonitoringAuth::role();
        $isAdmin = MonitoringAuth::isAdmin();
        $isFae = MonitoringAuth::isFae();
        $currentFaeId = MonitoringAuth::faeId();
        $currentFaeName = MonitoringAuth::faeName();
        $currentFaeCode = MonitoringAuth::faeCode();
        $profileImage = MonitoringAuth::currentProfileImage();

        $month = (int)$request->query('month', date('n'));
        $year = (int)$request->query('year', date('Y'));

        $data = array_merge(
            compact(
                'role',
                'isAdmin',
                'isFae',
                'currentFaeId',
                'currentFaeName',
                'currentFaeCode',
                'profileImage'
            ),
            CalendarDataBuilder::build($month, $year, null, 8)
        );

        // System overview status counts
        $data['totalTasks'] = Task::count();
        $data['totalInProgressTasks'] = Task::where('status', 'In Progress')->count();
        $data['totalCompletedTasks'] = Task::where('status', 'Completed')->count();
        $data['totalActiveTasks'] = Task::whereIn('status', ['Pending', 'In Progress'])->count();
        $data['totalFAE'] = FaeUser::approved()->count();

        return view('landing', $data);
    }
}
