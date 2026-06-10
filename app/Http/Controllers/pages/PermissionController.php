<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use HttpResponses;

    public function permissionManagement()
    {

        $this->checkPageAccess('user.view');

        return view('content.pages.pages-permission');

    }

    public function index(Request $request)
    {
        $columns = [
            1 => 'name',
            2 => 'group_name',
        ];

        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        // Initialize Query
        $query = Permission::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('group_name', 'LIKE', "%{$search}%");
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
                'group_name' => $user->group_name,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => Permission::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|unique:permissions,name',
            'group_name' => 'required|string|max:255',

        ]);
        $user = new Permission;
        $user->name = $request->name;
        $user->group_name = $request->group_name;
        $user->guard_name = 'web';
        $user->save();

        activity('permission')->event('created')
            ->withProperties(['name' => $user->name, 'group_name' => $user->group_name])
            ->log('Permission created');

        return response()->json(['success' => true, 'message' => 'Permission added successfully!', 'user' => $user]);

    }

    public function show($id)
    {

        $user = Permission::findOrFail($id);

        return response()->json([
            'success' => true,
            'permission' => [
                'id' => $user->id,
                'name' => $user->name,
                'group_name' => $user->group_name,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => ['required', Rule::unique('permissions', 'name')->ignore($id)],
            'group_name' => 'required|string|max:255',
        ]);

        $user = Permission::find($id);

        if ($user) {

            $user->name = $request->name;
            $user->group_name = $request->group_name;
            $user->save();

            activity('permission')->event('updated')
                ->withProperties(['id' => $id, 'name' => $user->name, 'group_name' => $user->group_name])
                ->log('Permission updated');

            return response()->json(['success' => true, 'message' => 'Permission updated successfully!', 'user' => $user]);

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
        $user = Permission::find($id);

        activity('permission')->event('deleted')
            ->withProperties(['id' => $id, 'name' => $user->name])
            ->log('Permission deleted');

        $user->delete();

        return response()->json(['success' => true, 'message' => 'Permission deleted successfully!']);
    }
}
