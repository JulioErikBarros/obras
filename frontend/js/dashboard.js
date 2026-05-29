document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
});

async function loadDashboard() {
    try {
        const data = await api.get('dashboard');

        const diasCard = document.getElementById('dashDiasAcidente');
        if (diasCard) diasCard.textContent = data.dias_sem_acidente || 0;
        const diasModal = document.getElementById('dashDiasAcidenteModal');
        if (diasModal) diasModal.textContent = data.dias_sem_acidente || 0;

        document.getElementById('dashTotalObras').textContent = data.total_obras || 0;
        document.getElementById('dashObrasAndamento').textContent = data.obras_andamento || 0;
        document.getElementById('dashObrasAtrasadas').textContent = data.obras_atrasadas || 0;
        document.getElementById('dashFuncionariosAtivos').textContent = data.funcionarios_ativos || 0;

        const gastosFormatado = parseFloat(data.total_gastos_mes || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('dashGastosTotais').textContent = gastosFormatado;

    } catch (error) {
        console.error('Erro ao carregar dashboard', error);
    }
}

let acidenteModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('acidenteModal');
    if (modalEl) {
        acidenteModalInstance = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('hidden.bs.modal', () => {
            hideFormAcidente();
            setTimeout(cleanupBackdrops, 350);
        });
    }
});

function openAcidenteModal() {
    hideFormAcidente();
    acidenteModalInstance.show();
}

function showFormAcidente() {
    document.getElementById('modalAcidenteView').style.display = 'none';
    document.getElementById('modalAcidenteForm').style.display = 'block';
    document.getElementById('acidenteDescricao').value = '';
    document.getElementById('acidenteDescricao').focus();
}

function hideFormAcidente() {
    document.getElementById('modalAcidenteView').style.display = 'block';
    document.getElementById('modalAcidenteForm').style.display = 'none';
}

function cleanupBackdrops() {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

async function submitResetAcidente() {
    const descEl = document.getElementById('acidenteDescricao');
    const descricao = descEl.value.trim();
    if (!descricao) {
        descEl.classList.add('is-invalid');
        return;
    }
    descEl.classList.remove('is-invalid');

    try {
        await api.post('acidentes', { descricao: descricao });
        acidenteModalInstance.hide();
        setTimeout(() => {
            cleanupBackdrops();
            loadDashboard();
        }, 400);
    } catch (error) {
        alert(error.message);
    }
}
