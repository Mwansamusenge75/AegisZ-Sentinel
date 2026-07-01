<?php
declare(strict_types=1);

namespace App\Entities;

class ThreatIntelligence
{
    private ?int $id = null;
    private string $title = '';
    private string $severity = 'Low';
    private int $confidenceScore = 0;
    private array $affectedAssets = [];
    private array $relatedIocs = [];
    private array $relatedCves = [];
    private array $mitreTechniques = [];
    private string $recommendedAction = '';
    private ?string $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getSeverity(): string { return $this->severity; }
    public function setSeverity(string $severity): void { $this->severity = $severity; }

    public function getConfidenceScore(): int { return $this->confidenceScore; }
    public function setConfidenceScore(int $score): void { $this->confidenceScore = $score; }

    public function getAffectedAssets(): array { return $this->affectedAssets; }
    public function setAffectedAssets(array $assets): void { $this->affectedAssets = $assets; }

    public function getRelatedIocs(): array { return $this->relatedIocs; }
    public function setRelatedIocs(array $iocs): void { $this->relatedIocs = $iocs; }

    public function getRelatedCves(): array { return $this->relatedCves; }
    public function setRelatedCves(array $cves): void { $this->relatedCves = $cves; }

    public function getMitreTechniques(): array { return $this->mitreTechniques; }
    public function setMitreTechniques(array $techniques): void { $this->mitreTechniques = $techniques; }

    public function getRecommendedAction(): string { return $this->recommendedAction; }
    public function setRecommendedAction(string $action): void { $this->recommendedAction = $action; }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'severity' => $this->severity,
            'confidence_score' => $this->confidenceScore,
            'affected_assets' => $this->affectedAssets,
            'related_iocs' => $this->relatedIocs,
            'related_cves' => $this->relatedCves,
            'mitre_techniques' => $this->mitreTechniques,
            'recommended_action' => $this->recommendedAction,
            'created_at' => $this->createdAt,
        ];
    }
}
