class Auth {
    constructor() { this.user = JSON.parse(localStorage.getItem('user')); }
    isAuthenticated() { return this.user !== null; }
    async login(email, senha) {
        try { const data = await api.post('auth', { email, senha }); this.user = data.user; localStorage.setItem('user', JSON.stringify(this.user)); return true; } catch (error) { alert(error.message); return false; }
    }
    async logout() { try { await api.delete('auth'); localStorage.removeItem('user'); this.user = null; window.location.href = '../index.html'; } catch (error) { console.error(error); } }
    applyTheme() {
        if (this.user && this.user.tema === 'escuro') {
            document.documentElement.setAttribute('data-theme', 'escuro');
        } else {
            document.documentElement.setAttribute('data-theme', 'claro');
        }
    }

    async toggleTheme() {
        const newTheme = document.documentElement.getAttribute('data-theme') === 'escuro' ? 'claro' : 'escuro';
        document.documentElement.setAttribute('data-theme', newTheme);
        if (this.user) {
            this.user.tema = newTheme;
            localStorage.setItem('user', JSON.stringify(this.user));
            await api.post('tema', { tema: newTheme });
        }
    }

    applyPermissions() {
        if (!this.user) return;
        const nivel = this.user.nivel_acesso;

        // Esconder todos os menus que exigem permissão específica e então revelar baseando no perfil
        document.querySelectorAll('[data-access]').forEach(el => {
            const accessList = el.getAttribute('data-access').split(',');
            if (!accessList.includes(nivel) && nivel !== 'Administrador') {
                el.classList.add('hide-no-permission');
            }
        });
    }

    async fetchNotifCount() {
        try {
            const data = await api.get('notificacoes?count_only=1');
            const badge = document.getElementById('sidebarNotifBadge');
            if (badge) {
                badge.textContent = data.unread;
                if(data.unread > 0) badge.classList.remove('d-none');
                else badge.classList.add('d-none');
            }
        } catch (e) {}
    }

    checkAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '../index.html';
            return;
        }

        // 1. Renderização imediata baseada em cache local para excelente UX sem delay
        const userNameEl = document.getElementById('userName');
        if (userNameEl) { userNameEl.textContent = this.user.nome; }
        this.applyTheme();
        this.applyPermissions();
        this.fetchNotifCount();

        // 2. Validação rigorosa em background com o servidor (Sincronização em Tempo Real)
        api.get('auth').then(data => {
            if (data && data.user) {
                // Sincroniza dados com o estado mais recente do banco, preservando o tema do LocalStorage
                const temaAtual = this.user.tema;
                this.user = data.user;
                this.user.tema = temaAtual;
                localStorage.setItem('user', JSON.stringify(this.user));

                if (userNameEl) { userNameEl.textContent = this.user.nome; }
                this.applyPermissions(); // Reaplica permissões se o nível de acesso foi alterado
            } else {
                localStorage.removeItem('user');
                window.location.href = '../index.html';
            }
        }).catch(() => {
            localStorage.removeItem('user');
            window.location.href = '../index.html';
        });

        // 3. Loop periódico para notificações
        if (!this.notifInterval) {
            this.notifInterval = setInterval(() => this.fetchNotifCount(), 30000);
        }
    }
}
const auth = new Auth();

// Injetar VLibras, Loader e interações mobile globalmente em todas as páginas via JS
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('globalLoader')) {
        const loader = document.createElement('div');
        loader.id = 'globalLoader';
        loader.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>';
        document.body.appendChild(loader);
    }

    if (!document.querySelector('.vw-plugin-wrapper')) {
        const vlibrasDiv = document.createElement('div');
        vlibrasDiv.setAttribute('vw', '');
        vlibrasDiv.className = 'enabled';
        vlibrasDiv.innerHTML = '<div vw-access-button class="active"></div><div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div>';
        document.body.appendChild(vlibrasDiv);

        const script = document.createElement('script');
        script.src = 'https://vlibras.gov.br/app/vlibras-plugin.js';
        script.onload = () => { new window.VLibras.Widget('https://vlibras.gov.br/app'); };
        document.body.appendChild(script);
    }

    // Gerenciador do Menu Lateral Mobile e Overlay de Fundo
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        // Criar overlay se não existir
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        const toggleBtns = document.querySelectorAll('.toggle-sidebar-btn');
        
        const toggleSidebar = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };

        toggleBtns.forEach(btn => {
            // Remove o atributo onclick inline se existir para evitar cliques duplicados
            btn.removeAttribute('onclick');
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                toggleSidebar();
            });
        });

        overlay.addEventListener('click', toggleSidebar);
    }
});
