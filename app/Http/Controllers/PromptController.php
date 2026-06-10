<?php

namespace App\Http\Controllers;

use App\Services\Openai\OpenAIService;
use App\Services\Openai\PromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    protected PromptService $promptService;
    protected OpenAIService $openAIService;


    public function __construct(PromptService $promptService, OpenAIService $openAIService)
    {
        $this->promptService = $promptService;
        $this->openAIService = $openAIService;
    }


    public function index(): JsonResponse
    {
        $prompts = $this->promptService->list(false);
        return response()->json($prompts);
    }

    
    public function show(int $id): JsonResponse
    {
        $prompt = $this->promptService->get($id);
        if (! $prompt) {
            return response()->json(['message' => 'Prompt not found'], 404);
        }
        return response()->json($prompt);
    }


    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $prompt = $this->promptService->create($data);
        return response()->json($prompt, 201);
    }


    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'template' => 'sometimes|string',
            'is_active' => 'boolean',
        ]);

        $prompt = $this->promptService->update($id, $data);
        if (! $prompt) {
            return response()->json(['message' => 'Prompt not found'], 404);
        }

        return response()->json($prompt);
    }


    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->promptService->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Prompt not found'], 404);
        }

        return response()->json(['message' => 'Prompt deleted']);
    }


    public function deactivate(int $id): JsonResponse
    {
        $prompt = $this->promptService->deactivate($id);
        if (! $prompt) {
            return response()->json(['message' => 'Prompt not found'], 404);
        }

        return response()->json(['message' => 'Prompt deactivated', 'prompt' => $prompt]);
    }


    public function runPrompt(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'placeholders' => 'required|array',
        ]);

        $result = $this->openAIService->runPrompt($id, $data['placeholders']);


        if (! $result) {
            return response()->json(['message' => 'Prompt not found or failed'], 404);
        }

        return response()->json($result);
    }
}
