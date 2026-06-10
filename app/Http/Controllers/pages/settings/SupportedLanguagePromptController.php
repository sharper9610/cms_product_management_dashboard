<?php

namespace App\Http\Controllers\pages\settings;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use App\Models\SupportedLanguagePrompt;
use Illuminate\Http\Request;

class SupportedLanguagePromptController extends Controller
{
    use HttpResponses;

    public function list()
    {
        $this->checkPageAccess('settings.prompt.management');

        return view('content.pages.prompt-management.supported-language');

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
        $query = SupportedLanguagePrompt::query();

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
                'interface' => $user->interface,
                'full_audio' => $user->full_audio,
                'subtitles' => $user->subtitles,
                'is_active' => $user->is_active,

            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => SupportedLanguagePrompt::count(), // Total Users count
            'recordsFiltered' => $totalFiltered, // Filtered count after search
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'interface' => 'required|string',
            'full_audio' => 'required|string',
            'status' => 'required|in:0,1', // must be 0 or 1
        ], [
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        $prompt = new SupportedLanguagePrompt;
        $prompt->name = $request->name;
        $prompt->interface = $request->interface;
        $prompt->full_audio = $request->full_audio;
        $prompt->subtitles = $request->subtitles;
        $prompt->is_active = $request->status ?? 0;
        $prompt->save();

        activity('supported_language')->event('created')
            ->withProperties([
                'id'        => $prompt->id,
                'name'      => $prompt->name,
                'interface' => $prompt->interface,
                'status'    => $prompt->is_active,
            ])
            ->log('Supported Language Prompt created');

        return response()->json(['success' => true, 'message' => 'Prompt added successfully!']);

    }

    public function show($id)
    {

        $prompt = SupportedLanguagePrompt::findOrFail($id);

        return response()->json([
            'success' => true,
            'prompt' => [
                'id' => $prompt->id,
                'name' => $prompt->name,
                'interface' => $prompt->interface,
                'full_audio' => $prompt->full_audio,
                'subtitles' => $prompt->subtitles,
                'status' => $prompt->is_active,

            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $prompt = SupportedLanguagePrompt::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255',
            'interface'  => 'required|string',
            'subtitles'  => 'required|string',
            'full_audio' => 'required|string',
            'status'     => 'required|in:0,1',
        ], [
            'status.in' => 'Status must be either Active (1) or Inactive (0)',
        ]);

        $old = [
            'name'      => $prompt->name,
            'interface' => $prompt->interface,
            'status'    => $prompt->is_active,
        ];

        $prompt->name       = $request->name;
        $prompt->interface  = $request->interface;
        $prompt->full_audio = $request->full_audio;
        $prompt->subtitles  = $request->subtitles;
        $prompt->is_active  = $request->status ?? 0;
        $prompt->save();

        activity('supported_language')->event('updated')
            ->withProperties([
                'id'  => $prompt->id,
                'old' => $old,
                'new' => [
                    'name'      => $prompt->name,
                    'interface' => $prompt->interface,
                    'status'    => $prompt->is_active,
                ],
            ])
            ->log('Supported Language Prompt updated');

        return response()->json([
            'success' => true,
            'message' => 'Prompt updated successfully!',
        ]);
    }

    public function destroy($id)
    {
        $prompt = SupportedLanguagePrompt::find($id);

        if ($prompt) {
            activity('supported_language')->event('deleted')
                ->withProperties([
                    'id'   => $prompt->id,
                    'name' => $prompt->name,
                ])
                ->log('Supported Language Prompt deleted');

            $prompt->delete();

            return response()->json([
                'success' => true,
                'message' => 'Prompt deleted successfully!',
            ]);
        }
    }
}
