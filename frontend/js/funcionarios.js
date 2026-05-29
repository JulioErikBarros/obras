let funcionarioModalInstance;
let demissaoModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    funcionarioModalInstance = new bootstrap.Modal(document.getElementById('funcionarioModal'));
    demissaoModalInstance = new bootstrap.Modal(document.getElementById('demissaoModal'));
    loadFuncionarios();

    document.getElementById('funcionarioForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveFuncionario();
    });

    document.getElementById('demissaoForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveDemissao();
    });

    document.getElementById('filtroFuncionariosForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadFuncionarios();
    });

    loadSelects();
});

async function loadSelects() {
    try {
        const funcoes = await api.get('funcoes?status=ativo');
        const eqps = await api.get('equipes?status=ativo');

        const selFun = document.getElementById('funcFuncaoId');
        funcoes.forEach(f => {
            selFun.innerHTML += `<option value="${f.id}">${f.nome}</option>`;
        });

        const selEqp = document.getElementById('funcEquipeId');
        eqps.forEach(e => {
            selEqp.innerHTML += `<option value="${e.id}">${e.nome}</option>`;
        });
    } catch(e) {
        console.error("Erro no loadSelects de Func", e);
    }
}

async function loadFuncionarios() {
    try {
        const busca = document.getElementById('filtroBusca').value;
        const status = document.getElementById('filtroStatusFunc').value;
        const equipe = document.getElementById('filtroEquipe').value;
        const funcao = document.getElementById('filtroFuncao').value;
        const dataAdmissao = document.getElementById('filtroDataAdmissao').value;

        let url = 'funcionarios';
        let queryParams = [];
        if (busca) queryParams.push(`search=${encodeURIComponent(busca)}`);
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (equipe) queryParams.push(`equipe=${encodeURIComponent(equipe)}`);
        if (funcao) queryParams.push(`funcao=${encodeURIComponent(funcao)}`);
        if (dataAdmissao) queryParams.push(`data_admissao=${encodeURIComponent(dataAdmissao)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const funcionarios = await api.get(url);
        const tbody = document.getElementById('funcionariosTableBody');
        tbody.innerHTML = '';

        funcionarios.forEach(func => {
            const tr = document.createElement('tr');

            let statusBadge = 'bg-success';
            if (func.status === 'afastado') statusBadge = 'bg-warning text-dark';
            if (func.status === 'demitido') statusBadge = 'bg-danger';

            let buttons = `<button class="btn btn-action btn-action-edit" onclick='editFuncionario(${JSON.stringify(func)})' title="Editar Funcionário"><i class="bi bi-pencil-square"></i></button>`;

            if (func.status !== 'demitido') {
                buttons += `<button class="btn btn-action btn-action-demitir" onclick='openDemissaoModal(${JSON.stringify(func)})' title="Demitir Funcionário"><i class="bi bi-person-x-fill"></i></button>`;
            }
            buttons += `<button class="btn btn-action btn-action-delete" onclick='deleteFuncionario(${func.id})' title="Excluir Funcionário"><i class="bi bi-trash"></i></button>`;

            tr.innerHTML = `
                <td>${func.id}</td>
                <td>${func.nome}</td>
                <td>${func.funcao_nome || '<span class="text-muted">Não definida</span>'}</td>
                <td>${func.equipe_nome || '<span class="text-muted">Nenhuma</span>'}</td>
                <td>${func.data_admissao || '-'}</td>
                <td><span class="badge ${statusBadge}">${func.status ? func.status.toUpperCase() : 'ATIVO'}</span></td>
                <td>${buttons}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar funcionários:', error);
    }
}

function openFuncionarioModal() {
    document.getElementById('funcionarioForm').reset();
    document.getElementById('funcionarioForm').classList.remove('was-validated');
    document.getElementById('funcId').value = '';
    document.getElementById('divFuncStatus').style.display = 'none';
    document.getElementById('funcionarioModalTitle').textContent = 'Novo Funcionário';
    funcionarioModalInstance.show();
}

function openDemissaoModal(func) {
    document.getElementById('demissaoForm').reset();
    document.getElementById('demissaoForm').classList.remove('was-validated');
    document.getElementById('demFuncId').value = func.id;
    document.getElementById('demFuncNome').textContent = func.nome;
    demissaoModalInstance.show();
}

function editFuncionario(func) {
    document.getElementById('funcionarioForm').classList.remove('was-validated');
    document.getElementById('funcId').value = func.id;
    document.getElementById('funcNome').value = func.nome;
    document.getElementById('funcFuncaoId').value = func.funcao_id || '';
    document.getElementById('funcEquipeId').value = func.equipe_id || '';
    document.getElementById('funcAdmissao').value = func.data_admissao;

    document.getElementById('divFuncStatus').style.display = 'block';
    document.getElementById('funcStatus').value = func.status === 'demitido' ? 'ativo' : (func.status || 'ativo'); // Não permite selecionar demitido na edição

    document.getElementById('funcionarioModalTitle').textContent = 'Editar Funcionário';
    funcionarioModalInstance.show();
}

async function saveDemissao() {
    const form = document.getElementById('demissaoForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const id = document.getElementById('demFuncId').value;
    const body = {
        data_demissao: document.getElementById('demData').value,
        motivo_demissao: document.getElementById('demMotivo').value
    };

    try {
        await api.post(`demitir/${id}`, body);
        demissaoModalInstance.hide();
        loadFuncionarios();
    } catch(e) {
        alert(e.message);
    }
}

async function saveFuncionario() {
    const form = document.getElementById('funcionarioForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    const id = document.getElementById('funcId').value;
    const body = {
        nome: document.getElementById('funcNome').value,
        funcao_id: document.getElementById('funcFuncaoId').value,
        equipe_id: document.getElementById('funcEquipeId').value,
        data_admissao: document.getElementById('funcAdmissao').value,
        status: document.getElementById('funcStatus').value
    };

    try {
        if (id) {
            await api.put(`funcionarios/${id}`, body);
        } else {
            await api.post('funcionarios', body);
        }
        funcionarioModalInstance.hide();
        loadFuncionarios();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteFuncionario(id) {
    if (confirm('Tem certeza que deseja excluir este funcionário?')) {
        try {
            await api.delete(`funcionarios/${id}`);
            loadFuncionarios();
        } catch (error) {
            alert(error.message);
        }
    }
}
