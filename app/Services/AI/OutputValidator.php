<?php
/**
 * AegisZ Sentinel - AI Output Validator (v0.7.0)
 * Validates and sanitises JSON returned by the AI model before it is
 * trusted anywhere in the application. Models can return malformed JSON,
 * markdown-fenced JSON, or hallucinated fields — this class is the single
 * gate that all AI output must pass through.
 *
 * No side effects. No SQL. No write access to anything.
 */

namespace App\Services\AI;

class OutputValidator
{
    /**
     * Parse and validate a National Assessment response.
     * Returns a clean array on success, or null if invalid/unparseable.
     */
    public function validateAssessment(?string $raw): ?array
    {
        $decoded = $this->extractJson($raw);
        if ($decoded === null) {
            return null;
        }

        $validLevels = ['Low', 'Moderate', 'Elevated', 'High', 'Critical'];

        if (!isset($decoded['threat_level']) || !in_array($decoded['threat_level'], $validLevels)) {
            return null;
        }
        if (!isset($decoded['summary']) || !is_string($decoded['summary']) || trim($decoded['summary']) === '') {
            return null;
        }

        return [
            'threat_level'      => $decoded['threat_level'],
            'confidence'        => $this->clampInt($decoded['confidence'] ?? 50, 0, 100),
            'summary'           => $this->cleanString($decoded['summary']),
            'recommendations'   => $this->cleanStringArray($decoded['recommendations'] ?? []),
            'affected_sectors'  => $this->cleanStringArray($decoded['affected_sectors'] ?? []),
            'watch_items'       => $this->cleanStringArray($decoded['watch_items'] ?? []),
        ];
    }

    /**
     * Parse and validate an explanation response.
     */
    public function validateExplanation(?string $raw): ?array
    {
        $decoded = $this->extractJson($raw);
        if ($decoded === null) {
            return null;
        }

        if (!isset($decoded['what_happened']) || !is_string($decoded['what_happened'])) {
            return null;
        }

        return [
            'what_happened'       => $this->cleanString($decoded['what_happened']),
            'why_it_matters'      => $this->cleanString($decoded['why_it_matters'] ?? ''),
            'potential_impact'    => $this->cleanString($decoded['potential_impact'] ?? ''),
            'confidence'          => $this->clampInt($decoded['confidence'] ?? 50, 0, 100),
            'recommended_action'  => $this->cleanString($decoded['recommended_action'] ?? ''),
        ];
    }

    /**
     * Extract JSON from raw model output, tolerating markdown code fences
     * (```json ... ```) which models frequently add despite instructions not to.
     */
    private function extractJson(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $text = trim($raw);

        // Strip markdown fences if present
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        // Find first { and last } as a defensive boundary in case of stray prose
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }
        $text = substr($text, $start, $end - $start + 1);

        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function clampInt(mixed $value, int $min, int $max): int
    {
        $int = is_numeric($value) ? (int) $value : $min;
        return max($min, min($max, $int));
    }

    private function cleanString(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        return trim(strip_tags($value));
    }

    private function cleanStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $clean = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $clean[] = trim(strip_tags($item));
            }
        }
        return array_slice($clean, 0, 10); // sanity cap
    }
}
