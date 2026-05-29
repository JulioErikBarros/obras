let materialModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    materialModalInstance = new bootstrap.Modal(document.getElementById('materialModal'));
    loadObrasSelect();
    loadMateriais();

    document.getElementById('materialForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveMaterial();
    });

    document.getElementById('filtroMateriaisForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadMateriais();
    });
});

async function loadObrasSelect() {
    try {
        const obrasList = await api.get('obras');
        const select = document.getElementById('matObraId');
        select.innerHTML = '<option value="">Selecione a Obra</option>';
        obrasList.forEach(obra => {
            select.innerHTML += `<option value="${obra.id}">${obra.nome}</option>`;
        });
    } catch (error) {
        console.error('Erro ao carregar obras:', error);
    }
}

async function loadMateriais() {
    try {
        const categoria = document.getElementById('filtroCategoria').value;
        const search = document.getElementById('filtroNome').value;
        let url = 'materiais';
        let queryParams = [];
        if (categoria) queryParams.push(`categoria=${encodeURIComponent(categoria)}`);
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const materiais = await api.get(url);
        const tbody = document.getElementById('materiaisTableBody');
        tbody.innerHTML = '';

        materiais.forEach(mat => {
            const tr = document.createElement('tr');

            const tipoBadge = mat.tipo_movimentacao === 'entrada' ? 'bg-success' : 'bg-danger';

            tr.innerHTML = `
                <td>${mat.id}</td>
                <td>${mat.obra_nome}</td>
                <td>${mat.nome} <br><small class="text-muted">${mat.categoria}</small></td>
                <td>${mat.quantidade}</td>
                <td>${mat.unidade_medida}</td>
                <td><span class="badge ${tipoBadge}">${mat.tipo_movimentacao.toUpperCase()}</span></td>
                <td>
                    <button class="btn btn-action btn-action-delete" onclick='deleteMaterial(${mat.id})' title="Excluir Movimentação"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar materiais:', error);
    }
}

function openMaterialModal() {
    document.getElementById('materialForm').reset();
    materialModalInstance.show();
}

async function saveMaterial() {
    const formId = document.querySelector("form:not([id^=filtro])").id;
    const form = document.getElementById(formId);
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        obra_id: document.getElementById('matObraId').value,
        nome: document.getElementById('matNome').value,
        categoria: document.getElementById('matCategoria').value,
        quantidade: document.getElementById('matQuantidade').value,
        unidade_medida: document.getElementById('matUnidade').value,
        tipo_movimentacao: document.getElementById('matTipo').value
    };

    try {
        await api.post('materiais', body);
        materialModalInstance.hide();
        loadMateriais();
    } catch (error) {
        alert(error.message); // Exibe alert se saldo for insuficiente, conforme lógica backend
    }
}

async function deleteMaterial(id) {
    if (confirm('Tem certeza que deseja excluir esta movimentação?')) {
        try {
            await api.delete(`materiais/${id}`);
            loadMateriais();
        } catch (error) {
            alert(error.message);
        }
    }
}
