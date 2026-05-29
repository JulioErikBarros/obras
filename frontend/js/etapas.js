let etapaModalInstance;
let obrasList = [];

document.addEventListener('DOMContentLoaded', () => {
    etapaModalInstance = new bootstrap.Modal(document.getElementById('etapaModal'));
    loadObrasSelect();
    loadEtapas();

    document.getElementById('etapaForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveEtapa();
    });
});

async function loadObrasSelect() {
    try {
        // Carregar Obras
        obrasList = await api.get('obras');
        const select = document.getElementById('etapaObraId');
        select.innerHTML = '<option value="">Selecione a Obra</option>';
        obrasList.forEach(obra => {
            select.innerHTML += `<option value="${obra.id}">${obra.nome} (${obra.tipo})</option>`;
        });

        // Carregar Equipes
        const equipes = await api.get('equipes?status=ativo');
        const selectEquipe = document.getElementById('etapaEquipeId');
        selectEquipe.innerHTML = '<option value="">Nenhuma Equipe</option>';
        equipes.forEach(eq => {
            selectEquipe.innerHTML += `<option value="${eq.id}">${eq.nome}</option>`;
        });

        // Carregar Funcionários Ativos para o select de Responsável de Etapa
        const funcionarios = await api.get('funcionarios?status=ativo');
        const selectResponsavel = document.getElementById('etapaResponsavelId');
        selectResponsavel.innerHTML = '<option value="">Nenhum Responsável</option>';
        funcionarios.forEach(func => {
            selectResponsavel.innerHTML += `<option value="${func.id}">${func.nome}</option>`;
        });

    } catch (error) {
        console.error('Erro ao carregar seletores de etapas:', error);
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

async function loadEtapas() {
    try {
        // Chamar o novo endpoint que traz obras agrupadas com suas etapas
        const obrasGrouped = await api.get('etapas?grouped=true');
        const accordion = document.getElementById('etapasAccordion');
        accordion.innerHTML = '';

        if (obrasGrouped.length === 0) {
            accordion.innerHTML = `
                <div class="alert alert-info text-center shadow-sm p-4 no-uppercase" role="alert">
                    <i class="bi bi-info-circle fs-3 text-primary d-block mb-2"></i>
                    <strong>Nenhuma obra cadastrada.</strong><br>
                    Cadastre uma obra primeiro para poder visualizar suas etapas.
                </div>
            `;
            return;
        }

        obrasGrouped.forEach((obra, index) => {
            const total = obra.total_etapas;
            const concluidas = obra.etapas_concluidas;
            const percentual = Math.round(obra.percentual_concluido);

            let statusClass = 'bg-secondary';
            if (obra.status === 'Em andamento') statusClass = 'bg-primary';
            else if (obra.status === 'Concluída') statusClass = 'bg-success';
            else if (obra.status === 'Paralisada') statusClass = 'bg-warning text-dark';
            else if (obra.status === 'Cancelada') statusClass = 'bg-danger';

            let statusGeral = `<span class="badge ${statusClass}">${obra.status}</span>`;

            let accordionHtml = `
                <div class="card mb-3 rounded-3 border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 p-3" id="heading${obra.id}">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-md-5 d-flex align-items-center">
                                <button class="btn btn-sm btn-link p-0 me-3 text-decoration-none d-flex align-items-center text-dark" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse${obra.id}" 
                                        aria-expanded="false" 
                                        aria-controls="collapse${obra.id}"
                                        title="Expandir/Recolher Etapas">
                                    <i class="bi bi-chevron-right fs-5 transition-icon" id="icon${obra.id}" style="transition: transform 0.2s ease;"></i>
                                </button>
                                <div>
                                    <h5 class="mb-0 text-dark font-weight-bold" style="font-size: 1.1rem;">${obra.nome}</h5>
                                    <span class="badge bg-light text-muted border px-2 py-1 mt-1 no-uppercase">${obra.tipo}</span>
                                    ${statusGeral}
                                    ${obra.responsavel_nome ? `<div class="text-muted mt-1 small no-uppercase"><i class="bi bi-person-badge"></i> Resp. Técnico: <strong>${obra.responsavel_nome}</strong></div>` : ''}
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                                    <span>Progresso Geral</span>
                                    <strong>${percentual}%</strong>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 5px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: ${percentual}%;" aria-valuenow="${percentual}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="text-muted small mt-1 no-uppercase">
                                    <i class="bi bi-list-check"></i> ${concluidas}/${total} etapas concluídas
                                </div>
                            </div>

                            <div class="col-12 col-md-2 text-md-end">
                                <button class="btn btn-sm btn-outline-primary" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse${obra.id}" 
                                        aria-expanded="false" 
                                        aria-controls="collapse${obra.id}">
                                    <i class="bi bi-list"></i> Detalhes
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="collapse${obra.id}" class="collapse transition-collapse" aria-labelledby="heading${obra.id}">
                        <div class="card-body p-0 border-top bg-light">
            `;

            if (obra.etapas.length === 0) {
                accordionHtml += `
                    <div class="alert alert-warning m-4 text-center no-uppercase" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> Nenhuma etapa cadastrada para esta obra.
                    </div>
                `;
            } else {
                accordionHtml += `
                    <div class="table-responsive border-0 shadow-none m-0 rounded-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Etapa</th>
                                    <th>Equipe</th>
                                    <th>Responsável</th>
                                    <th>Progresso</th>
                                    <th>Status</th>
                                    <th>Início</th>
                                    <th>Previsão</th>
                                    <th class="pe-4 text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                obra.etapas.forEach(etapa => {
                    let statusBadge = 'bg-secondary';
                    if (etapa.status === 'Em andamento') statusBadge = 'bg-primary';
                    else if (etapa.status === 'Concluída') statusBadge = 'bg-success';

                    accordionHtml += `
                        <tr>
                            <td class="ps-4">
                                <strong class="text-dark no-uppercase">${etapa.nome}</strong>
                                ${etapa.descricao ? `<br><small class="text-muted no-uppercase">${etapa.descricao}</small>` : ''}
                                ${etapa.observacoes ? `<br><small class="text-info no-uppercase"><i class="bi bi-sticky"></i> ${etapa.observacoes}</small>` : ''}
                            </td>
                            <td>${etapa.equipe_nome || '<span class="text-muted">-</span>'}</td>
                            <td>${etapa.responsavel_nome || '<span class="text-muted">-</span>'}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; width: 60px; border-radius: 3px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: ${etapa.percentual}%;"></div>
                                    </div>
                                    <span class="small font-weight-bold">${etapa.percentual}%</span>
                                </div>
                            </td>
                            <td><span class="badge ${statusBadge}">${etapa.status}</span></td>
                            <td>${formatDate(etapa.data_inicio)}</td>
                            <td>${formatDate(etapa.data_fim_prevista)}</td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-action btn-action-edit" onclick='editEtapa(${JSON.stringify(etapa)})' title="Editar Etapa"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-action btn-action-delete" onclick='deleteEtapa(${etapa.id})' title="Excluir Etapa"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `;
                });

                accordionHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            accordionHtml += `
                        </div>
                    </div>
                </div>
            `;
            accordion.innerHTML += accordionHtml;
        });

        // Adicionar eventos para rotacionar o chevron icon ao expandir/recolher
        document.querySelectorAll('.collapse').forEach(collapseEl => {
            const obraId = collapseEl.id.replace('collapse', '');
            const icon = document.getElementById(`icon${obraId}`);
            
            collapseEl.addEventListener('show.bs.collapse', () => {
                if (icon) {
                    icon.style.transform = 'rotate(90deg)';
                }
            });
            collapseEl.addEventListener('hide.bs.collapse', () => {
                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        });

    } catch (error) {
        console.error('Erro ao carregar etapas:', error);
    }
}

function openEtapaModal() {
    document.getElementById('etapaForm').reset();
    document.getElementById('etapaId').value = '';
    document.getElementById('etapaPercentual').value = '0';
    document.getElementById('etapaModalTitle').textContent = 'Nova Etapa';
    
    const form = document.getElementById('etapaForm');
    if (form) form.classList.remove('was-validated');
    
    etapaModalInstance.show();
}

function editEtapa(etapa) {
    document.getElementById('etapaId').value = etapa.id;
    document.getElementById('etapaObraId').value = etapa.obra_id;
    document.getElementById('etapaNome').value = etapa.nome;
    document.getElementById('etapaDescricao').value = etapa.descricao || '';
    document.getElementById('etapaObservacoes').value = etapa.observacoes || '';
    document.getElementById('etapaStatus').value = etapa.status;
    document.getElementById('etapaPercentual').value = etapa.percentual || 0;
    document.getElementById('etapaEquipeId').value = etapa.equipe_id || '';
    document.getElementById('etapaResponsavelId').value = etapa.responsavel_id || '';
    document.getElementById('etapaDataInicio').value = etapa.data_inicio || '';
    document.getElementById('etapaDataFimPrevista').value = etapa.data_fim_prevista || '';
    document.getElementById('etapaModalTitle').textContent = 'Editar Etapa';
    
    const form = document.getElementById('etapaForm');
    if (form) form.classList.remove('was-validated');
    
    etapaModalInstance.show();
}

async function saveEtapa() {
    const id = document.getElementById('etapaId').value;
    const form = document.getElementById('etapaForm');
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        obra_id: document.getElementById('etapaObraId').value,
        nome: document.getElementById('etapaNome').value,
        descricao: document.getElementById('etapaDescricao').value,
        observacoes: document.getElementById('etapaObservacoes').value,
        status: document.getElementById('etapaStatus').value,
        percentual: document.getElementById('etapaPercentual').value,
        equipe_id: document.getElementById('etapaEquipeId').value || null,
        responsavel_id: document.getElementById('etapaResponsavelId').value || null,
        data_inicio: document.getElementById('etapaDataInicio').value || null,
        data_fim_prevista: document.getElementById('etapaDataFimPrevista').value || null
    };

    try {
        if (id) {
            await api.put(`etapas/${id}`, body);
        } else {
            await api.post('etapas', body);
        }
        etapaModalInstance.hide();
        loadEtapas();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteEtapa(id) {
    if (confirm('Tem certeza que deseja excluir esta etapa?')) {
        try {
            await api.delete(`etapas/${id}`);
            loadEtapas();
        } catch (error) {
            alert(error.message);
        }
    }
}
