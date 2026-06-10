<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('user.view');

        $data['roles'] = Role::all();

        $user = auth()->user();
        $userRole = $user->roles->first() ?? '';

        // If role is not super admin (id != 1), restrict roles
        if ($userRole && $userRole->id != 1) {
            $data['roles'] = Role::where('id', '>=', $userRole->id)->get();
        }

        $data['loginRoleId'] = auth()->user()->roles->first()->id ?? '';

        return view('content.pages.users', $data);

    }

    public function index(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'email',
        ];

        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        // Initialize Query
        $query = User::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        }

        // Get total filtered records count before applying limit & offset
        $totalFiltered = $query->count();

        // Apply sorting, limit, and offset
        $users = $query->orderBy($order, $dir)
            ->offset($start)
            ->limit($limit)
            ->get();

        // Format Data
        $data = [];
        $ids = $start;

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->id,
                'fake_id' => ++$ids,
                'name' => $user->name,
                'full_name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => '',
                'role' => $user->getRoleNames()[0] ?? '',
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => User::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:20',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).+$/',
            ],
            'role' => 'required|exists:roles,id',
        ], [
            'password.min'      => 'Password must be at least 20 characters.',
            'password.regex'    => 'Password must contain uppercase, lowercase, a number, and a special character.',
            'password.confirmed'=> 'Password confirmation does not match.',
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->created_by = auth()->user()->id ?? null;

        $user->password = Hash::make($request->password);

        if ($request->role) {
            $user->role_id = $request->role;
        }
        $user->save();

        $this->userLog($user, 'created');

        if ($request->role) {
            $user->role_id = $request->role;

            $role = Role::where('id', $request->role)->first()->name;
            $roleFormate = [];
            array_push($roleFormate, $role);
            $user->assignRole($roleFormate);
        }

        return response()->json(['success' => true, 'message' => 'User added successfully!', 'user' => $user]);

    }

    public function show($id)
    {

        $roleId = auth()->user()->roles->first()->id ?? '';
        $loginUserId = auth()->user()->id ?? '';

        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()->id ?? null,
            ],
            'loginUserRole' => $roleId,
            'loginUserId' => $loginUserId,
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'new_password' => [
                'nullable',
                'min:20',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).+$/',
            ],
            'role' => 'required|exists:roles,id',
        ], [
            'new_password.min'      => 'Password must be at least 20 characters.',
            'new_password.regex'    => 'Password must contain uppercase, lowercase, a number, and a special character.',
            'new_password.confirmed'=> 'Password confirmation does not match.',
        ]);

        $user = User::find($id);

        if ($user && $user->id == 1 && $user->roles->isNotEmpty() && $user->roles->first()->id != $request->role) {
            return response()->json([
                'success' => false,
                'message' => 'This user cannot be assigned a different role.',
            ]);
        }

        if ($user) {

            if ($user->id != 1 && auth()->user()->role_id != $request->role && auth()->user()->id == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to modify your own role.',
                ]);
            }

            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->new_password) {
                $user->password = Hash::make($request->new_password);
            }

            $user->role_id = $request->role;
            $user->save();

            if ($request->role) {
                if ($user->roles->isNotEmpty()) {
                    if ($request->role != $user->roles->first()->id) {
                        $user->roles()->detach();
                    } else {

                    }
                }

                $role = Role::find($request->role)?->name;
                if ($role) {
                    $user->assignRole([$role]);
                }
            }

            $this->userLog($user, 'updated');

            return response()->json(['success' => true, 'message' => 'User updated successfully!', 'user' => $user]);

        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $user = User::find($id);

        if ($user && $user->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ]);
        }

        if ($id == 1) {
            return response()->json(['success' => false, 'message' => 'The Super admin role cannot be deleted!']);
        } else {
            $this->userLog($user, 'deleted');
            $user->delete();

            return response()->json(['success' => true, 'message' => 'User deleted successfully!']);
        }

    }

    private function userLog($data, $event)
    {
        $data = [
            'id' => $data->id ?? '',
            'name' => $data->name ?? '',
            'email' => $data->email ?? '',
        ];
        activity('user')->event($event)
            ->withProperties($data)
            ->log('User '.$event);

    }
}
