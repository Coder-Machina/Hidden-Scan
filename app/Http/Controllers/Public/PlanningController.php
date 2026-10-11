<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ScheduleService;

class PlanningController extends Controller
{
    public function index()
    {
        $schedule = ScheduleService::getHydratedSchedule();
        $todayKey = ScheduleService::getTodayKey();

        return view('public.planning', compact('schedule', 'todayKey'));
    }
}
