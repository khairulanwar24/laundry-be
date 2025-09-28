<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Permissions\OutletPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Outlet\InviteEmployeeRequest;
use App\Http\Requests\Api\V1\Outlet\StoreOutletRequest;
use App\Http\Resources\Api\V1\OutletResource;
use App\Http\Resources\Api\V1\UserOutletResource;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserOutlet;
use App\Services\Api\V1\OutletService;
use App\Services\Api\V1\UserService;
use App\Support\ResponseJson;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutletController extends Controller
{
    use AuthorizesRequests, ResponseJson;

    public function __construct(
        private OutletService $outletService,
        private UserService $userService
    ) {}

    /**
     * Display list of outlets for authenticated user.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Get outlets for the user using direct query to avoid relationship method issues
        $userOutlets = UserOutlet::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('outlet')
            ->get();

        $outlets = $userOutlets->map(function ($userOutlet) {
            $outlet = $userOutlet->outlet;
            // Add user role and permissions for each outlet
            $outlet->user_role = $userOutlet->role;
            $outlet->user_permissions = $userOutlet->permissions_json;

            return $outlet;
        });

        return $this->ok(OutletResource::collection($outlets));
    }

    /**
     * Store a newly created outlet.
     */
    public function store(StoreOutletRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Outlet::class);

            $outlet = $this->outletService->createForOwner(
                Auth::user(),
                $request->validated()
            );

            // Add user role and permissions for the response using direct query
            $userOutlet = UserOutlet::where('outlet_id', $outlet->id)
                ->where('user_id', Auth::id())
                ->first();
            if ($userOutlet) {
                $outlet->user_role = $userOutlet->role;
                $outlet->user_permissions = $userOutlet->permissions_json;
            }

            return $this->created(new OutletResource($outlet), 'Outlet berhasil dibuat');
        } catch (Exception $e) {
            return $this->badRequest('Gagal membuat outlet: '.$e->getMessage());
        }
    }

    /**
     * Display the specified outlet.
     */
    public function show(Outlet $outlet): JsonResponse
    {
        $this->authorize('view', $outlet);

        $user = Auth::user();

        // Load the user's role and permissions for this outlet
        $userOutlet = UserOutlet::where('user_id', $user->id)
            ->where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->first();

        if ($userOutlet) {
            // Set the user role and permissions on the outlet model for the resource
            $outlet->user_role = $userOutlet->role;
            $outlet->user_permissions = $userOutlet->permissions_json;
        }

        return $this->ok(new OutletResource($outlet));
    }

    /**
     * Update the specified outlet.
     */
    public function update(StoreOutletRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('update', $outlet);

            $outlet->update($request->validated());

            return $this->ok(new OutletResource($outlet), 'Outlet berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui outlet: '.$e->getMessage());
        }
    }

    /**
     * Invite user to outlet.
     */
    public function invite(InviteEmployeeRequest $request, Outlet $outlet): JsonResponse
    {
        try {
            $this->authorize('invite', $outlet);

            $data = $request->validated();

            // Find or create user
            $user = $this->userService->findOrCreateByEmailOrPhone(
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['name'] ?? null
            );

            // Assign user to outlet
            $userOutlet = $this->outletService->assignUser(
                $outlet,
                $user,
                $data['role'],
                $data['permissions'] ?? null
            );

            // TODO: Send WhatsApp/Email invitation

            return $this->ok([
                'user_outlet' => new UserOutletResource($userOutlet),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                ],
            ], 'Undangan berhasil dikirim');
        } catch (Exception $e) {
            return $this->badRequest('Gagal mengundang user: '.$e->getMessage());
        }
    }

    /**
     * Update outlet member role and permissions.
     */
    public function updateMember(Request $request, Outlet $outlet, UserOutlet $member): JsonResponse
    {
        try {
            $this->authorize('invite', $outlet);

            // Validate request
            $request->validate([
                'role' => 'sometimes|in:owner,karyawan',
                'permissions' => 'sometimes|array',
                'permissions.*' => 'boolean',
            ]);

            $data = [];

            if ($request->has('role')) {
                $data['role'] = $request->input('role');

                // Update permissions based on new role
                $basePermissions = OutletPermissions::defaultsFor($data['role']);
                $overridePermissions = $request->input('permissions', []);
                $data['permissions_json'] = OutletPermissions::merge($basePermissions, $overridePermissions);
            } elseif ($request->has('permissions')) {
                // Only update permissions
                $basePermissions = OutletPermissions::defaultsFor($member->role);
                $overridePermissions = $request->input('permissions');
                $data['permissions_json'] = OutletPermissions::merge($basePermissions, $overridePermissions);
            }

            if (! empty($data)) {
                $member->update($data);
            }

            return $this->ok(new UserOutletResource($member->fresh()), 'Member berhasil diperbarui');
        } catch (Exception $e) {
            return $this->badRequest('Gagal memperbarui member: '.$e->getMessage());
        }
    }

    /**
     * Remove member from outlet (soft delete).
     */
    public function removeMember(Outlet $outlet, UserOutlet $member): JsonResponse
    {
        try {
            $this->authorize('invite', $outlet);

            // Prevent owner from removing themselves if they're the only owner
            if ($member->role === UserOutlet::ROLE_OWNER && $member->user_id === Auth::id()) {
                $ownerCount = UserOutlet::where('outlet_id', $outlet->id)
                    ->where('role', UserOutlet::ROLE_OWNER)
                    ->where('is_active', true)
                    ->count();

                if ($ownerCount <= 1) {
                    return $this->forbidden('Tidak dapat menghapus owner terakhir');
                }
            }

            // Soft remove: set is_active to false
            $member->update(['is_active' => false]);

            return $this->ok(null, 'Member dinonaktifkan');
        } catch (Exception $e) {
            return $this->badRequest('Gagal menonaktifkan member: '.$e->getMessage());
        }
    }
}
