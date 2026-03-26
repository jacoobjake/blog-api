<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asset\UploadAssetRequest;
use App\Models\Asset;
use App\Services\AssetService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AssetController extends Controller
{
    public function __construct(protected AssetService $assetService) {}
    public function store(UploadAssetRequest $request)
    {
        $asset = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $data = Arr::except($validated, 'file');
            $file = $validated['file'];
            return $this->assetService->store($data)
                ->uploadMedia($file)
                ->getModel();
        });
        return $this->success($this->modelActionMessage($asset, 'uploaded'), [
            'uuid' => $asset->uuid,
        ]);
    }

    public function destroy(Asset $asset)
    {
        // Handle asset deletion
        if ($asset->user_id !== auth()->id()) {
            return $this->error(__("auth.unauthorized"), 403);
        }

        $this->assetService->setModel($asset)->delete();

        return $this->success($this->modelActionMessage($asset, 'deleted'));
    }
}
