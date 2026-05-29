let equipeModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    equipeModalInstance = new bootstrap.Modal(document.getElementById('equipeModal'));
    loadEquipes();
    loadSelects();

    document.getElementById('equipeForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveEquipe();
    });

    document.getElementById('filtroEquipesForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadEquipes();
    });
});

async function loadSelects() {
    try {
        const funcionarios = await api.get('funcionarios?status=ativo');
        const selectResp = document.getElementById('eqpResponsavel');
        selectResp.innerHTML = '<option value="">Nenhum</option>';
        funcionarios.forEach(f => {
            selectResp.innerHTML += `<option value="${f.id}">${f.nome}</option>`;
        });

        const obras = await api.get('obras');
        const selectObra = document.getElementById('eqpObra');
        selectObra.innerHTML = '<option value="">Nenhuma</option>';
        obras.forEach(o => {
            selectObra.innerHTML += `<option value="${o.id}">${o.nome}</option>`;
        });
    } catch (e) {
        console.error("Erro carregando selects de equipe", e);
    }
}

async function loadEquipes() {
    try {
        const status = document.getElementById('filtroStatusEquipe').value;
        const search = document.getElementById('filtroNomeEquipe').value;

        let url = 'equipes';
        let queryParams = [];
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const equipes = await api.get(url);
        const tbody = document.getElementById('equipesTableBody');
        tbody.innerHTML = '';

        equipes.forEach(eqp => {
            const tr = document.createElement('tr');
            const statusBadge = eqp.status === 'ativo' ? 'bg-success' : 'bg-danger';
            const etapaAtual = eqp.etapa_nome ? `${eqp.etapa_nome} (${eqp.etapa_status})` : '-';

            tr.innerHTML = `
                <td>${eqp.id}</td>
                <td>${eqp.nome}</td>
                <td>${eqp.responsavel_nome || '-'}</td>
                <td>${eqp.obra_nome || '-'}</td>
                <td>${etapaAtual}</td>
                <td>${eqp.data_criacao || '-'}</td>
                <td><span class="badge ${statusBadge}">${eqp.status.toUpperCase()}</span></td>
                <td>
                    <button class="btn btn-action btn-action-edit" onclick='editEquipe(${JSON.stringify(eqp)})' title="Editar Equipe"><i class="bi bi-pencil-square"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar equipes:', error);
    }
}

function openEquipeModal() {
    document.getElementById('equipeForm').reset();
    document.getElementById('eqpId').value = '';
    document.getElementById('divEqpStatus').style.display = 'none';
    document.getElementById('equipeModalTitle').textContent = 'Nova Equipe';
    equipeModalInstance.show();
}

function editEquipe(eqp) {
    document.getElementById('eqpId').value = eqp.id;
    document.getElementById('eqpNome').value = eqp.nome;
    document.getElementById('eqpResponsavel').value = eqp.responsavel_id || '';
    document.getElementById('eqpObra').value = eqp.obra_id || '';
    document.getElementById('eqpDescricao').value = eqp.descricao;

    document.getElementById('divEqpStatus').style.display = 'block';
    document.getElementById('eqpStatus').value = eqp.status;

    document.getElementById('equipeModalTitle').textContent = 'Editar Equipe';
    equipeModalInstance.show();
}

async function saveEquipe() {
    const id = document.getElementById('eqpId').value;
    const formId = document.querySelector("form:not([id^=filtro])").id;
    const form = document.getElementById(formId);
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        nome: document.getElementById('eqpNome').value,
        responsavel_id: document.getElementById('eqpResponsavel').value,
        obra_id: document.getElementById('eqpObra').value,
        descricao: document.getElementById('eqpDescricao').value,
        status: document.getElementById('eqpStatus').value || 'ativo'
    };

    try {
        if (id) {
            await api.put(`equipes/${id}`, body);
        } else {
            await api.post('equipes', body);
        }
        equipeModalInstance.hide();
        loadEquipes();
    } catch (error) {
        alert(error.message);
    }
}
