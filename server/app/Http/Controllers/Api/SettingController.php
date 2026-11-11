<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Http\Requests\Setting\UploadLogoRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->grouped(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->authorize('settings.update');

        $this->settings->updateMany($request->input('settings'));

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => $this->settings->grouped(),
        ]);
    }

    public function uploadLogo(UploadLogoRequest $request): JsonResponse
    {
        $this->authorize('settings.update');

        $path = $this->settings->uploadLogo($request->file('logo'));

        return response()->json([
            'message' => 'Logo uploaded successfully.',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }
}
