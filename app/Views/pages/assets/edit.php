<?php use App\Core\Security; ?>
<div class="max-w-3xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/assets/detail?id=<?= $asset->id ?>" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Asset</a>
    <h1 class="text-2xl font-bold text-white mb-6">Edit: <?= Security::e($asset->name) ?></h1>
    <?php if (!empty($errors)): ?><div class="mb-4 p-4 bg-red-900/30 border border-red-700 rounded-lg"><ul class="text-red-300 text-sm list-disc list-inside"><?php foreach ($errors as $e): ?><li><?= Security::e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
        <form method="POST" action="<?= Security::e($baseUrl) ?>/assets/update">
            <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
            <input type="hidden" name="asset_id" value="<?= $asset->id ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $f = fn($n,$l,$val,$p='') => '<div><label class="block text-sm font-medium text-gray-400 mb-1">'.$l.'</label><input type="text" name="'.$n.'" value="'.Security::e($val??'').'" placeholder="'.Security::e($p).'" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent"></div>';
                echo $f('name',            'Asset Name *',     $asset->name);
                echo $f('hostname',        'Hostname',         $asset->hostname);
                echo $f('ip_address',      'IP Address',       $asset->ipAddress);
                echo $f('owner',           'Owner',            $asset->owner);
                echo $f('department',      'Department',       $asset->department);
                echo $f('location',        'Location',         $asset->location);
                echo $f('operating_system','Operating System', $asset->operatingSystem);
                echo $f('network_segment', 'Network Segment',  $asset->networkSegment);
                ?>
                <?php
                $sel = fn($n,$l,$opts,$cur) => '<div><label class="block text-sm font-medium text-gray-400 mb-1">'.$l.'</label><select name="'.$n.'" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">'.implode('', array_map(fn($v)=>'<option value="'.$v.'" '.($cur===$v?'selected':'').'>'.ucfirst($v).'</option>', $opts)).'</select></div>';
                echo $sel('asset_type',  'Asset Type',   ['server','endpoint','router','app','db','website'],  $asset->assetType);
                echo $sel('criticality', 'Criticality',  ['low','medium','high','critical'],                    $asset->criticality);
                echo $sel('status',      'Status',       ['active','inactive','maintenance'],                   $asset->status);
                ?>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-400 mb-1">Notes</label><textarea name="notes" rows="3" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent resize-none"><?= Security::e($asset->notes ?? '') ?></textarea></div>
            </div>
            <div class="mt-5 flex items-center gap-4">
                <button type="submit" class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
                <a href="<?= Security::e($baseUrl) ?>/assets/detail?id=<?= $asset->id ?>" class="text-sm text-gray-400 hover:text-white">Cancel</a>
                <div class="ml-auto">
                    <form method="POST" action="<?= Security::e($baseUrl) ?>/assets/delete" onsubmit="return confirm('Delete this asset? This cannot be undone.')">
                        <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                        <input type="hidden" name="asset_id" value="<?= $asset->id ?>">
                        <button type="submit" class="text-sm text-red-400 hover:underline">Delete Asset</button>
                    </form>
                </div>
            </div>
        </form>
    </div>
</div>
