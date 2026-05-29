let obraModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    obraModalInstance = new bootstrap.Modal(document.getElementById('obraModal'));
    loadObras();

    document.getElementById('obraForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveObra();
    });

    document.getElementById('filtroObrasForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadObras();
    });

    loadSelects();
});

async function loadSelects() {
    try {
        const funcionarios = await api.get('funcionarios?status=ativo');

        const selResp = document.getElementById('obraResponsavelId');
        funcionarios.forEach(f => {
            selResp.innerHTML += `<option value="${f.id}">${f.nome}</option>`;
        });
    } catch(e) {
        console.error("Erro no loadSelects de Obras", e);
    }
}

async function loadObras() {
    try {
        const status = document.getElementById('filtroStatusObra').value;
        const search = document.getElementById('filtroNomeObra').value;

        let url = 'obras';
        let queryParams = [];
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const obras = await api.get(url);
        const tbody = document.getElementById('obrasTableBody');
        tbody.innerHTML = '';

        obras.forEach(obra => {
            const tr = document.createElement('tr');

            let statusBadge = 'bg-secondary';
            if (obra.status === 'Em andamento') statusBadge = 'bg-primary';
            else if (obra.status === 'Concluída') statusBadge = 'bg-success';
            else if (obra.status === 'Paralisada') statusBadge = 'bg-warning';
            else if (obra.status === 'Cancelada') statusBadge = 'bg-danger';

            tr.innerHTML = `
                <td>${obra.id}</td>
                <td>${obra.nome} <br><small class="text-muted">${obra.tipo || 'Outros'}</small></td>
                <td><span class="badge ${statusBadge}">${obra.status}</span></td>
                <td>${obra.responsavel_nome || '-'} <br><small class="text-info">${obra.equipe_nome || ''}</small></td>
                <td>${obra.data_inicio || '-'}</td>
                <td>${obra.data_fim_prevista || '-'}</td>
                <td>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: ${obra.percentual_concluido}%;" aria-valuenow="${obra.percentual_concluido}" aria-valuemin="0" aria-valuemax="100">${obra.percentual_concluido}%</div>
                    </div>
                </td>
                <td>
                    <button class="btn btn-action btn-action-edit" onclick='editObra(${JSON.stringify(obra)})' title="Editar Obra"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn btn-action btn-action-delete" onclick='deleteObra(${obra.id})' title="Excluir Obra"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar obras:', error);
    }
}

function openObraModal() {
    document.getElementById('obraForm').reset();
    document.getElementById('obraId').value = '';
    document.getElementById('obraModalTitle').textContent = 'Nova Obra';
    obraModalInstance.show();
}

function editObra(obra) {
    document.getElementById('obraId').value = obra.id;
    document.getElementById('obraNome').value = obra.nome;
    document.getElementById('obraTipo').value = obra.tipo || 'Outros';
    document.getElementById('obraDescricao').value = obra.descricao || '';
    document.getElementById('obraStatus').value = obra.status;
    document.getElementById('obraEndereco').value = obra.endereco;
    document.getElementById('obraResponsavelId').value = obra.responsavel_id || '';
    document.getElementById('obraDataInicio').value = obra.data_inicio;
    document.getElementById('obraDataFim').value = obra.data_fim_prevista;
    document.getElementById('obraPercentual').value = obra.percentual_concluido;
    document.getElementById('obraModalTitle').textContent = 'Editar Obra';
    obraModalInstance.show();
}

async function saveObra() {
    const id = document.getElementById('obraId').value;
    const formId = document.querySelector("form:not([id^=filtro])").id;
    const form = document.getElementById(formId);
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        nome: document.getElementById('obraNome').value,
        tipo: document.getElementById('obraTipo').value,
        descricao: document.getElementById('obraDescricao').value,
        status: document.getElementById('obraStatus').value,
        endereco: document.getElementById('obraEndereco').value,
        responsavel_id: document.getElementById('obraResponsavelId').value,
        data_inicio: document.getElementById('obraDataInicio').value,
        data_fim_prevista: document.getElementById('obraDataFim').value,
        percentual_concluido: document.getElementById('obraPercentual').value
    };

    try {
        if (id) {
            await api.put(`obras/${id}`, body);
        } else {
            await api.post('obras', body);
        }
        obraModalInstance.hide();
        loadObras();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteObra(id) {
    if (confirm('Tem certeza que deseja excluir esta obra?')) {
        try {
            await api.delete(`obras/${id}`);
            loadObras();
        } catch (error) {
            alert(error.message);
        }
    }
}
