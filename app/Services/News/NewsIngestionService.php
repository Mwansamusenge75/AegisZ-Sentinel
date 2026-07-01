<?php
/**
 * AegisZ Sentinel - News Ingestion Service (v0.7.0 — SCAFFOLDING ONLY)
 *
 * Per the v0.7.0 specification: "Do not implement live TV streaming in
 * this version. Instead, create the ingestion interfaces and service
 * contracts that future releases can plug into."
 *
 * This class defines the pipeline shape (Normalize → Store → AI Analysis)
 * and is fully wired to the database and AI layer, but ships with ZERO
 * registered NewsSourceInterface implementations. Calling run() today
 * is a safe no-op that logs and returns immediately — there is nothing
 * to fetch because no provider classes exist yet (that's v0.8.0+ work).
 *
 * Future version wiring (not built now):
 *   $service->registerSource(new ZnbcNewsSource());
 *   $service->registerSource(new ReutersNewsSource());
 *   etc.
 */

namespace App\Services\News;

use App\Core\Database;
use App\Core\Logger;
use App\Domain\News\NewsSourceInterface;
use App\Domain\News\NewsArticleEntity;
use App\Services\IntelligenceBus\IntelligenceBusService;
use PDO;

class NewsIngestionService
{
    /** @var NewsSourceInterface[] */
    private array $sources = [];

    private PDO    $db;
    private Logger $logger;
    private IntelligenceBusService $bus;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = new Logger();
        $this->bus    = new IntelligenceBusService();
    }

    /**
     * Register a news source implementation. No-op container for now —
     * future versions will populate this in cli/news_worker.php.
     */
    public function registerSource(NewsSourceInterface $source): void
    {
        $this->sources[$source->getSourceId()] = $source;
    }

    /**
     * Run ingestion across all registered sources.
     * v0.7.0: always a no-op (zero sources registered) — logs and exits.
     * This method exists so the pipeline shape and call site are real and
     * testable now, ready for v0.8.0 to populate registerSource() calls.
     */
    public function run(): array
    {
        if (empty($this->sources)) {
            $this->logger->info('[NewsIngestion] No news sources registered — scaffolding only in v0.7.0, skipping run.');
            return ['ingested' => 0, 'sources' => 0];
        }

        // National (Zambian) sources processed before international, per spec priority.
        uasort($this->sources, fn($a, $b) =>
            $a->getPriorityTier() === 'national' ? -1 : ($b->getPriorityTier() === 'national' ? 1 : 0)
        );

        $totalIngested = 0;
        $since = $this->getLastIngestionTime();

        foreach ($this->sources as $source) {
            try {
                $articles = $source->fetchSince($since);
                foreach ($articles as $article) {
                    $this->store($article);
                    $totalIngested++;
                }
                $this->logger->info("[NewsIngestion] {$source->getDisplayName()}: " . count($articles) . ' article(s) ingested');
            } catch (\Throwable $e) {
                $this->logger->warning("[NewsIngestion] {$source->getDisplayName()} failed: " . $e->getMessage());
            }
        }

        if ($totalIngested > 0) {
            $this->bus->publish(IntelligenceBusService::EVENT_NEWS_INGESTED, ['count' => $totalIngested], 'NewsIngestionService');
        }

        return ['ingested' => $totalIngested, 'sources' => count($this->sources)];
    }

    /**
     * Normalize + Store stage of the pipeline (News Feed → Normalize → Store).
     * AI Analysis stage is intentionally NOT invoked here in v0.7.0 — that
     * wiring point exists (IntelligenceAnalysisService::explainObject with
     * objectType='news_article') but is not called until a real source
     * exists to populate articles worth analyzing.
     */
    private function store(NewsArticleEntity $article): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO news_articles
             (source_id, title, summary, url, published_at, priority_tier)
             VALUES (:source_id, :title, :summary, :url, :published_at, :priority_tier)
             ON DUPLICATE KEY UPDATE title = title" // idempotent on unique url
        );
        $stmt->execute([
            'source_id'     => $article->sourceId,
            'title'         => $article->title,
            'summary'       => $article->summary,
            'url'           => $article->url,
            'published_at'  => $article->publishedAt,
            'priority_tier' => $article->priorityTier,
        ]);
    }

    private function getLastIngestionTime(): \DateTimeImmutable
    {
        $stmt = $this->db->query("SELECT MAX(published_at) FROM news_articles");
        $last = $stmt->fetchColumn();
        return $last ? new \DateTimeImmutable($last) : new \DateTimeImmutable('-24 hours');
    }
}
