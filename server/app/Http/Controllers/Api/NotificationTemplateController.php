<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationTemplate\UpdateNotificationTemplateRequest;
use App\Http\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;

class NotificationTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('notifications.view');

        $templates = NotificationTemplate::orderBy('key')->orderBy('channel')->get();

        return response()->json([
            'data' => NotificationTemplateResource::collection($templates),
        ]);
    }

    public function show(NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('notifications.view');

        return response()->json([
            'data' => new NotificationTemplateResource($notificationTemplate),
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('notifications.manage');

        $notificationTemplate->update($request->validated());

        return response()->json([
            'message' => 'Template updated successfully.',
            'data' => new NotificationTemplateResource($notificationTemplate->fresh()),
        ]);
    }
}
