let usuarioModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    usuarioModalInstance = new bootstrap.Modal(document.getElementById('usuarioModal'));
    loadUsuarios();

    document.getElementById('usuarioForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        saveUsuario();
    });
});

async function loadUsuarios() {
    try {
        const usuarios = await api.get('users');
        const tbody = document.getElementById('usuariosTableBody');
        tbody.innerHTML = '';

        usuarios.forEach(user => {
            const tr = document.createElement('tr');
            const statusBadge = user.status === 'ativo' ? 'bg-success' : 'bg-danger';

            tr.innerHTML = `
                <td>${user.id}</td>
                <td>${user.nome}</td>
                <td class="no-uppercase">${user.email}</td>
                <td>${user.nivel_acesso}</td>
                <td><span class="badge ${statusBadge}">${user.status.toUpperCase()}</span></td>
                <td>
                    <button class="btn btn-action btn-action-edit" onclick='editUsuario(${JSON.stringify(user)})' title="Editar Usuário"><i class="bi bi-pencil-square"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
    }
}

function openUsuarioModal() {
    document.getElementById('usuarioForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('divStatus').style.display = 'none';
    document.getElementById('userSenha').required = true;
    document.getElementById('usuarioModalTitle').textContent = 'Novo Usuário';
    usuarioModalInstance.show();
}

function editUsuario(user) {
    document.getElementById('userId').value = user.id;
    document.getElementById('userNome').value = user.nome;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userNivel').value = user.nivel_acesso;
    document.getElementById('userStatus').value = user.status;
    document.getElementById('userSenha').value = '';
    document.getElementById('userSenha').required = false;
    document.getElementById('divStatus').style.display = 'block';
    document.getElementById('usuarioModalTitle').textContent = 'Editar Usuário';
    usuarioModalInstance.show();
}

async function saveUsuario() {
    const id = document.getElementById('userId').value;
    const formId = document.querySelector("form:not([id^=filtro])").id;
    const form = document.getElementById(formId);
    if (form && !form.checkValidity()) {
        form.classList.add("was-validated");
        return;
    }
    const body = {
        nome: document.getElementById('userNome').value,
        email: document.getElementById('userEmail').value,
        nivel_acesso: document.getElementById('userNivel').value,
        status: document.getElementById('userStatus').value,
        senha: document.getElementById('userSenha').value
    };

    try {
        if (id) {
            await api.put(`users/${id}`, body);
        } else {
            await api.post('users', body);
        }
        usuarioModalInstance.hide();
        loadUsuarios();
    } catch (error) {
        alert(error.message);
    }
}
