let documentoModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    documentoModalInstance = new bootstrap.Modal(document.getElementById('documentoModal'));
    loadObrasSelect();
    loadDocumentos();

    document.getElementById('documentoForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        uploadDocumento();
    });
});

async function loadObrasSelect() {
    try {
        const obrasList = await api.get('obras');
        const select = document.getElementById('docObraId');
        select.innerHTML = '<option value="">Selecione a Obra</option>';
        obrasList.forEach(obra => {
            select.innerHTML += `<option value="${obra.id}">${obra.nome}</option>`;
        });
    } catch (error) {
        console.error('Erro ao carregar obras:', error);
    }
}

async function loadDocumentos() {
    try {
        const documentos = await api.get('documentos');
        const tbody = document.getElementById('documentosTableBody');
        tbody.innerHTML = '';

        if (documentos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum documento encontrado.</td></tr>';
            return;
        }

        documentos.forEach(doc => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${doc.id}</td>
                <td>${doc.obra_nome}</td>
                <td>${doc.nome}</td>
                <td>${doc.tipo}</td>
                <td>
                    <a href="../${doc.caminho_arquivo}" target="_blank" class="btn btn-action btn-action-view" title="Visualizar Documento"><i class="bi bi-eye-fill"></i></a>
                    <button class="btn btn-action btn-action-delete" onclick='deleteDocumento(${doc.id})' title="Excluir Documento"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar documentos:', error);
    }
}

function openDocumentoModal() {
    document.getElementById('documentoForm').reset();
    documentoModalInstance.show();
}

async function uploadDocumento() {
    const obraId = document.getElementById('docObraId').value;
    const nome = document.getElementById('docNome').value;
    const tipo = document.getElementById('docTipo').value;
    const arquivoInput = document.getElementById('docArquivo');

    if (arquivoInput.files.length === 0) {
        alert("Selecione um arquivo.");
        return;
    }

    const file = arquivoInput.files[0];

    // Validação de tamanho (Ex: max 5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('O arquivo deve ter no máximo 5MB.');
        return;
    }

    // Validação de extensão
    const allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
        alert('Tipo de arquivo não permitido.');
        return;
    }

    const submitBtn = document.querySelector('#documentoForm button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Enviando...';
    submitBtn.disabled = true;

    const formData = new FormData();
    formData.append('obra_id', obraId);
    formData.append('nome', nome);
    formData.append('tipo', tipo);
    formData.append('arquivo', file);

    try {
        await api.post('documentos', formData, true); // true para isFormData
        documentoModalInstance.hide();
        loadDocumentos();
    } catch (error) {
        alert(error.message);
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

async function deleteDocumento(id) {
    if (confirm('Tem certeza que deseja excluir este documento? O arquivo também será removido do servidor.')) {
        try {
            await api.delete(`documentos/${id}`);
            loadDocumentos();
        } catch (error) {
            alert(error.message);
        }
    }
}
