<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InterviewController extends Controller
{
    /**
     * POST /api/interviews/start
     * Creates an interview with AI-generated questions based on user skills.
     */
    public function start(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'skill_ids' => 'nullable|array',
        ]);

        $userId = $request->input('user_id');
        $skillIds = $request->input('skill_ids', []);

        // Fetch user skills
        if (!empty($skillIds)) {
            $skills = DB::table('user_skills')
                ->whereIn('id', $skillIds)
                ->where('user_id', $userId)
                ->get(['title', 'years_of_experience', 'proficiency_level'])
                ->toArray();
        } else {
            $skills = DB::table('user_skills')
                ->where('user_id', $userId)
                ->get(['title', 'years_of_experience', 'proficiency_level'])
                ->toArray();
        }

        if (empty($skills)) {
            return response()->json(['error' => 'No skills found for this user'], 400);
        }

        // Generate the FIRST question only
        $firstQuestion = $this->generateSingleQuestion($skills);

        if (!$firstQuestion) {
            return response()->json(['error' => 'Failed to generate first question'], 500);
        }

        // Create interview with ONE initial question
        $id = DB::table('interviews')->insertGetId([
            'user_id'          => $userId,
            'question_set'     => json_encode([$firstQuestion]),
            'current_question' => 0,
            'status'           => 'active',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json([
            'id'            => $id,
            'status'        => 'active',
            'first_question'=> $firstQuestion,
            'skills_used'   => $skills,
        ]);
    }

    /**
     * Generate ONE adaptive first question (GPT-5-compatible)
     */
    private function generateSingleQuestion(array $skills): ?string
    {
        $skillContext = collect($skills)->map(function ($skill) {
            return "- {$skill->title} ({$skill->proficiency_level}, {$skill->years_of_experience} yrs)";
        })->implode("\n");

        $prompt = <<<PROMPT
You are an expert technical interviewer with 20+ years of experience.

Generate ONE highly relevant interview question for a candidate with the following skill profile:

{$skillContext}

Rules:
- Use both proficiency level AND years of experience to adjust difficulty.
- If skill is beginner → ask easier foundational questions.
- If intermediate → ask practical hands-on & medium complexity questions.
- If advanced/expert → ask deep scenario, architecture, optimization, or debugging questions.
- The question must be answerable in 1–3 minutes.
- No numbering, no quotes, no JSON.
- Output ONLY the question text.
PROMPT;

        try {
            $apiKey = env('OPENAI_API_KEY');
            if (!$apiKey) {
                Log::error('OPENAI_API_KEY missing when generating first question');
                return $this->simpleFirstQuestionFallback($skills);
            }

            $model = env('QUESTION_MODEL', 'gpt-4.1');

            // ✅ FIXED: Increased tokens for GPT-5 reasoning models
            $payload = $this->buildChatPayload(
                $model,
                [
                    ['role' => 'system', 'content' => 'You create concise, accurate technical interview questions.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                1024,  // ✅ Increased from 256 to support GPT-5 reasoning
                0.6
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                Log::error('First-question generation error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->simpleFirstQuestionFallback($skills);
            }

            $data = $response->json();
            $content = trim($data['choices'][0]['message']['content'] ?? '');

            if ($content === '') {
                Log::warning('Empty content for first question, using fallback.');
                return $this->simpleFirstQuestionFallback($skills);
            }

            return $content;

        } catch (\Throwable $e) {
            Log::error('Error generating first question', [
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return $this->simpleFirstQuestionFallback($skills);
        }
    }

    /**
     * Very robust fallback if generation fails
     */
    private function simpleFirstQuestionFallback(array $skills): string
    {
        $titles = collect($skills)->pluck('title')->take(3)->implode(', ');
        return "To start, can you briefly describe your experience with {$titles}?";
    }

    /**
     * GET /api/interviews/{id}/next-question
     */
    public function nextQuestion(int $id)
    {
        $interview = DB::table('interviews')->find($id);

        if (!$interview) {
            return response()->json(['error' => 'Interview not found'], 404);
        }

        $questionSet = json_decode($interview->question_set ?? '[]', true);
        if (!is_array($questionSet)) $questionSet = [];

        // Fetch skills
        $skills = DB::table('user_skills')
            ->where('user_id', $interview->user_id)
            ->get(['title', 'years_of_experience', 'proficiency_level'])
            ->toArray();

        // Fetch previous answers + feedback
        $answers = DB::table('interview_answers')
            ->where('interview_id', $id)
            ->orderBy('question_index')
            ->get();

        $nextQuestion = $this->generateNextQuestion($skills, $questionSet, $answers);

        if (!$nextQuestion) {
            return response()->json([
                'error' => 'Failed to generate next question',
            ], 500);
        }

        $questionSet[] = $nextQuestion;

        DB::table('interviews')->where('id', $id)->update([
            'question_set'     => json_encode($questionSet),
            'current_question' => count($questionSet) - 1,
            'updated_at'       => now(),
        ]);

        return response()->json([
            'done'      => false,
            'index'     => count($questionSet) - 1,
            'question'  => $nextQuestion,
            'total'     => count($questionSet),
            'remaining' => null,
        ]);
    }

    private function generateNextQuestion(array $skills, array $previousQuestions, $answers): ?string
    {
        $skillContext = collect($skills)->map(function ($skill) {
            return "- {$skill->title} ({$skill->proficiency_level}, {$skill->years_of_experience} yrs)";
        })->implode("\n");

        $history = "";
        foreach ($answers as $ans) {
            $fb = json_decode($ans->feedback ?? '{}', true) ?: [];

            $history .= "Q: {$ans->question_text}\n";
            $history .= "A: {$ans->answer_text}\n";

            if ($fb) {
                $history .= "Scores → clarity: {$fb['clarity']}, confidence: {$fb['confidence']}, structure: {$fb['structure']}, relevance: {$fb['relevance']}\n";
                $history .= "Summary: {$fb['summary']}\n";
            }
            $history .= "\n";
        }

        $difficulty = $this->calculateDifficultyFromFeedback($answers);

        $weakness = "general";
        if (count($answers) > 0) {
            $last = json_decode($answers[count($answers) - 1]->feedback ?? '{}', true) ?: [];
            $scores = [
                "clarity"    => $last['clarity'] ?? 70,
                "confidence" => $last['confidence'] ?? 70,
                "structure"  => $last['structure'] ?? 70,
                "relevance"  => $last['relevance'] ?? 70,
            ];
            $weakness = array_keys($scores, min($scores))[0] ?? "general";
        }

        $prompt = <<<PROMPT
You are a senior technical interviewer who generates adaptive, personalized interview questions.

Candidate Skills:
{$skillContext}

Previous Interview History:
{$history}

Adaptive Intelligence:
- Current difficulty level: {$difficulty}
- Weakest communication area: {$weakness}

Generate the NEXT question following these strict rules:

1. Difficulty:
   - easy: foundational & confidence-building
   - medium: practical, hands-on scenarios
   - hard: deep reasoning, debugging, architecture

2. Adaptation Based on Weakness:
   - clarity low → ask structured explanation questions
   - confidence low → ask justification or decision-making questions
   - structure low → ask multi-step reasoning or workflow questions
   - relevance low → ask precise domain-focused questions

3. Requirements:
   - Must relate strongly to the candidate's skills
   - Must NOT repeat or closely resemble previous questions
   - One single question only
   - No numbering, no markdown, no extra text
   - Output ONLY the question text
PROMPT;

        try {
            $apiKey = env('OPENAI_API_KEY');
            if (!$apiKey) {
                Log::error('OPENAI_API_KEY missing for generateNextQuestion');
                return $this->simpleNextQuestionFallback($skills);
            }

            $model = env('NEXT_QUESTION_MODEL', 'gpt-5-mini');

            // ✅ FIXED: Increased tokens for GPT-5 reasoning
            $payload = $this->buildChatPayload(
                $model,
                [
                    ['role' => 'system', 'content' => 'You generate intelligent adaptive interview questions.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                1024,  // ✅ Increased from 256
                0.65
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                Log::error('Next-question error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->simpleNextQuestionFallback($skills);
            }

            $data = $response->json();
            $content = trim($data['choices'][0]['message']['content'] ?? '');

            if ($content === '') {
                Log::warning('Empty content for next question, using fallback.');
                return $this->simpleNextQuestionFallback($skills);
            }

            return $content;

        } catch (\Throwable $e) {
            Log::error('Adaptive question generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->simpleNextQuestionFallback($skills);
        }
    }

    private function simpleNextQuestionFallback(array $skills): string
    {
        $firstSkill = collect($skills)->pluck('title')->first() ?? 'your main skill';
        return "Can you walk me through a recent project where you used {$firstSkill}, explaining the main challenge and how you solved it?";
    }

    /**
     * POST /api/interviews/{id}/finalize
     */
    public function finalize(int $id)
    {
        $interview = DB::table('interviews')->where('id', $id)->first();

        if (!$interview) {
            return response()->json(['error' => 'Interview not found'], 404);
        }

        $answers = DB::table('interview_answers')
            ->where('interview_id', $id)
            ->orderBy('question_index')
            ->get();

        if ($answers->count() === 0) {
            return response()->json(['error' => 'No answers found'], 400);
        }

        // Calculate weighted scores
        $totalClarity    = 0;
        $totalConfidence = 0;
        $totalStructure  = 0;
        $totalRelevance  = 0;

        foreach ($answers as $ans) {
            $fb = json_decode($ans->feedback ?? '{}', true) ?: [];

            $totalClarity    += $fb['clarity'] ?? 0;
            $totalConfidence += $fb['confidence'] ?? 0;
            $totalStructure  += $fb['structure'] ?? 0;
            $totalRelevance  += $fb['relevance'] ?? 0;
        }

        $count = max(1, $answers->count());

        $avgClarity    = round($totalClarity / $count);
        $avgConfidence = round($totalConfidence / $count);
        $avgStructure  = round($totalStructure / $count);
        $avgRelevance  = round($totalRelevance / $count);

        $overallScore = round(
            ($avgClarity * 0.30) +
            ($avgConfidence * 0.20) +
            ($avgStructure * 0.30) +
            ($avgRelevance * 0.20)
        );

        // Build history text
        $history = "";
        foreach ($answers as $ans) {
            $fb = json_decode($ans->feedback ?? '{}', true) ?: [];

            $history .= "Q: {$ans->question_text}\nA: {$ans->answer_text}\n";
            $history .= "Scores: clarity {$fb['clarity']}, confidence {$fb['confidence']}, structure {$fb['structure']}, relevance {$fb['relevance']}\n";
            $history .= "Summary: {$fb['summary']}\n\n";
        }

        // Generate final analysis
        $modelUsed      = env('ANALYSIS_MODEL', 'gpt-5-mini');
        $analysisSource = 'ai';
        $overallFeedback = null;

        try {
            $apiKey = env('OPENAI_API_KEY');
            if (!$apiKey) {
                throw new \Exception('OPENAI_API_KEY missing for finalize()');
            }

            $prompt = <<<PROMPT
You are a senior technical interview evaluator.

Based on the candidate's full interview performance, provide a detailed evaluation.

Performance Data:
{$history}

Average Scores:
- Clarity: {$avgClarity}
- Confidence: {$avgConfidence}
- Structure: {$avgStructure}
- Relevance: {$avgRelevance}
- Overall: {$overallScore}

You MUST respond ONLY with a JSON object following EXACTLY this schema:

{
  "clarity": number,
  "confidence": number,
  "structure": number,
  "relevance": number,
  "average": number,
  "summary": "One detailed professional paragraph summarizing the candidate's performance, strengths, weaknesses, speaking quality, and technical competence.",
  "tips": [
    "One specific improvement tip.",
    "Another specific and actionable improvement tip."
  ],
  "next_recommendations": {
    "suggested_level": "junior/mid/senior",
    "recommended_next_difficulty": "easy/medium/hard",
    "skills_to_focus": ["skill1", "skill2"],
    "suggested_interview_type": "technical/behavioral/system design/problem-solving",
    "practice_questions": [
      "One targeted practice question",
      "Second targeted practice question",
      "Third targeted practice question"
    ]
  }
}

DO NOT ADD ANY MARKDOWN.
DO NOT RETURN ANY TEXT OUTSIDE THE JSON.
PROMPT;

            $payload = $this->buildChatPayload(
                $modelUsed,
                [
                    ['role' => 'system', 'content' => 'Return ONLY clean JSON. No markdown.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                2000,  // High token limit for comprehensive analysis
                0.15
            );

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', $payload);

            if (!$response->successful()) {
                throw new \Exception('Finalize error: ' . $response->body());
            }

            $json = trim($response->json('choices.0.message.content', '{}'));
            
            // Clean any markdown
            $json = preg_replace('/```json\s*|\s*```/', '', $json);
            $json = trim($json);
            
            $overallFeedback = json_decode($json, true);

            if (!$overallFeedback) {
                throw new \Exception("Invalid JSON received");
            }

        } catch (\Throwable $e) {
            $analysisSource = 'fallback';

            Log::error('Finalize AI error, using fallback', [
                'error' => $e->getMessage(),
            ]);

            $overallFeedback = [
                "clarity"    => $avgClarity,
                "confidence" => $avgConfidence,
                "structure"  => $avgStructure,
                "relevance"  => $avgRelevance,
                "average"    => $overallScore,
                "summary"    => "A fallback summary was used due to AI error.",
                "tips"       => [
                    "Work on delivering more structured and concise answers.",
                    "Practice confidence and reduce filler words.",
                ],
                "next_recommendations" => [
                    "suggested_level"            => "junior",
                    "recommended_next_difficulty"=> "easy",
                    "skills_to_focus"           => ["communication", "clarity"],
                    "suggested_interview_type"  => "behavioral",
                    "practice_questions"        => [
                        "Describe a challenge you faced and how you solved it.",
                        "Explain a technical concept as if teaching a beginner.",
                        "Talk about a time you had to learn something quickly.",
                    ],
                ],
            ];
        }

        // Save final results
        DB::table('interviews')->where('id', $id)->update([
            'overall'    => json_encode([
                ...$overallFeedback,
                "next_recommendations" => $overallFeedback["next_recommendations"] ?? [],
            ]),
            'status'     => 'complete',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status'          => 'completed',
            'overall'         => $overallFeedback,
            'analysis_source' => $analysisSource,
            'model_used'      => $modelUsed,
        ]);
    }

    /**
     * GET /api/interviews/{id}
     */
    public function show(int $id)
    {
        try {
            $row = DB::table('interviews')->find($id);

            if (!$row) {
                return response()->json(['error' => 'Interview not found'], 404);
            }

            $answers = [];
            try {
                $answers = DB::table('interview_answers')
                    ->where('interview_id', $id)
                    ->orderBy('question_index')
                    ->get()
                    ->map(function ($answer) {
                        return [
                            'id'           => $answer->id ?? 0,
                            'question_text'=> $answer->question_text ?? '',
                            'answer_text'  => $answer->answer_text ?? '',
                            'feedback'     => $answer->feedback ?? '{}',
                            'created_at'   => $answer->created_at ?? now(),
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('Could not fetch interview answers', [
                    'interview_id' => $id,
                    'error'        => $e->getMessage(),
                ]);
            }

            $overallFeedback = null;
            if (!empty($row->overall)) {
                try {
                    $overallFeedback = json_decode($row->overall, true);
                } catch (\Exception $e) {
                    Log::warning('Could not parse overall column', [
                        'interview_id' => $id,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }

            $questionSet = [];
            if (!empty($row->question_set)) {
                try {
                    $questionSet = json_decode($row->question_set, true) ?: [];
                } catch (\Exception $e) {
                    Log::warning('Could not parse question_set', [
                        'interview_id' => $id,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'id'               => $row->id,
                'status'           => $row->status ?? 'created',
                'question_set'     => $questionSet,
                'current_question' => (int) ($row->current_question ?? 0),
                'overall'          => $overallFeedback,
                'answers'          => $answers,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in show() method', [
                'interview_id' => $id,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => 'Server error loading interview',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateDifficultyFromFeedback($answers)
    {
        if (count($answers) === 0) {
            return "medium";
        }

        $last = $answers[count($answers) - 1];
        $fb   = json_decode($last->feedback ?? '{}', true);

        if (!$fb) return "medium";

        $avg = (
            ($fb['clarity'] ?? 50) +
            ($fb['confidence'] ?? 50) +
            ($fb['structure'] ?? 50) +
            ($fb['relevance'] ?? 50)
        ) / 4;

        if ($avg >= 80) return "hard";
        if ($avg >= 60) return "medium";
        return "easy";
    }

    /**
     * Helper: build payload compatible with GPT-5 and non-GPT-5 models.
     *
     * - For GPT-5 main reasoning model (gpt-5):
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

        // Only the MAIN GPT-5 reasoning model uses special parameters
        if (preg_match('/^gpt-5$/', $model) || preg_match('/^gpt-5-\d{4}-\d{2}-\d{2}$/', $model)) {
            // GPT-5 main reasoning model
            $payload['max_completion_tokens'] = $maxTokens;
            // NO temperature for reasoning models
        } else {
            // All other models
            $payload['max_tokens'] = $maxTokens;
            if (!is_null($temperature)) {
                $payload['temperature'] = $temperature;
            }
        }

        return $payload;
    }
}