<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReminderConfigRequest;
use App\Services\ReminderService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    use HasResponse;
    /** @var  ReminderService*/
    private $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function createOrUpdateReminderConfig(ReminderConfigRequest $request)
    {
        return $this->reminderService->createOrUpdateReminderConfig($request->validated());
    }
}
