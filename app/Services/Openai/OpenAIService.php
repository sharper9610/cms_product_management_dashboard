<?php

namespace App\Services\Openai;

use App\Models\DlcProductPrompt;
use App\Models\Notice;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    protected PromptService $promptService;
    protected int $tokensUsedThisMinute = 0;
    protected int $requestsThisMinute = 0;
    protected float $minuteWindowStart = 0;

    public function __construct(PromptService $promptService)
    {
        $this->promptService = $promptService;
    }


    public function runPrompt(
        int $promptId,
        array $placeholders = [],
        string $language = 'en',
        int $maxRetries = 5,
        string $type = 'game'
    ): ?array {
        if ($language === 'en') {
            $promptText = $this->promptService->buildPrompt($promptId, $placeholders, $type);
        } elseif ($language === 'pt-br') {
            $promptText = $this->promptService->buildPromptPt($promptId, $placeholders, $type);
        } elseif ($language === 'es-419') {
            $promptText = $this->promptService->buildPromptEs($promptId, $placeholders, $type);
        } else {
            $promptText = '';
        }

        if (!$promptText) return null;

        $attempt = 0;
        $backoff = 1;
        $maxRPM = 2700;
        $maxTPM = 450_000;

        while ($attempt <= $maxRetries) {
            $now = time();
            $windowKey = "gpt4o_usage_" . floor($now / 60);
            $usage = Cache::get($windowKey, ['requests' => 0, 'tokens' => 0]);
            $estimatedTokens = max(50, ceil(strlen($promptText) / 4));

            if ($usage['requests'] + 1 > $maxRPM || $usage['tokens'] + $estimatedTokens > $maxTPM) {
                $sleepTime = 60 - ($now % 60);
                usleep($sleepTime * 1_000_000);
                continue;
            }

            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => $promptText],
                    ],
                ]);

                $responseContent = $response->choices[0]->message->content ?? null;

                if ($responseContent) {
                    $usageTokens = $response->usage->totalTokens ?? $estimatedTokens;
                    $this->updateUsage($windowKey, $usageTokens);
                    $cost = $this->calculateCost($response->model, $response->usage);

                    $responseData = [
                        'response_content' => $responseContent,
                        'model' => $response->model,
                        'usage' => [
                            'prompt_tokens' => $response->usage->promptTokens ?? 0,
                            'completion_tokens' => $response->usage->completionTokens ?? 0,
                            'total_tokens' => $response->usage->totalTokens ?? 0,
                        ],
                        'cost' => $cost,
                        // 'promptText' => $promptText
                    ];
                    Log::info("OpenAI response details", $responseData);
                    $this->deactivateRateLimitNotice();
                    return $responseData;
                }
            } catch (\OpenAI\Exceptions\RateLimitException $e) {
                $this->activateRateLimitNotice($e);
                print_r($e);
                $wait = $backoff + rand(0, 1000) / 1000;
                sleep($wait);
                $backoff = min($backoff * 2, 60);
            } catch (\Exception $e) {
                print_r($e);
                sleep($backoff);
                $backoff = min($backoff * 2, 60);
            }

            $attempt++;
        }

        return [
            'response_content' => null,
            'model' => 'gpt-4o',
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
            'cost' => 0,
            'promptText' => $promptText
        ];
    }

    public function runGenrePrompt(
        int $promptId,
        array $placeholders = [],
        string $language = 'en',
        int $maxRetries = 5,
        string $type = 'game'
    ): ?array {
        if ($language === 'en') {
            $promptText = $this->promptService->buildPrompt($promptId, $placeholders, $type);
        } elseif ($language === 'pt-br') {
            $promptText = $this->promptService->buildPromptPt($promptId, $placeholders, $type);
        } elseif ($language === 'es-419') {
            $promptText = $this->promptService->buildPromptEs($promptId, $placeholders, $type);
        } else {
            $promptText = '';
        }

        if (!$promptText) return null;


        $attempt = 0;
        $backoff = 1;
        $maxRPM = 2700;
        $maxTPM = 450_000;

        while ($attempt <= $maxRetries) {
            $now = time();
            $windowKey = "gpt4o_usage_" . floor($now / 60);
            $usage = Cache::get($windowKey, ['requests' => 0, 'tokens' => 0]);
            $estimatedTokens = max(50, ceil(strlen($promptText) / 4));

            if ($usage['requests'] + 1 > $maxRPM || $usage['tokens'] + $estimatedTokens > $maxTPM) {
                $sleepTime = 60 - ($now % 60);
                usleep($sleepTime * 1_000_000);
                continue;
            }

            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                        ['role' => 'user', 'content' => $promptText],
                    ],
                ]);

                $responseContent = $response->choices[0]->message->content ?? null;

                if ($responseContent) {
                    $usageTokens = $response->usage->totalTokens ?? $estimatedTokens;
                    $this->updateUsage($windowKey, $usageTokens);
                    $cost = $this->calculateCost($response->model, $response->usage);

                    $responseData = [
                        'response_content' => $responseContent,
                        'model' => $response->model,
                        'usage' => [
                            'prompt_tokens' => $response->usage->promptTokens ?? 0,
                            'completion_tokens' => $response->usage->completionTokens ?? 0,
                            'total_tokens' => $response->usage->totalTokens ?? 0,
                        ],
                        'cost' => $cost,
                        // 'promptText' => $promptText
                    ];
                    Log::info("OpenAI response details", $responseData);
                    $this->deactivateRateLimitNotice();
                    return $responseData;
                }
            } catch (\OpenAI\Exceptions\RateLimitException $e) {
                $this->activateRateLimitNotice($e);
                print_r($e);
                $wait = $backoff + rand(0, 1000) / 1000;
                sleep($wait);
                $backoff = min($backoff * 2, 60);
            } catch (\Exception $e) {
                print_r($e);
                sleep($backoff);
                $backoff = min($backoff * 2, 60);
            }

            $attempt++;
        }

        return [
            'response_content' => null,
            'model' => 'gpt-4o',
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
            'cost' => 0,
            'promptText' => $promptText
        ];
    }


    public function processDlcProductIdsGeneration($sku)
    {
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            Log::error("Product with SKU {$sku} not found.");
            return;
        }

        $prompt = DlcProductPrompt::where('is_active', 1)->first();


        if (!$prompt) {
            Log::error("No active DLC Product Prompt found.");
            return;
        }

        $converted_prompt_text = str_replace('<Insert Game Name>', $product->name, $prompt->template);


        //use this converted_prompt_text and get response
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $converted_prompt_text],
            ],
        ]);

        $responseContent = $response->choices[0]->message->content ?? null;
        Log::info("DLC Product ID Generation Response", [
            'sku' => $sku,
            'prompt' => $converted_prompt_text,
            'response' => $responseContent,
            'model' => $response->model,
            'usage' => [
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
            ],
        ]);

        // dd($responseContent);

        return $responseContent;
    }

    protected function updateUsage(string $windowKey, int $tokens)
    {
        Cache::lock("lock_{$windowKey}", 5)->get(function () use ($windowKey, $tokens) {
            $usage = Cache::get($windowKey, ['requests' => 0, 'tokens' => 0]);
            $usage['requests']++;
            $usage['tokens'] += $tokens;
            Cache::put($windowKey, $usage, 60);
        });
    }



    protected function calculateCost(string $model, $usage): float
    {
        if (! $usage) {
            return 0.0;
        }

        $prompt = $usage->promptTokens ?? 0;
        $completion = $usage->completionTokens ?? 0;

        $pricing = [
            'gpt-4o-mini' => [
                'prompt'     => 0.00015 / 1000,
                'completion' => 0.00060 / 1000,
            ],
            'gpt-4o' => [
                'prompt'     => 0.00050 / 1000,
                'completion' => 0.00150 / 1000,
            ],
        ];

        $rate = $pricing[$model] ?? $pricing['gpt-4o-mini'];

        return round(
            ($prompt * $rate['prompt']) + ($completion * $rate['completion']),
            6
        );
    }

    public function activateRateLimitNotice(\Throwable $e): void
    {
        // 1️⃣ If an active notice already exists → stop (no spam)
        $activeNotice = Notice::where('type', 'openai_rate_limit')
            ->where('status', 'active')
            ->first();

        if ($activeNotice) {
            return;
        }

        // 2️⃣ Check if ANY notice exists (active or inactive)
        $anyNoticeExists = Notice::where('type', 'openai_rate_limit')->exists();

        // 3️⃣ Create a NEW notice if none exists OR last one was inactive
        $notice = Notice::create([
            'title'      => 'OpenAI API Rate Limit Reached',
            'type'       => 'openai_rate_limit',
            'details'    => $e->getMessage(),
            'status'     => 'active',
            'start_date' => now(),
        ]);

        // 4️⃣ Email managers (only once per incident)
        // Mail::raw(
        //     "OpenAI API rate limit has been reached.\n\nError:\n{$e->getMessage()}",
        //     function ($message) {
        //         $message->to(config('alerts.manager_email'))
        //             ->subject('🚨 OpenAI Rate Limit Alert');
        //     }
        // );

        Log::critical('OpenAI rate limit notice created and managers notified', [
            'notice_id' => $notice->id,
            'previous_notice_exists' => $anyNoticeExists,
        ]);
    }


    public function deactivateRateLimitNotice(): void
    {
        Notice::where('type', 'openai_rate_limit')
            ->where('status', "active")
            ->update([
                'status'   => 'inactive',
                'end_date' => now(),
            ]);
    }
}
