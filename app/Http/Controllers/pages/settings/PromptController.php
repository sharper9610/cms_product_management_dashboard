<?php

namespace App\Http\Controllers\pages\settings;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('settings.prompt.management');

        return view('content.pages.prompt-management.prompts');

    }

    public function index(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'description',
            3 => 'is_active',
        ];

        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        // Initialize Query
        $query = Prompt::query();

        // Filtering based on search value
        if (! empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
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
                'description' => $user->description,
                'is_active' => $user->is_active,

            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => Prompt::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'template' => 'required|string',
            'status' => 'required|in:0,1', // must be 0 or 1
        ], [
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        $prompt = new Prompt;
        $prompt->name = $request->name;
        $prompt->description = $request->description;
        $prompt->template = $request->template;
        $prompt->template_pt = $request->template_pt;
        $prompt->template_es = $request->template_es;
        $prompt->template_gift_card = $request->template_gift_card;
        $prompt->template_gift_card_pt = $request->template_gift_card_pt;
        $prompt->template_gift_card_es = $request->template_gift_card_es;
        $prompt->is_active = $request->status ?? 0;
        $prompt->save();

        activity('prompt')->event('created')
            ->withProperties([
                'id'     => $prompt->id,
                'name'   => $prompt->name,
                'status' => $prompt->is_active,
            ])
            ->log('Prompt created');

        return response()->json(['success' => true, 'message' => 'Prompt added successfully!']);

    }

    public function show($id)
    {

        $prompt = Prompt::findOrFail($id);

        return response()->json([
            'success' => true,
            'prompt' => [
                'id' => $prompt->id,
                'name' => $prompt->name,
                'description' => $prompt->description,
                'template' => $prompt->template,
                'template_pt' => $prompt->template_pt,
                'template_es' => $prompt->template_es,
                'template_gift_card' => $prompt->template_gift_card,
                'template_gift_card_pt' => $prompt->template_gift_card_pt,
                'template_gift_card_es' => $prompt->template_gift_card_es,
                'status' => $prompt->is_active,

            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $prompt = Prompt::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'template' => 'required|string',
            'status' => 'required|in:0,1',
        ], [
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        // Update fields
        $prompt->name = $request->name;
        $prompt->description = $request->description;
        $prompt->template = $request->template;
        $prompt->template_pt = $request->template_pt;
        $prompt->template_es = $request->template_es;
        $prompt->template_gift_card = $request->template_gift_card;
        $prompt->template_gift_card_pt = $request->template_gift_card_pt;
        $prompt->template_gift_card_es = $request->template_gift_card_es;
        $prompt->is_active = $request->status ?? 0;

        $prompt->save();

        activity('prompt')->event('updated')
            ->withProperties([
                'id'   => $prompt->id,
                'name' => $prompt->name,
            ])
            ->log('Prompt updated');

        return response()->json([
            'success' => true,
            'message' => 'Prompt updated successfully!',
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
        $prompt = Prompt::find($id);
        if ($prompt) {
            activity('prompt')->event('deleted')
                ->withProperties([
                    'id'   => $prompt->id,
                    'name' => $prompt->name,
                ])
                ->log('Prompt deleted');

            $prompt->delete();

            return response()->json(['success' => true, 'message' => 'Prompt deleted successfully!']);
        }
    }
}
