let financeiroModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    financeiroModalInstance = new bootstrap.Modal(document.getElementById('financeiroModal'));
    loadObrasSelect();
    loadFinanceiro();

    document.getElementById('financeiroForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveFinanceiro();
    });

    document.getElementById('filtroFinanceiroForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadFinanceiro();
    });

    // Limpar valor padrão 0.00 ao focar no valor do lançamento
    const valorInput = document.getElementById('finValor');
    if (valorInput) {
        valorInput.addEventListener('focus', function() {
            const val = this.value.trim();
            if (val === '0' || val === '0.00' || parseFloat(val) === 0) {
                this.value = '';
            }
        });
        valorInput.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.value = '0.00';
            }
        });
    }
});

async function loadObrasSelect() {
    try {
        const obrasList = await api.get('obras');
        const select = document.getElementById('finObraId');
        select.innerHTML = '<option value="">Selecione a Obra</option>';
        obrasList.forEach(obra => {
            select.innerHTML += `<option value="${obra.id}">${obra.nome}</option>`;
        });
    } catch (error) {
        console.error('Erro ao carregar obras:', error);
    }
}

async function loadFinanceiro() {
    try {
        const status = document.getElementById('filtroStatusFin').value;
        const tipo = document.getElementById('filtroTipoFin').value;
        const dataInicio = document.getElementById('filtroDataInicioFin').value;
        const dataFim = document.getElementById('filtroDataFimFin').value;

        let url = 'financeiro';
        let queryParams = [];
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (tipo) queryParams.push(`tipo=${encodeURIComponent(tipo)}`);
        if (dataInicio) queryParams.push(`data_inicio=${encodeURIComponent(dataInicio)}`);
        if (dataFim) queryParams.push(`data_fim=${encodeURIComponent(dataFim)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const registros = await api.get(url);
        const tbody = document.getElementById('financeiroTableBody');
        tbody.innerHTML = '';

        registros.forEach(reg => {
            const tr = document.createElement('tr');

            let statusBadge = 'bg-secondary';
            if (reg.status === 'Pago' || reg.status === 'Recebido') statusBadge = 'bg-success';
            else if (reg.status === 'Pendente') statusBadge = 'bg-warning text-dark';
            else if (reg.status === 'Cancelado') statusBadge = 'bg-danger';

            const tipoBadge = reg.tipo === 'despesa' ? 'bg-danger' : 'bg-primary';

            tr.innerHTML = `
                <td>${reg.id}</td>
                <td>${reg.obra_nome}</td>
                <td><span class="badge ${tipoBadge}">${reg.tipo.toUpperCase()}</span></td>
                <td class="no-uppercase">${reg.descricao}</td>
                <td>R$ ${parseFloat(reg.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                <td><span class="badge ${statusBadge}">${reg.status}</span></td>
                <td>${reg.data_vencimento || '-'}</td>
                <td>
                    <button class="btn btn-action btn-action-edit" onclick='editFinanceiro(${JSON.stringify(reg)})' title="Editar Registro"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn btn-action btn-action-delete" onclick='deleteFinanceiro(${reg.id})' title="Excluir Registro"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar financeiro:', error);
    }
}

function openFinanceiroModal() {
    document.getElementById('financeiroForm').reset();
    document.getElementById('finId').value = '';
    document.getElementById('financeiroModalTitle').textContent = 'Novo Registro Financeiro';
    financeiroModalInstance.show();
}

function editFinanceiro(reg) {
    document.getElementById('finId').value = reg.id;
    document.getElementById('finObraId').value = reg.obra_id;
    document.getElementById('finTipo').value = reg.tipo;
    document.getElementById('finDescricao').value = reg.descricao;
    document.getElementById('finValor').value = reg.valor;
    document.getElementById('finStatus').value = reg.status;
    document.getElementById('finDataVencimento').value = reg.data_vencimento;
    document.getElementById('financeiroModalTitle').textContent = 'Editar Registro';
    financeiroModalInstance.show();
}

async function saveFinanceiro() {
    const id = document.getElementById('finId').value;
    const formId = document.querySelector("form:not([id^=filtro])").id;
    const form = document.getElementById(formId);
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        obra_id: document.getElementById('finObraId').value,
        tipo: document.getElementById('finTipo').value,
        descricao: document.getElementById('finDescricao').value,
        valor: document.getElementById('finValor').value,
        status: document.getElementById('finStatus').value,
        data_vencimento: document.getElementById('finDataVencimento').value
    };

    try {
        if (id) {
            await api.put(`financeiro/${id}`, body);
        } else {
            await api.post('financeiro', body);
        }
        financeiroModalInstance.hide();
        loadFinanceiro();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteFinanceiro(id) {
    if (confirm('Tem certeza que deseja excluir este registro?')) {
        try {
            await api.delete(`financeiro/${id}`);
            loadFinanceiro();
        } catch (error) {
            alert(error.message);
        }
    }
}
