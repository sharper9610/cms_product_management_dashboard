<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\ApiUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiUserController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('user.view');

        return view('content.pages.api-users');

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
        $query = ApiUser::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('domain', 'LIKE', "%{$search}%");
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
                'email' => $user->email,
                'ip' => $user->ip,
                'domain' => $user->domain,
                'status' => $user->status,

            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => ApiUser::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:20', // minimum 20 characters
                'regex:/[a-z]/',      // at least one lowercase letter
                'regex:/[A-Z]/',      // at least one uppercase letter
                'regex:/[0-9]/',      // at least one number
                'regex:/[\W_]/',      // at least one special character
                'confirmed',          // matches password_confirmation
            ],
            'domain' => [
                'required',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', // domain like example.com
            ],
            'ip' => [
                'required',
                'ip', // validates IPv4 or IPv6
            ],
            'status' => 'required|in:0,1', // must be 0 or 1
        ], [
            // Custom error messages
            'password.regex' => 'Password must have at least one uppercase, one lowercase, one number, and one special character',
            'domain.regex' => 'Please enter a valid domain (e.g., example.com)',
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        $user = new ApiUser;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->domain = $request->domain;
        $user->ip = $request->ip;
        $user->status = $request->status;
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'API user added successfully!', 'user' => $user]);

    }

    public function show($id)
    {

        $user = ApiUser::findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'domain' => $user->domain,
                'ip' => $user->ip,
                'status' => $user->status,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = ApiUser::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email', // ignore current user's email
            'new_password' => [
                'nullable',            // optional
                'string',
                'min:20',              // minimum 20 characters
                'regex:/[a-z]/',       // at least one lowercase
                'regex:/[A-Z]/',       // at least one uppercase
                'regex:/[0-9]/',       // at least one number
                'regex:/[\W_]/',       // at least one special character
                'confirmed',           // matches new_password_confirmation
            ],
            'domain' => [
                'required',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            ],
            'ip' => [
                'required',
                'ip',
            ],
            'status' => 'required|in:0,1',
        ], [
            'new_password.regex' => 'Password must have at least one uppercase, one lowercase, one number, and one special character',
            'domain.regex' => 'Please enter a valid domain (e.g., example.com)',
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        // Update fields
        $user->name = $request->name;
        $user->email = $request->email;
        $user->domain = $request->domain;
        $user->ip = $request->ip;
        $user->status = $request->status;

        // Only update password if new password is provided
        if (! empty($request->new_password)) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'API user updated successfully!',
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = ApiUser::find($id);
        if ($user) {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'API user deleted successfully!',
            ]);
        }

    }
}
