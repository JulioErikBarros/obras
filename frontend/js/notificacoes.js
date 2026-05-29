document.addEventListener('DOMContentLoaded', () => {
    loadNotificacoes();

    document.getElementById('filtroNotifForm').addEventListener('submit', (e) => {
        e.preventDefault();
        loadNotificacoes();
    });

    // Auto-refresh a cada 30 segundos
    setInterval(loadNotificacoes, 30000);
});

async function loadNotificacoes() {
    try {
        const lida = document.getElementById('filtroLida').value;
        const tipo = document.getElementById('filtroTipo').value;
        const prioridade = document.getElementById('filtroPrioridade').value;

        let url = 'notificacoes';
        let queryParams = [];
        if (lida !== '') queryParams.push(`lida=${encodeURIComponent(lida)}`);
        if (tipo !== '') queryParams.push(`tipo=${encodeURIComponent(tipo)}`);
        if (prioridade !== '') queryParams.push(`prioridade=${encodeURIComponent(prioridade)}`);
        if (queryParams.length > 0) url += `?${queryParams.join('&')}`;

        const notificacoes = await api.get(url);

        // Atualizar contador global de unread
        const countData = await api.get('notificacoes?count_only=1');
        const badge1 = document.getElementById('badgeNotReadCount');
        const badge2 = document.getElementById('sidebarNotifBadge');
        if(badge1) badge1.textContent = countData.unread;
        if(badge2) {
            badge2.textContent = countData.unread;
            if(countData.unread > 0) badge2.classList.remove('d-none');
            else badge2.classList.add('d-none');
        }

        const listGroup = document.getElementById('notificacoesList');
        listGroup.innerHTML = '';

        if (notificacoes.length === 0) {
            listGroup.innerHTML = '<div class="alert alert-secondary">Você não possui notificações.</div>';
            return;
        }

        notificacoes.forEach(notif => {
            const isLida = parseInt(notif.lida) === 1;

            // Definir classes visuais com base no tipo
            let alertClass = 'list-group-item-info';
            let icon = '<i class="bi bi-info-circle-fill text-info me-2"></i>';

            switch(notif.tipo) {
                case 'atraso':
                    alertClass = 'list-group-item-danger';
                    icon = '<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>';
                    break;
                case 'estoque baixo':
                    alertClass = 'list-group-item-warning';
                    icon = '<i class="bi bi-box-seam-fill text-warning me-2"></i>';
                    break;
                case 'pendências':
                    alertClass = 'list-group-item-secondary';
                    icon = '<i class="bi bi-clock-fill text-secondary me-2"></i>';
                    break;
                case 'atualizações':
                    alertClass = 'list-group-item-success';
                    icon = '<i class="bi bi-arrow-repeat text-success me-2"></i>';
                    break;
            }

            const opacityClass = isLida ? 'opacity-50' : '';
            const fwClass = isLida ? '' : 'fw-bold';

            let prioridadeBadge = '';
            if (notif.prioridade === 'alta') prioridadeBadge = '<span class="badge bg-danger ms-2">Alta Prioridade</span>';

            const div = document.createElement('div');
            div.className = `list-group-item flex-column align-items-start ${alertClass} ${opacityClass} mb-2 rounded shadow-sm`;

            div.innerHTML = `
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h5 class="mb-1 ${fwClass}">${icon} ${notif.titulo || notif.tipo.toUpperCase()} ${prioridadeBadge}</h5>
                    <div>
                        <small class="text-muted me-2">${notif.data_criacao || ''}</small>
                        ${!isLida ? `<button class="btn btn-sm btn-light border" onclick="marcarComoLida(event, ${notif.id})">Marcar como lida</button>` : '<small>Lida</small>'}
                    </div>
                </div>
                <p class="mb-1 mt-2 ${fwClass}">${notif.mensagem}</p>
                ${notif.link ? `<a href="${notif.link}" class="btn btn-sm btn-outline-dark mt-2">Ver detalhes</a>` : ''}
                <small class="d-block mt-2 text-muted">Origem: ${notif.modulo_origem || 'Sistema'}</small>
            `;

            listGroup.appendChild(div);
        });
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    }
}

async function marcarComoLida(event, id) {
    if(event) event.preventDefault();
    try {
        await api.post(`notificacoes/${id}`);
        loadNotificacoes();
    } catch (error) {
        alert(error.message);
    }
}

async function markAllAsRead() {
    try {
        await api.post('notificacoes?mark_all=1');
        loadNotificacoes();
    } catch (error) {
        alert(error.message);
    }
}
