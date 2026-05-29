let funcaoModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    funcaoModalInstance = new bootstrap.Modal(document.getElementById('funcaoModal'));
    loadFuncoes();

    document.getElementById('funcaoForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveFuncao();
    });

    document.getElementById('filtroFuncoesForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadFuncoes();
    });

    // Limpar valor padrão 0.00 ao focar no salário base
    const salarioInput = document.getElementById('funSalario');
    if (salarioInput) {
        salarioInput.addEventListener('focus', function() {
            const val = this.value.trim();
            if (val === '0' || val === '0.00' || parseFloat(val) === 0) {
                this.value = '';
            }
        });
        salarioInput.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.value = '0.00';
            }
        });
    }
});

async function loadFuncoes() {
    try {
        const status = document.getElementById('filtroStatusFuncao').value;
        const search = document.getElementById('filtroNomeFuncao').value;

        let url = 'funcoes';
        let queryParams = [];
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const funcoes = await api.get(url);
        const tbody = document.getElementById('funcoesTableBody');
        tbody.innerHTML = '';

        funcoes.forEach(fun => {
            const tr = document.createElement('tr');
            const statusBadge = fun.status === 'ativo' ? 'bg-success' : 'bg-danger';

            tr.innerHTML = `
                <td>${fun.id}</td>
                <td>${fun.nome}</td>
                <td>${fun.setor || '-'}</td>
                <td>R$ ${parseFloat(fun.salario_base || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                <td>${fun.horario_padrao || '-'}</td>
                <td><span class="badge ${statusBadge}">${fun.status.toUpperCase()}</span></td>
                <td>
                    <button class="btn btn-action btn-action-edit" onclick='editFuncao(${JSON.stringify(fun)})' title="Editar Função"><i class="bi bi-pencil-square"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar funções:', error);
    }
}

function openFuncaoModal() {
    document.getElementById('funcaoForm').reset();
    document.getElementById('funId').value = '';
    document.getElementById('divFunStatus').style.display = 'none';
    document.getElementById('funcaoModalTitle').textContent = 'Nova Função';
    funcaoModalInstance.show();
}

function editFuncao(fun) {
    document.getElementById('funId').value = fun.id;
    document.getElementById('funNome').value = fun.nome;
    document.getElementById('funSetor').value = fun.setor;
    document.getElementById('funSalario').value = fun.salario_base;
    document.getElementById('funHorario').value = fun.horario_padrao;
    document.getElementById('funPermissao').value = fun.permissao_sugerida;
    document.getElementById('funDescricao').value = fun.descricao;

    document.getElementById('divFunStatus').style.display = 'block';
    document.getElementById('funStatus').value = fun.status;

    document.getElementById('funcaoModalTitle').textContent = 'Editar Função';
    funcaoModalInstance.show();
}

async function saveFuncao() {
    const id = document.getElementById('funId').value;
    const formId = document.querySelector("form:not([id^=filtro])").id;
    const form = document.getElementById(formId);
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        nome: document.getElementById('funNome').value,
        setor: document.getElementById('funSetor').value,
        salario_base: document.getElementById('funSalario').value,
        horario_padrao: document.getElementById('funHorario').value,
        permissao_sugerida: document.getElementById('funPermissao').value,
        descricao: document.getElementById('funDescricao').value,
        status: document.getElementById('funStatus').value || 'ativo'
    };

    try {
        if (id) {
            await api.put(`funcoes/${id}`, body);
        } else {
            await api.post('funcoes', body);
        }
        funcaoModalInstance.hide();
        loadFuncoes();
    } catch (error) {
        alert(error.message);
    }
}
