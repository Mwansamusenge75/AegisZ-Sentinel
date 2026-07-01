<?php
/**
 * AegisZ Sentinel - News Source Interface (v0.7.0)
 * Contract that future news provider integrations must implement.
 * v0.7.0 ships this interface and a database table only — NO live
 * fetching, NO HTTP calls to any news provider. This is scaffolding
 * for v0.8.0's "Intelligence Wall" feature per the development spec.
 *
 * Planned implementations (future versions, not built now):
 *   International: CNN, BBC, Reuters, Bloomberg, Al Jazeera
 *   Zambia (priority): ZNBC, Diamond TV, Prime TV Zambia, Muvi TV,
 *                       Phoenix News, News Diggers, Zambia Daily Mail,
 *                       Times of Zambia
 */

namespace App\Domain\News;

interface NewsSourceInterface
{
    /**
     * Unique machine-readable identifier, e.g. 'znbc', 'reuters'.
     */
    public function getSourceId(): string;

    /**
     * Human-readable display name, e.g. 'ZNBC', 'Reuters'.
     */
    public function getDisplayName(): string;

    /**
     * Country/region this source primarily covers.
     */
    public function getRegion(): string;

    /**
     * Priority tier — 'national' (Zambian sources) get priority processing
     * over 'international' per the spec's stated priorities.
     */
    public function getPriorityTier(): string; // 'national' | 'international'

    /**
     * Fetch and return normalized articles since the given timestamp.
     * NOT IMPLEMENTED in v0.7.0 — concrete classes for each provider are
     * a future release. This method signature defines the contract that
     * NewsIngestionService will call once implementations exist.
     *
     * @return NewsArticleEntity[]
     */
    public function fetchSince(\DateTimeImmutable $since): array;
}
