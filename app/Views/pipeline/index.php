<?php
// Prepare clean pipeline data for JS (strip heavy fields like ia_analise, ia_market_analysis)
$__pipelineJson = json_encode(
    array_map(function($s) {
        $cleanLeads = array_map(function($l) {
            return [
                'id' => $l['id'],
                'company_name' => $l['company_name'] ?? '',
                'company_niche' => $l['company_niche'] ?? '',
                'company_city' => $l['company_city'] ?? '',
                'score' => (int)($l['score'] ?? 0),
                'estimated_value' => floatval($l['estimated_value'] ?? 0),
                'is_final' => (bool)($l['is_final'] ?? false),
                'created_at' => $l['created_at'] ?? ''
            ];
        }, $s['leads'] ?? []);
        return [
            'id' => $s['id'],
            'name' => $s['name'],
            'color' => $s['color'],
            'leads' => $cleanLeads,
            'count' => $s['count'],
            'total_value' => floatval($s['total_value'] ?? 0),
            'valid_transitions' => $s['valid_transitions'] ?? [],
            'is_final' => (bool)($s['is_final'] ?? false)
        ];
    }, $pipeline),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
);
?>
<script>window.__pipelineStages = <?= $__pipelineJson ?>;</script>

<!-- Pipeline Kanban -->
<div class="space-y-6" x-cloak x-data="pipelineBoard()">

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-sm"
         :class="toast.type === 'error' ? 'bg-red-900 text-red-200 border border-red-700' : 'bg-green-900 text-green-200 border border-green-700'">
        <span x-text="toast.msg"></span>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Pipeline</h1>
            <p class="text-sm text-gray-400 mt-1">Arraste e solte para mover leads entre estágios</p>
        </div>
        <a href="/leads/create" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Novo Lead
        </a>
    </div>

    <!-- Kanban Board -->
    <div class="flex gap-4 overflow-x-auto pb-4" style="min-height: 60vh;">
        <template x-for="(stage, si) in stages" :key="stage.id">
            <div class="flex-shrink-0 w-72"
                 @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                 @drop="handleDrop(stage, $event)">
                <!-- Column Header -->
                <div class="mb-3">
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" :style="'background-color:' + stage.color"></span>
                            <h3 class="text-sm font-semibold text-white" x-text="stage.name"></h3>
                        </div>
                        <span class="text-xs text-gray-500" x-text="stage.count + ' leads'"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 px-1" x-text="stage.total_value > 0 ? 'R$ ' + Number(stage.total_value).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : ''"></div>
                </div>

                <!-- Cards -->
                <div class="space-y-2 min-h-[200px] bg-gray-900/50 rounded-xl p-2">
                    <template x-for="lead in stage.leads" :key="lead.id">
                        <div class="bg-gray-900 border border-gray-800 rounded-lg p-3 cursor-grab hover:border-purple-500/50 transition"
                             draggable="true"
                             @dragstart="dragging = lead.id; $event.dataTransfer.effectAllowed = 'move'"
                             @dragend="dragging = null">
                            <a :href="'/leads/' + lead.id" class="block">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-white truncate" x-text="lead.company_name"></span>
                                    <span x-show="lead.score > 0" class="text-xs font-medium px-1.5 py-0.5 rounded"
                                          :class="lead.score >= 70 ? 'bg-green-900/50 text-green-400' : (lead.score >= 40 ? 'bg-yellow-900/50 text-yellow-400' : 'bg-gray-800 text-gray-400')"
                                          x-text="lead.score"></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span x-show="lead.company_niche" x-text="lead.company_niche"></span>
                                    <span x-show="lead.company_niche && lead.company_city">·</span>
                                    <span x-show="lead.company_city" x-text="lead.company_city"></span>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span x-show="lead.estimated_value > 0" class="text-xs text-green-400" x-text="'R$ ' + Number(lead.estimated_value).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                                    <span class="text-xs text-gray-600" x-text="new Date(lead.created_at).toLocaleDateString('pt-BR')"></span>
                                </div>
                            </a>
                        </div>
                    </template>
                    
                    <!-- Empty state -->
                    <div x-show="stage.leads.length === 0" class="text-center py-8 text-gray-600">
                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-30"></i>
                        <p class="text-xs">Arraste leads aqui</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function pipelineBoard() {
    return {
        stages: window.__pipelineStages,
        dragging: null,
        toast: { msg: '', type: 'info', show: false },

        showToast(msg, type = 'info') {
            this.toast = { msg, type, show: true };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        async moveLead(leadId, stageId, reason) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value;
            try {
                const formData = new FormData();
                formData.append('pipeline_stage_id', stageId);
                formData.append('reason', reason);
                formData.append('_token', token);
                const resp = await fetch('/pipeline/move/' + leadId, {
                    method: 'POST',
                    body: formData
                });
                const data = await resp.json();
                if (data.error) {
                    this.showToast(data.error, 'error');
                    setTimeout(() => window.location.reload(), 1500);
                }
                return data;
            } catch (e) {
                console.error(e);
                return null;
            }
        },

        handleDrop(targetStage, event) {
            if (!this.dragging) return;
            const leadId = this.dragging;
            const fromStage = this.stages.find(s => s.leads.find(l => l.id === leadId));
            if (!fromStage) { this.dragging = null; return; }

            // Validate transition
            const validTransitions = fromStage.valid_transitions || [];
            if (validTransitions.length > 0 && !validTransitions.includes(targetStage.id)) {
                const names = validTransitions.map(id => this.stages.find(s => s.id === id)?.name).join(', ');
                this.showToast('Transição não permitida. De ' + fromStage.name + ' só pode ir para: ' + names, 'error');
                this.dragging = null;
                return;
            }

            // Check if lead is in final stage
            const leadData = fromStage.leads.find(l => l.id === leadId);
            if (leadData && leadData.is_final) {
                this.showToast('Lead em "Fechado" é final e não pode ser movido.', 'error');
                this.dragging = null;
                return;
            }

            // Prompt for reason via modal
            const leadIdRef = leadId;
            const fromStageRef = fromStage;
            const targetStageRef = targetStage;
            const leadDataRef = leadData;
            Alpine.store('promptModal').open({
                title: 'Mudança de Estágio',
                message: 'Motivo da mudança de estágio:',
                placeholder: 'Ex: Cliente demonstrou interesse...',
                confirmText: 'Mover',
                value: '',
                onConfirm: (reason) => {
                    // Move lead locally
                    const leadIdx = fromStageRef.leads.findIndex(l => l.id === leadIdRef);
                    if (leadIdx > -1) {
                        fromStageRef.leads.splice(leadIdx, 1);
                        fromStageRef.count = fromStageRef.leads.length;
                    }
                    targetStageRef.leads.push(leadDataRef);
                    targetStageRef.count = targetStageRef.leads.length;
                    // Persist via API
                    this.moveLead(leadIdRef, targetStageRef.id, reason);
                },
                onCancel: () => {
                    // Drag cancelled, nothing to revert since we didn't move yet
                }
            });
            this.dragging = null;
        }
    }
}
</script>