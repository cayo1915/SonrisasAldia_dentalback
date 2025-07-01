<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationCreateRequest;
use App\Services\NotificationsService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    use HasResponse;
    /** @var NotificationsService */
    private $notificationsService;
    public function __construct(NotificationsService $notificationsService)
    {
        $this->notificationsService = $notificationsService;
    }

    public function index(Request $request)
    {
        $withPagination = $this->validatePagination($request->only('perPage', 'page'));
        return $this->notificationsService->get($request->all(), $withPagination);
    }

    public function create(NotificationCreateRequest $request)
    {
        return $this->notificationsService->create($request->validated());
    }

    public function markNotificationAsViewed($id)
    {
        return $this->notificationsService->markNotificationAsViewed($id);
    }

    public function delete(int $iduser)
    {
        return $this->notificationsService->delete($iduser);
    }
}
