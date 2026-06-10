<?php

namespace App\Http\Controllers\pages\settings;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\DrmType;
use App\Models\Product;
use Illuminate\Http\Request;

class DrmTypeController extends Controller
{
    use HttpResponses;

    public function list(Request $request)
    {

        $this->checkPageAccess('settings.drm.type');

        $type = $request->query('type'); // or $request->type

        if ($type == 'check') {
            $products = Product::all();
            $existingDrmNames = DrmType::pluck('name')->map(fn ($n) => trim($n))->toArray();

            foreach ($products as $product) {
                $drmName = trim($product->drm_type_formatted);
                if ($drmName && ! in_array($drmName, $existingDrmNames)) {
                    DrmType::create(['name' => $drmName]);
                    $existingDrmNames[] = $drmName;
                }
            }
        }

        return view('content.pages.drm-types');

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
        $query = DrmType::query();

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
            'recordsTotal' => DrmType::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:drm_types,name',
        ]);

        $drmType = new DrmType;
        $drmType->name = $request->name;
        $drmType->save();

        activity('drm_type')->event('created')
            ->withProperties([
                'id'   => $drmType->id,
                'name' => $drmType->name,
            ])
            ->log('DRM Type created');


        return response()->json([
            'success' => true,
            'message' => 'DRM Type added successfully!',
        ]);
    }

    public function show($id)
    {

        $user = DrmType::findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:drm_types,name,' . $id,
        ]);

        $drmType = DrmType::findOrFail($id);

        $old = $drmType->name;

        $drmType->name = $request->name;
        $drmType->save();

        activity('drm_type')->event('updated')
            ->withProperties([
                'id'       => $drmType->id,
                'old_name' => $old,
                'new_name' => $drmType->name,
            ])
            ->log('DRM Type updated');

        return response()->json([
            'success'  => true,
            'message'  => 'DRM Type updated successfully!',
            'drm_type' => $drmType,
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

        $drmType = DrmType::find($id);
        if ($drmType) {
            activity('drm_type')->event('deleted')
                ->withProperties([
                    'id'   => $drmType->id,
                    'name' => $drmType->name,
                ])
                ->log('DRM Type deleted');

            $drmType->delete();

            return response()->json(['success' => true, 'message' => 'DRM Type deleted successfully!']);

        }

    }
}
