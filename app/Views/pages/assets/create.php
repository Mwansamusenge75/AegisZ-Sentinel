<?php use App\Core\Security; ?>
<div class="max-w-3xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/assets" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Assets</a>
    <h1 class="text-2xl font-bold text-white mb-6">Add Asset</h1>
    <?php if (!empty($errors)): ?><div class="mb-4 p-4 bg-red-900/30 border border-red-700 rounded-lg"><div class="text-red-400 font-medium text-sm mb-2">Fix errors:</div><ul class="text-red-300 text-sm space-y-1 list-disc list-inside"><?php foreach ($errors as $e): ?><li><?= Security::e($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
        <form method="POST" action="<?= Security::e($baseUrl) ?>/assets/store">
            <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $field = fn($n,$l,$p='',$req=false,$t='text') =>
                    '<div><label class="block text-sm font-medium text-gray-400 mb-1">'.$l.($req?' <span class="text-red-400">*</span>':'').'</label>'
                    .'<input type="'.$t.'" name="'.$n.'" value="'.Security::e($old[$n]??'').'" '.($req?'required':'')
                    .' placeholder="'.Security::e($p).'" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent"></div>';
                echo $field('name',       'Asset Name', 'e.g. Production DB Server', true);
                echo $field('hostname',   'Hostname',   'e.g. db01.internal');
                echo $field('ip_address', 'IP Address', 'e.g. 10.0.1.50');
                echo $field('owner',      'Owner',      'e.g. IT Department');
                echo $field('department', 'Department', 'e.g. Finance');
                echo $field('location',   'Location',   'e.g. Lusaka DC1');
                echo $field('operating_system', 'Operating System', 'e.g. Windows Server 2022');
                echo $field('network_segment', 'Network Segment', 'e.g. DMZ');
                ?>
                <div><label class="block text-sm font-medium text-gray-400 mb-1">Asset Type <span class="text-red-400">*</span></label>
                    <select name="asset_type" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                        <?php foreach (['server','endpoint','router','app','db','website'] as $v): ?><option value="<?= $v ?>" <?= ($old['asset_type']??'server')===$v?'selected':'' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-400 mb-1">Criticality</label>
                    <select name="criticality" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                        <?php foreach (['low','medium','high','critical'] as $v): ?><option value="<?= $v ?>" <?= ($old['criticality']??'medium')===$v?'selected':'' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div><label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                    <select name="status" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                        <?php foreach (['active','inactive','maintenance'] as $v): ?><option value="<?= $v ?>" <?= ($old['status']??'active')===$v?'selected':'' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-400 mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent resize-none" placeholder="Optional description or notes…"><?= Security::e($old['notes']??'') ?></textarea></div>
            </div>
            <div class="mt-5 flex gap-3">
                <button type="submit" class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">Create Asset</button>
                <a href="<?= Security::e($baseUrl) ?>/assets" class="text-sm text-gray-400 hover:text-white self-center">Cancel</a>
            </div>
        </form>
    </div>
</div>
