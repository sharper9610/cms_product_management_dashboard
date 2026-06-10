<?php

namespace App\Http\Controllers\pages\settings;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\ProductPublisher;
use Illuminate\Http\Request;

class ProductPublisherController extends Controller
{
    use HttpResponses;

    public function list()
    {
        $this->checkPageAccess('settings.publisher.management');

        return view('content.pages.prompt-management.publishers');

    }

    public function index(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'name',

        ];

        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        // Initialize Query
        $query = ProductPublisher::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%");
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

            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => ProductPublisher::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $publisher = new ProductPublisher;
        $publisher->name = $request->name;
        $publisher->source = 2;

        $publisher->save();

        activity('publisher')->event('created')
            ->withProperties([
                'id'   => $publisher->id,
                'name' => $publisher->name,
            ])
            ->log('Publisher created');

        return response()->json(['success' => true, 'message' => 'Publisher added successfully!']);

    }

    public function show($id)
    {

        $publisher = ProductPublisher::findOrFail($id);

        return response()->json([
            'success' => true,
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name,

            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $publisher = ProductPublisher::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldName = $publisher->name;

        $publisher->name = $request->name;
        $publisher->save();

        activity('publisher')->event('updated')
            ->withProperties([
                'id'       => $publisher->id,
                'old_name' => $oldName,
                'new_name' => $publisher->name,
            ])
            ->log('Publisher updated');

        return response()->json([
            'success' => true,
            'message' => 'Publisher updated successfully!',
        ]);
    }

    public function destroy($id)
    {
        $publisher = ProductPublisher::find($id);

        if ($publisher) {
            activity('publisher')->event('deleted')
                ->withProperties([
                    'id'   => $publisher->id,
                    'name' => $publisher->name,
                ])
                ->log('Publisher deleted');

            $publisher->delete();

            return response()->json([
                'success' => true,
                'message' => 'Publisher deleted successfully!',
            ]);
        }
    }
}
