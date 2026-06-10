<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('role.view');

        $data['roles'] = Role::get();
        $data['permissions'] = Permission::get()->groupBy('group_name');

        return view('content.pages.app-access-roles', $data);

    }

    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->withCount('users')->get();

        return response()->json($roles);

    }

    public function store(Request $request)
    {
        $this->checkPageAccess('role.create');
        $request->validate([
            'roleName' => 'required|string|unique:roles,name',
            'permission' => 'array',
        ]);

        $role = Role::create(['name' => $request->roleName]);

        if ($request->has('permission')) {
            $role->permissions()->sync(array_keys($request->permission));
        }

        activity('role')->event('created')
            ->withProperties([
                'name' => $request->roleName,
            ])
            ->log('Created Role');

        return response()->json(['success' => true, 'message' => 'Role added successfully!', 'role' => $role]);
    }

    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json($role);
    }

    public function edit($id)
    {
        $this->checkPageAccess('role.edit');
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json($role);
    }

    public function update(Request $request, $id)
    {

        if ($id == '1') {
            $request->validate([
                'roleName' => 'required|string|unique:roles,name,'.$id,
                'permission' => [
                    'required',
                    'array',
                    function ($attribute, $value, $fail) {
                        $requiredPermissions = [1, 2, 3, 4, 5, 6, 7, 8];

                        // Ensure all required permissions are present in the submitted array
                        if (array_diff($requiredPermissions, $value)) {
                            $fail('The super admin role must have both user and role all permissions.');
                        }
                    },
                ],
            ]);

        } else {
            $request->validate([
                'roleName' => 'required|string|unique:roles,name,'.$id,
                'permission' => 'array',
            ]);

        }

        $role = Role::findOrFail($id);

        activity('role')->event('updated')
            ->withProperties([
                'old_name' => $role->name,
                'updated_name' => $request->roleName,
            ])
            ->log('Updated Role');

        $role->update(['name' => $request->roleName]);

        if ($request->has('permission')) {
            $role->permissions()->sync(array_keys($request->permission));
        }

        $roleId = auth()->user()->roles->first()->id ?? '';

        return response()->json(['success' => true,
            'message' => 'Role updated successfully!',
            'isLoginUserRoleUpdate' => $id == $roleId ? true : false,
        ]);
    }

    public function destroy($id)
    {
        $this->checkPageAccess('role.delete');

        if ($id == 1) {
            return response()->json(['success' => false, 'message' => 'The Super admin role cannot be deleted!']);
        } else {
            $role = Role::findOrFail($id);

            activity('role')->event('deleted')
                ->withProperties([
                    'id' => $role->id,
                    'name' => $role->name,
                ])
                ->log('Deleted Role');

            $role->permissions()->detach();
            $role->delete();

            return response()->json(['success' => true, 'message' => 'Role deleted successfully']);

        }

    }
}
