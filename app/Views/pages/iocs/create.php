<?php use App\Core\Security; ?>
<div class="max-w-2xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/iocs" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to IOCs</a>
    <h1 class="text-2xl font-bold text-white mb-6">Add IOC Manually</h1>
    <?php if (!empty($errors)): ?><div class="mb-4 p-4 bg-red-900/30 border border-red-700 rounded-lg"><ul class="text-red-300 text-sm list-disc list-inside"><?php foreach ($errors as $e): ?><li><?= Security::e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
        <div class="mb-4 p-3 bg-aegisz-accent/10 border border-aegisz-accent/30 rounded-lg text-xs text-aegisz-accent">
            Manually created IOCs are assigned source <code class="font-mono">manual</code> and default confidence 75%. They will appear in the IOC list and be included in future correlation runs.
        </div>
        <form method="POST" action="<?= Security::e($baseUrl) ?>/iocs/store">
            <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Type <span class="text-red-400">*</span></label>
                    <select name="type" required class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                        <?php foreach (['ip'=>'IP Address','domain'=>'Domain','url'=>'URL','hash'=>'Hash (MD5/SHA)'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($old['type']??'ip')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Value <span class="text-red-400">*</span></label>
                    <input type="text" name="value" required value="<?= Security::e($old['value']??'') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:border-aegisz-accent"
                           placeholder="e.g. 192.168.1.100 or malicious.example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Confidence Score (0–100)</label>
                    <input type="number" name="confidence_score" min="0" max="100" value="<?= Security::e($old['confidence_score']??'75') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                    <p class="text-xs text-gray-600 mt-1">Default 75 for analyst-asserted IOCs</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Expiry Date (optional)</label>
                    <input type="date" name="expiry_at" value="<?= Security::e($old['expiry_at']??'') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Tags (comma-separated)</label>
                    <input type="text" name="tags" value="<?= Security::e($old['tags']??'') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent"
                           placeholder="e.g. ransomware, apt, phishing">
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">Create IOC</button>
                <a href="<?= Security::e($baseUrl) ?>/iocs" class="text-sm text-gray-400 hover:text-white self-center">Cancel</a>
            </div>
        </form>
    </div>
</div>
