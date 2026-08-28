<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorProfile\CreateAuthorProfileRequest;
use App\Http\Requests\AuthorProfile\UpdateAuthorProfileRequest;
use App\Http\Requests\AuthorProfile\UpdateOwnAuthorProfileRequest;
use App\Models\AuthorProfile;
use App\Services\AuthorProfileService;
use Illuminate\Support\Facades\DB;

class AuthorProfileController extends Controller
{
    public function __construct(protected AuthorProfileService $authorProfileService) {}

    public function store(CreateAuthorProfileRequest $request)
    {
        $profile = DB::transaction(function () use ($request) {
            return $this->authorProfileService
                ->createWithUserData($request->validated())
                ->getModel()
                ->load('user:id,name,email');
        });

        return $this->success($this->modelActionMessage($profile, 'created'), [
            'author' => $profile,
        ]);
    }

    public function update(UpdateAuthorProfileRequest $request, AuthorProfile $authorProfile)
    {
        $profile = DB::transaction(function () use ($request, $authorProfile) {
            return $this->authorProfileService
                ->setModel($authorProfile)
                ->updateWithUserData($request->validated())
                ->getModel()
                ->load('user:id,name,email');
        });

        return $this->success($this->modelActionMessage($profile, 'updated'), [
            'author' => $profile,
        ]);
    }

    public function destroy(AuthorProfile $authorProfile)
    {
        $this->authorize('delete', $authorProfile);

        DB::transaction(function () use ($authorProfile) {
            $this->authorProfileService->setModel($authorProfile)->delete();
        });

        return $this->success($this->modelActionMessage($authorProfile, 'deleted'));
    }

    public function updateMe(UpdateOwnAuthorProfileRequest $request)
    {
        $profile = $request->user()->authorProfile;

        if ($profile === null) {
            return $this->error(__('errors.author_profile_not_found'), 404);
        }

        $profile = DB::transaction(function () use ($request, $profile) {
            return $this->authorProfileService
                ->setModel($profile)
                ->update($request->validated())
                ->getModel()
                ->load('user:id,name,email');
        });

        return $this->success($this->modelActionMessage($profile, 'updated'), [
            'author' => $profile,
        ]);
    }
}
