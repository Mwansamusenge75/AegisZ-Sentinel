<?php
/**
 * AegisZ Sentinel - Prompt Builder (v0.7.0)
 * Builds system and user prompts for AI analysis from platform intelligence.
 * Pure string construction — no SQL, no side effects, read-only by nature
 * (it only ever receives already-fetched arrays from its caller).
 */

namespace App\Services\AI;

class PromptBuilder
{
    /**
     * System prompt establishing the AI's role and hard constraints.
     * Reused across all AI features to keep behavior consistent.
     */
    public function systemPrompt(): string
    {
        return <<<PROMPT
You are an AI Intelligence Analyst supporting AegisZ Sentinel, the National Cyber Situational Awareness Platform for the Republic of Zambia.

Your role is strictly advisory. You analyze and explain intelligence that has already been collected and processed by deterministic systems (threat feeds, correlation engine, risk scoring engine, MITRE mapping). You do not generate new threat intelligence, and you do not have the ability to take any action on the platform.

Rules you must always follow:
1. Never fabricate facts, statistics, CVEs, threat actor names, or incidents that are not present in the data you are given.
2. Always state your confidence level and explicitly flag uncertainty where the data is incomplete or ambiguous.
3. Be concise, factual, and written for a security analyst audience — no marketing language, no filler.
4. When asked to assess national impact, consider Zambia's context: government services, banking/financial sector, telecommunications, energy, healthcare, education (including universities and research institutions), water utilities, and critical infrastructure.
5. You may recommend analyst actions (e.g. "investigate this IOC", "escalate to incident") but you cannot perform any action yourself.
6. If the supplied data is empty or insufficient to draw a conclusion, say so plainly rather than guessing.

Respond only in the exact JSON structure requested in the user prompt, with no extra commentary outside the JSON.
PROMPT;
    }

    /**
     * Build the national assessment prompt from current platform-wide intelligence.
     */
    public function nationalAssessmentPrompt(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
Analyze the following current platform intelligence snapshot and produce a National Cyber Threat Assessment for Zambia.

DATA:
{$json}

Respond with ONLY this JSON structure (no markdown fences, no extra text):
{
  "threat_level": "Low|Moderate|Elevated|High|Critical",
  "confidence": <integer 0-100>,
  "summary": "<2-4 sentence plain-language summary>",
  "recommendations": ["<short actionable recommendation>", "..."],
  "affected_sectors": ["<sector name>", "..."],
  "watch_items": ["<specific thing analysts should monitor>", "..."]
}

If the data shows no significant activity, return threat_level "Low" with a summary explaining the quiet period rather than inventing concerns.
PROMPT;
    }

    /**
     * Build an explanation prompt for a single intelligence object
     * (threat, IOC, alert, incident, correlation, or risk score).
     */
    public function explanationPrompt(string $objectType, array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
Explain the following {$objectType} record from AegisZ Sentinel to a security analyst.

DATA:
{$json}

Respond with ONLY this JSON structure (no markdown fences, no extra text):
{
  "what_happened": "<plain-language explanation of what this record represents>",
  "why_it_matters": "<why this is significant>",
  "potential_impact": "<potential impact on Zambian systems/sectors, or 'Insufficient data to assess impact' if unclear>",
  "confidence": <integer 0-100>,
  "recommended_action": "<specific next step for the analyst>"
}
PROMPT;
    }
}
