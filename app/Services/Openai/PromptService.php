<?php

namespace App\Services\Openai;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\Collection;

class PromptService
{
    public function list(bool $onlyActive = true): Collection
    {
        return Prompt::when($onlyActive, fn($q) => $q->where('is_active', true))
            ->orderBy('id', 'desc')
            ->get();
    }

    public function get(int $id): ?Prompt
    {
        return Prompt::find($id);
    }


    public function create(array $data): Prompt
    {
        return Prompt::create($data);
    }


    public function update(int $id, array $data): ?Prompt
    {
        $prompt = Prompt::find($id);
        if ($prompt) {
            $prompt->update($data);
        }
        return $prompt;
    }


    public function delete(int $id): bool
    {
        $prompt = Prompt::find($id);
        return $prompt ? $prompt->delete() : false;
    }


    public function deactivate(int $id): ?Prompt
    {
        $prompt = Prompt::find($id);
        if ($prompt) {
            $prompt->is_active = false;
            $prompt->save();
        }
        return $prompt;
    }

     /**
     * Build prompt text by replacing placeholders
     *
     * @param int $id
     * @param array $placeholders ['Name' => 'Civilization VI', 'DRM' => 'Steam']
     * @return string|null
     */
    public function buildPrompt(int $id, array $placeholders = [], string $type='game'): ?string
    {
        $prompt = $this->get($id);
        if (! $prompt) {
            return null;
        }

        if ($type === 'gift_card' && !empty($prompt->template_gift_card)) {
            $text = $prompt->template_gift_card;
        } else {
            $text = $prompt->template;
        }

        foreach ($placeholders as $key => $value) {
            $text = str_replace("[$key]", $value, $text);
        }

        return $text;
    }

    public function buildPromptPt(int $id, array $placeholders = [], string $type='game'): ?string
    {
        $prompt = $this->get($id);
        if (! $prompt) {
            return null;
        }

        if ($type === 'gift_card' && !empty($prompt->template_gift_card)) {
            $text = $prompt->template_gift_card_pt;
        } else {
            $text = $prompt->template_pt;
        }

        foreach ($placeholders as $key => $value) {
            $text = str_replace("[$key]", $value, $text);
        }

        return $text;
    }

    public function buildPromptEs(int $id, array $placeholders = [], string $type='game'): ?string
    {
        $prompt = $this->get($id);
        if (! $prompt) {
            return null;
        }

        if ($type === 'gift_card' && !empty($prompt->template_gift_card)) {
            $text = $prompt->template_gift_card_es;
        } else {
            $text = $prompt->template_es;
        }

        foreach ($placeholders as $key => $value) {
            $text = str_replace("[$key]", $value, $text);
        }

        return $text;
    }
}
