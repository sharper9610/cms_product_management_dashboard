<?php

namespace App\Http\Controllers\pages\settings;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\DlcProductPrompt;
use Illuminate\Http\Request;

class DlcProductIDPromptController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('settings.prompt.management');

        return view('content.pages.prompt-management.dlc-product-id');

    }

    public function index(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'template',
            3 => 'is_active',
        ];

        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        // Initialize Query
        $query = DlcProductPrompt::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%");
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
                'template' => $user->template,
                'is_active' => $user->is_active,

            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => DlcProductPrompt::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'template' => 'required|string',

            'status' => 'required|in:0,1', // must be 0 or 1
        ], [
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        $prompt = new DlcProductPrompt;
        $prompt->template = $request->template;
        $prompt->is_active = $request->status ?? 0;
        $prompt->save();

        activity('dlc_prompt')->event('created')
            ->withProperties([
                'id'       => $prompt->id,
                'template' => substr($prompt->template, 0, 100), // trim long templates
                'status'   => $prompt->is_active,
            ])
            ->log('DLC Product ID Prompt created');

        return response()->json(['success' => true, 'message' => 'Prompt added successfully!']);

    }

    public function show($id)
    {

        $prompt = DlcProductPrompt::findOrFail($id);

        return response()->json([
            'success' => true,
            'prompt' => [
                'id' => $prompt->id,
                'template' => $prompt->template,
                'status' => $prompt->is_active,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $prompt = DlcProductPrompt::findOrFail($id);

        $request->validate([
            'template' => 'required|string',
            'status'   => 'required|in:0,1',
        ], [
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        $old = [
            'template' => substr($prompt->template, 0, 100),
            'status'   => $prompt->is_active,
        ];

        $prompt->template  = $request->template;
        $prompt->is_active = $request->status ?? 0;
        $prompt->save();

        activity('dlc_prompt')->event('updated')
            ->withProperties([
                'id'  => $prompt->id,
                'old' => $old,
                'new' => [
                    'template' => substr($prompt->template, 0, 100),
                    'status'   => $prompt->is_active,
                ],
            ])
            ->log('DLC Product ID Prompt updated');

        return response()->json([
            'success' => true,
            'message' => 'Prompt updated successfully!',
        ]);
    }

    public function destroy($id)
    {
        $prompt = DlcProductPrompt::find($id);

        if ($prompt) {
            activity('dlc_prompt')->event('deleted')
                ->withProperties([
                    'id'       => $prompt->id,
                    'template' => substr($prompt->template, 0, 100),
                ])
                ->log('DLC Product ID Prompt deleted');

            $prompt->delete();

            return response()->json([
                'success' => true,
                'message' => 'Prompt deleted successfully!',
            ]);
        }
    }
}
