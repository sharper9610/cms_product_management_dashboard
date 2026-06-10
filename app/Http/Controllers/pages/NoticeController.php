<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('notice.view');

        return view('content.pages.notice');

    }

    public function index(Request $request)
    {
        $columns = [
            1 => 'title',
            2 => 'status',
        ];

        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        // Initialize Query
        $query = Notice::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('status', 'LIKE', "%{$search}%")
                ->orWhere('title', 'LIKE', "%{$search}%");
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
                'title' => $user->title,
                'details' => $user->details,
                'status' => $user->status,
                'start_date' => $user->start_date,
                'end_date' => $user->end_date,

            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => Notice::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:400',
        ]);
        $user = new Notice;
        $user->title = $request->title;
        $user->details = $request->details;

        $user->start_date = $request->start_date
          ? Carbon::createFromFormat('Y-m-d H:i:s', $request->start_date)->format('Y-m-d H:i:s')
          : null;

        $user->end_date = $request->end_date
          ? Carbon::createFromFormat('Y-m-d H:i:s', $request->end_date)->format('Y-m-d H:i:s')
          : null;

        $user->status = $request->status;
        $user->save();

        activity('notice')->event('added')
            ->withProperties([
                'id' => $user->id ?? '',
                'title' => $user->title ?? '',
            ])
            ->log('Notice added');

        return response()->json(['success' => true, 'message' => 'Notice added successfully!', 'user' => $user]);

    }

    public function show($id)
    {

        $user = Notice::findOrFail($id);

        return response()->json([
            'success' => true,
            'notice' => [
                'id' => $user->id,
                'title' => $user->title,
                'details' => $user->details,
                'status' => $user->status,
                'start_date' => $user->start_date
                  ? Carbon::parse($user->start_date)->format('Y-m-d H:i:s')
                  : null,
                'end_date' => $user->end_date
                  ? Carbon::parse($user->end_date)->format('Y-m-d H:i:s')
                  : null,
            ],

        ]);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'title' => 'required|string|max:400',

        ]);

        $user = Notice::find($id);

        if ($user) {

            $user->title = $request->title;
            $user->details = $request->details;
            // ✅ Convert only if not empty
            $user->start_date = $request->start_date
              ? Carbon::createFromFormat('Y-m-d H:i:s', $request->start_date)->format('Y-m-d H:i:s')
              : null;

            $user->end_date = $request->end_date
              ? Carbon::createFromFormat('Y-m-d H:i:s', $request->end_date)->format('Y-m-d H:i:s')
              : null;
            $user->status = $request->status;
            $user->save();

            activity('notice')->event('updated')
                ->withProperties([
                    'id' => $user->id ?? '',
                    'title' => $user->title ?? '',
                ])
                ->log('Notice updated');

            return response()->json(['success' => true, 'message' => 'Notice updated successfully!', 'user' => $user]);

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

        $notice = Notice::find($id);

        if ($notice) {

            activity('notice')->event('deleted')
                ->withProperties([
                    'id' => $notice->id ?? '',
                    'title' => $notice->title ?? '',
                ])
                ->log('Notice deleted');

            $notice->delete();

            return response()->json(['success' => true, 'message' => 'Notice deleted successfully!']);

        }

    }
}
