<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffController extends Controller
{
    /**
     * List members of the current workspace with their roles.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $members = $this->workspace($request)
            ->members()
            ->orderBy('name')
            ->get();

        return StaffResource::collection($members);
    }

    /**
     * Attach an existing user to the workspace with a role.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $workspace = $this->workspace($request);
        $data = $request->validated();

        $user = User::where('email', $data['email'])->firstOrFail();

        if ($workspace->members()->whereKey($user->id)->exists()) {
            return response()->json(['message' => 'User is already a member of this workspace.'], 422);
        }

        $workspace->members()->attach($user->id, ['role' => $data['role']]);

        $member = $workspace->members()->whereKey($user->id)->first();

        return (new StaffResource($member))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $staff): StaffResource
    {
        $member = $this->workspace($request)->members()->whereKey($staff)->firstOrFail();

        return new StaffResource($member);
    }

    /**
     * Change a member's role and/or active state.
     */
    public function update(UpdateStaffRequest $request, int $staff): StaffResource
    {
        $workspace = $this->workspace($request);
        $workspace->members()->whereKey($staff)->firstOrFail();

        $data = $request->validated();
        $pivot = [];

        if (array_key_exists('role', $data)) {
            $pivot['role'] = $data['role'];
        }
        if (array_key_exists('isActive', $data)) {
            $pivot['is_active'] = $data['isActive'];
        }

        if ($pivot !== []) {
            $workspace->members()->updateExistingPivot($staff, $pivot);
        }

        $member = $workspace->members()->whereKey($staff)->first();

        return new StaffResource($member);
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(Request $request, int $staff): JsonResponse
    {
        $workspace = $this->workspace($request);

        if ($workspace->owner_id === $staff) {
            return response()->json(['message' => 'The workspace owner cannot be removed.'], 422);
        }

        $workspace->members()->detach($staff);

        return response()->json(null, 204);
    }

    private function workspace(Request $request): Workspace
    {
        return Workspace::findOrFail($request->user()->current_workspace_id);
    }
}
