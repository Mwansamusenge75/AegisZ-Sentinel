<?php use App\Core\Security; ?>
<div class="max-w-2xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= $ioc->id ?>" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to IOC</a>
    <h1 class="text-2xl font-bold text-white mb-6">Edit IOC</h1>
    <p class="text-xs text-gray-500 font-mono mb-6 bg-gray-800 px-3 py-2 rounded"><?= Security::e($ioc->value) ?></p>
    <?php if (!empty($errors)): ?><div class="mb-4 p-4 bg-red-900/30 border border-red-700 rounded-lg"><ul class="text-red-300 text-sm list-disc list-inside"><?php foreach ($errors as $e): ?><li><?= Security::e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
        <form method="POST" action="<?= Security::e($baseUrl) ?>/iocs/update">
            <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
            <input type="hidden" name="ioc_id" value="<?= $ioc->id ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Confidence Score (0–100)</label>
                    <input type="number" name="confidence_score" min="0" max="100" value="<?= (int)$ioc->confidenceScore ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Expiry Date</label>
                    <input type="date" name="expiry_at" value="<?= Security::e($ioc->expiryAt ? substr($ioc->expiryAt,0,10) : '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Tags (comma-separated)</label>
                    <input type="text" name="tags" value="<?= Security::e(implode(', ', $ioc->getTagsArray())) ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                </div>
            </div>
            <div class="mt-6 flex items-center gap-4">
                <button type="submit" class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
                <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= $ioc->id ?>" class="text-sm text-gray-400 hover:text-white">Cancel</a>
                <div class="ml-auto">
                    <form method="POST" action="<?= Security::e($baseUrl) ?>/iocs/delete" onsubmit="return confirm('Delete this IOC permanently?')">
                        <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                        <input type="hidden" name="ioc_id" value="<?= $ioc->id ?>">
                        <button type="submit" class="text-sm text-red-400 hover:underline">Delete IOC</button>
                    </form>
                </div>
            </div>
        </form>
    </div>
</div>
