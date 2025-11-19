<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RealtimeInterviewController extends Controller
{
    /**
     * POST /api/interviews/{id}/rt/start
     * Called when a question starts. Returns Node.js WebSocket URL.
     */
    public function start(int $id)
    {
        $interview = DB::table('interviews')->where('id', $id)->first();
        
        if (!$interview) {
            return response()->json(['error' => 'Interview not found'], 404);
        }

        $sid = (string) Str::uuid();

        return response()->json([
            'sessionId' => $sid,
            'node_ws_url' => env('NODE_REALTIME_URL', 'ws://127.0.0.1:8081'),
            'interview_id' => $id
        ]);
    }

    /**
     * POST /api/interviews/{id}/rt/submit-answer
     * Called when the user finishes speaking and submits their answer.
     * Analyzes the answer and provides detailed feedback.
     */
    public function submitAnswer(Request $request, $id)
    {
        $request->validate([
            'sessionId' => 'required|string',
            'transcript' => 'required|string',
            'question_index' => 'required|integer',
        ]);

        $interviewId = $id;
        $questionIndex = $request->question_index;
        $answerText = trim($request->transcript);

        // Retrieve the interview
        $interview = DB::table('interviews')->where('id', $interviewId)->first();
        if (!$interview) {
            return response()->json(['error' => 'Interview not found'], 404);
        }

        // Retrieve question text
        $questionSet = json_decode($interview->question_set ?? '[]', true);
        $questionText = $questionSet[$questionIndex] ?? 'Unknown Question';

        /*
        |--------------------------------------------------------------------------
        | AI Feedback Generation (GPT-5 FIXED)
        |--------------------------------------------------------------------------
        */

        $modelUsed = env('ANALYSIS_MODEL', 'gpt-5-mini');
        $analysisSource = 'ai';
        $feedback = null;
        $verification_hash = null;

        try {
            $prompt = <<<PROMPT
You are a senior technical interview evaluator.
Evaluate the candidate's response using the following strict scoring criteria (0–100):

1. Clarity:
   - Is the explanation clear and understandable?
   - Is terminology used correctly?
   - Does it avoid confusion and rambling?

2. Confidence:
   - Is the tone decisive and assured?
   - Does the reasoning feel confident?
   - Does the candidate avoid hesitation language?

3. Structure:
   - Is the answer organized logically?
   - Is there a beginning → middle → conclusion?
   - Is reasoning sequential and coherent?

4. Relevance:
   - Does the answer directly address the question?
   - Does it avoid unrelated details?
   - Does it use contextually correct domain concepts?

Return ONLY valid JSON in this exact shape:

{
  "clarity": number,
  "confidence": number,
  "structure": number,
  "relevance": number,
  "average": number,
  "summary": "one-paragraph high-level performance summary",
  "tips": ["...", "..."],
  "next_recommendations": {
    "suggested_level": "junior/mid/senior",
    "recommended_next_difficulty": "easy/medium/hard",
    "skills_to_focus": ["...", "..."],
    "suggested_interview_type": "technical/behavioral/system design/problem-solving",
    "practice_questions": ["...", "...", "..."]
  }
}

NO markdown.
NO commentary.
NO extra text.

---------------------
QUESTION:
{$questionText}

ANSWER:
{$answerText}
PROMPT;

            $apiKey = env('OPENAI_API_KEY');

            // ✅ Build proper payload using helper
            // For GPT-5, we need higher token limits for reasoning
            $maxTokens = ($modelUsed === 'gpt-5') ? 5000 : 500;
            
            $payload = $this->buildChatPayload(
                $modelUsed,
                [
                    ['role' => 'system', 'content' => 'Return ONLY pure JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                $maxTokens,
                0.1
            );

            Log::info('Submitting answer to AI', [
                'model' => $modelUsed,
                'payload' => $payload, // Log the FULL payload
                'prompt_length' => strlen($prompt),
                'answer_length' => strlen($answerText)
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(120) // GPT-5 needs even longer for reasoning
              ->post('https://api.openai.com/v1/chat/completions', $payload);

            // Log the FULL response
            Log::info('API raw response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'headers' => $response->headers(),
                'full_body' => $response->body(),
                'json' => $response->json()
            ]);

            if (!$response->successful()) {
                Log::error('API error in submitAnswer', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $modelUsed
                ]);
                throw new \Exception('API error: ' . $response->body());
            }

            // Try multiple ways to get the content
            $responseData = $response->json();
            $raw = '';
            
            if (isset($responseData['choices'][0]['message']['content'])) {
                $raw = $responseData['choices'][0]['message']['content'];
            }
            
            $raw = trim($raw);
            
            Log::info('Extracted content', [
                'has_choices' => isset($responseData['choices']),
                'choices_count' => isset($responseData['choices']) ? count($responseData['choices']) : 0,
                'raw_length' => strlen($raw),
                'raw_preview' => substr($raw, 0, 300)
            ]);

            if (empty($raw)) {
                throw new \Exception('Empty response from API');
            }

            // Clean any potential markdown
            $raw = preg_replace('/```json\s*|\s*```/', '', $raw);
            $raw = trim($raw);

            Log::info('Parsing AI response', [
                'raw_length' => strlen($raw),
                'raw_preview' => substr($raw, 0, 200)
            ]);

            $feedback = json_decode($raw, true);

            if (!$feedback || !isset($feedback['clarity']) || !isset($feedback['tips'])) {
                Log::error('Invalid feedback structure', [
                    'raw' => $raw,
                    'decoded' => $feedback,
                    'json_error' => json_last_error_msg()
                ]);
                throw new \Exception("Invalid JSON returned: " . $raw);
            }

            // Ensure all required fields exist
            $feedback = array_merge([
                'clarity' => 50,
                'confidence' => 50,
                'structure' => 50,
                'relevance' => 50,
                'average' => 50,
                'summary' => 'Analysis completed.',
                'tips' => [],
                'next_recommendations' => [
                    'suggested_level' => 'mid',
                    'recommended_next_difficulty' => 'medium',
                    'skills_to_focus' => [],
                    'suggested_interview_type' => 'technical',
                    'practice_questions' => []
                ]
            ], $feedback);

            // Calculate average if not provided
            if (!isset($feedback['average']) || $feedback['average'] == 0) {
                $feedback['average'] = round((
                    $feedback['clarity'] + 
                    $feedback['confidence'] + 
                    $feedback['structure'] + 
                    $feedback['relevance']
                ) / 4);
            }

            // Verification Hash
            $verification_hash = hash('sha256', json_encode($feedback) . $modelUsed);

            Log::info('Feedback successfully generated', [
                'clarity' => $feedback['clarity'],
                'confidence' => $feedback['confidence'],
                'structure' => $feedback['structure'],
                'relevance' => $feedback['relevance'],
                'average' => $feedback['average']
            ]);

        } catch (\Throwable $e) {
            Log::error('Feedback analysis error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $modelUsed
            ]);

            $analysisSource = 'fallback';

            $feedback = [
                "clarity" => 50,
                "confidence" => 50,
                "structure" => 50,
                "relevance" => 50,
                "average" => 50,
                "summary" => "Fallback analysis used due to an AI evaluation error: " . $e->getMessage(),
                "tips" => [
                    "Try to expand your explanation with more relevant detail.",
                    "Organize your answer into clear steps or sections."
                ],
                "next_recommendations" => [
                    "suggested_level" => "mid",
                    "recommended_next_difficulty" => "medium",
                    "skills_to_focus" => ["communication", "clarity"],
                    "suggested_interview_type" => "technical",
                    "practice_questions" => [
                        "Practice explaining technical concepts clearly",
                        "Work on structuring your answers"
                    ]
                ]
            ];

            $verification_hash = hash('sha256', 'fallback-' . json_encode($feedback));
        }

        /*
        |--------------------------------------------------------------------------
        | Store Answer
        |--------------------------------------------------------------------------
        */

        DB::table('interview_answers')->insert([
            'interview_id'   => $interviewId,
            'question_index' => $questionIndex,
            'question_text'  => $questionText,
            'answer_text'    => $answerText,
            'feedback'       => json_encode($feedback),
            'model_used'     => $modelUsed,
            'analysis_source'=> $analysisSource,
            'verification_token' => $verification_hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Response to frontend
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'success',
            'model_used' => $modelUsed,
            'analysis_source' => $analysisSource,
            'verification_token' => $verification_hash,
            'feedback' => $feedback,
        ]);
    }

    /**
     * Helper: build payload compatible with GPT-5 and non-GPT-5 models.
     * 
     * - For GPT-5 reasoning models (gpt-5, NOT gpt-5-mini):
     *   - uses max_completion_tokens
     *   - DOES NOT send temperature
     * 
     * - For all other models (gpt-5-mini, gpt-5-nano, gpt-4.1, gpt-4o, etc.):
     *   - uses max_tokens
     *   - sends temperature if provided
     */
    private function buildChatPayload(string $model, array $messages, int $maxTokens, ?float $temperature = null): array
    {
        $payload = [
            'model'    => $model,
            'messages' => $messages,
        ];

        // Only the MAIN GPT-5 model (not mini or nano) uses reasoning-style parameters
        if (preg_match('/^gpt-5$/', $model) || preg_match('/^gpt-5-\d{4}-\d{2}-\d{2}$/', $model)) {
            // GPT-5 main reasoning model
            $payload['max_completion_tokens'] = $maxTokens;
            // NO temperature for reasoning models
        } else {
            // All other models: gpt-5-mini, gpt-5-nano, gpt-4.1, gpt-4o, etc.
            $payload['max_tokens'] = $maxTokens;
            if (!is_null($temperature)) {
                $payload['temperature'] = $temperature;
            }
        }

        return $payload;
    }

    /**
     * LEGACY: Analyze interview answer using AI (NOT USED in submitAnswer)
     * This is kept for backward compatibility but should use buildChatPayload
     */
    private function analyzeAnswer(string $question, string $answer): array
    {
        $model = env('ANALYSIS_MODEL', 'gpt-5-mini');

        // Handle empty or very short answers
        if (strlen($answer) < 10) {
            return [
                'clarity' => 20,
                'confidence' => 20,
                'structure' => 20,
                'relevance' => 20,
                'average' => 20,
                'summary' => 'Answer too short. Please provide more detail.',
                'tips' => ['Provide a more detailed response', 'Aim for at least 50 words']
            ];
        }

        $prompt = <<<PROMPT
You are an expert interview coach analyzing a candidate's answer. Evaluate the following interview response:

**Question:** "$question"

**Candidate's Answer:** "$answer"

Provide a detailed analysis with scores (0-100) for:
1. **Clarity**: How clear and easy to understand is the answer?
2. **Confidence**: Does the candidate sound confident and knowledgeable?
3. **Structure**: Is the answer well-organized with a clear beginning, middle, and end?
4. **Relevance**: How well does the answer address the question?

Also provide:
- A brief summary (1-2 sentences) of the overall performance
- 2-3 actionable tips for improvement

OUTPUT ONLY VALID JSON in this exact format:
{
  "clarity": 0-100,
  "confidence": 0-100,
  "structure": 0-100,
  "relevance": 0-100,
  "average": 0-100,
  "summary": "brief summary here",
  "tips": ["tip 1", "tip 2", "tip 3"]
}

DO NOT include any markdown formatting or code blocks. ONLY OUTPUT THE JSON OBJECT.
PROMPT;

        try {
            $apiKey = env('OPENAI_API_KEY');

            Log::info('Analyzing answer with AI (LEGACY method)', [
                'model' => $model,
                'question_length' => strlen($question),
                'answer_length' => strlen($answer)
            ]);

            // ✅ Use the helper method
            $payload = $this->buildChatPayload(
                $model,
                [
                    [
                        'role' => 'system',
                        'content' => 'You are an interview coach. Return ONLY valid JSON without markdown formatting.'
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                800,
                0.3
            );

            $res = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$res->successful()) {
                Log::error('Feedback API error', [
                    'status' => $res->status(),
                    'body' => $res->body()
                ]);
                return $this->getFallbackFeedback($answer);
            }

            $content = $res->json('choices.0.message.content') ?? '{}';
            
            Log::info('AI feedback received', [
                'content_length' => strlen($content),
                'preview' => substr($content, 0, 100)
            ]);
            
            // Clean response
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);
            
            $feedback = json_decode($content, true);

            if (!is_array($feedback) || !isset($feedback['clarity'])) {
                Log::warning('Invalid feedback format from AI', ['content' => $content]);
                return $this->getFallbackFeedback($answer);
            }

            Log::info('AI feedback successfully parsed', [
                'clarity' => $feedback['clarity'] ?? 0,
                'confidence' => $feedback['confidence'] ?? 0,
                'structure' => $feedback['structure'] ?? 0,
                'relevance' => $feedback['relevance'] ?? 0
            ]);

            // Ensure all required fields exist
            return [
                'clarity' => (int) ($feedback['clarity'] ?? 70),
                'confidence' => (int) ($feedback['confidence'] ?? 70),
                'structure' => (int) ($feedback['structure'] ?? 70),
                'relevance' => (int) ($feedback['relevance'] ?? 70),
                'average' => (int) ($feedback['average'] ?? 70),
                'summary' => $feedback['summary'] ?? 'Good effort on this answer.',
                'tips' => $feedback['tips'] ?? ['Keep practicing']
            ];

        } catch (\Throwable $e) {
            Log::error('Feedback analysis error: ' . $e->getMessage());
            return $this->getFallbackFeedback($answer);
        }
    }

    /**
     * Generate fallback feedback based on basic metrics
     * This is only used when AI analysis fails
     */
    private function getFallbackFeedback(string $answer): array
    {
        Log::warning('Using FALLBACK feedback (AI analysis failed)');
        
        $wordCount = str_word_count($answer);
        $sentences = preg_split('/[.!?]+/', $answer, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        // Basic scoring based on length and structure
        $clarityScore = min(100, max(40, $wordCount * 2));
        $confidenceScore = 65;
        $structureScore = min(100, max(50, $sentenceCount * 15));
        $relevanceScore = 70;
        $average = round(($clarityScore + $confidenceScore + $structureScore + $relevanceScore) / 4);

        $tips = [];
        if ($wordCount < 30) {
            $tips[] = "Try to provide more detailed responses (aim for 50+ words)";
        }
        if ($sentenceCount < 3) {
            $tips[] = "Structure your answer into multiple clear points";
        }
        $tips[] = "Use specific examples to support your points";

        return [
            'clarity' => (int) $clarityScore,
            'confidence' => (int) $confidenceScore,
            'structure' => (int) $structureScore,
            'relevance' => (int) $relevanceScore,
            'average' => $average,
            'summary' => "⚠️ Basic analysis (AI unavailable). Word count: {$wordCount}. Consider adding more detail and structure.",
            'tips' => $tips
        ];
    }
}