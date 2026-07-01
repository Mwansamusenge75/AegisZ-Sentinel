<?php
/**
 * AegisZ Sentinel - News Article Entity (v0.7.0)
 * Normalized representation of a news article from any source.
 * Used by the future NewsIngestionService (v0.8.0+) and the
 * news_articles table created in this release's migration.
 */

namespace App\Domain\News;

class NewsArticleEntity
{
    public ?int    $id              = null;
    public string  $sourceId        = '';
    public string  $title           = '';
    public ?string $summary         = null;
    public ?string $url             = null;
    public ?string $publishedAt     = null;
    public string  $priorityTier    = 'international';
    // AI analysis fields (populated by future OpenRouter pass, nullable until then)
    public ?bool   $affectsZambia   = null;
    public ?array  $affectedSectors = null;
    public ?bool   $isImmediate     = null;
    public ?int    $aiConfidence    = null;
    public ?string $aiSummary       = null;
    public ?string $createdAt       = null;

    public static function fromArray(array $data): self
    {
        $entity                  = new self();
        $entity->id              = isset($data['id']) ? (int) $data['id'] : null;
        $entity->sourceId        = $data['source_id'] ?? '';
        $entity->title           = $data['title'] ?? '';
        $entity->summary         = $data['summary'] ?? null;
        $entity->url             = $data['url'] ?? null;
        $entity->publishedAt     = $data['published_at'] ?? null;
        $entity->priorityTier    = $data['priority_tier'] ?? 'international';
        $entity->affectsZambia   = isset($data['affects_zambia']) ? (bool) $data['affects_zambia'] : null;
        $entity->affectedSectors = isset($data['affected_sectors']) ? json_decode($data['affected_sectors'], true) : null;
        $entity->isImmediate     = isset($data['is_immediate']) ? (bool) $data['is_immediate'] : null;
        $entity->aiConfidence    = isset($data['ai_confidence']) ? (int) $data['ai_confidence'] : null;
        $entity->aiSummary       = $data['ai_summary'] ?? null;
        $entity->createdAt       = $data['created_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'source_id'        => $this->sourceId,
            'title'            => $this->title,
            'summary'          => $this->summary,
            'url'              => $this->url,
            'published_at'     => $this->publishedAt,
            'priority_tier'    => $this->priorityTier,
            'affects_zambia'   => $this->affectsZambia,
            'affected_sectors' => $this->affectedSectors ? json_encode($this->affectedSectors) : null,
            'is_immediate'     => $this->isImmediate,
            'ai_confidence'    => $this->aiConfidence,
            'ai_summary'       => $this->aiSummary,
            'created_at'       => $this->createdAt,
        ];
    }
}
