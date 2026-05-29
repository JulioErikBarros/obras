class API {
    constructor(baseURL) { this.baseURL = baseURL; }
    showLoader() {
        const loader = document.getElementById('globalLoader');
        if(loader) loader.style.display = 'flex';
    }

    hideLoader() {
        const loader = document.getElementById('globalLoader');
        if(loader) loader.style.display = 'none';
    }

    async request(endpoint, method = 'GET', body = null, isFormData = false) {
        this.showLoader();
        const options = { method, credentials: 'include' };
        if (body) { if (isFormData) { options.body = body; } else { options.headers = { 'Content-Type': 'application/json' }; options.body = JSON.stringify(body); } }
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, options);
            if (response.status === 401) { window.location.href = '../index.html'; return null; }
            if (response.status === 403) { throw new Error('Acesso negado.'); }
            const data = await response.json();
            if (!response.ok) { throw new Error(data.message || 'Erro na requisição'); }
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        } finally {
            this.hideLoader();
        }
    }
    // Converter endpoints dinamicamente para lidar com parâmetros GET.
    // Ex: "obras/1" se torna "?resource=obras&id=1"
    // Ex: "relatorios?tipo=gastos" se torna "?resource=relatorios&tipo=gastos"
    formatEndpoint(endpoint) {
        if (endpoint.includes('?')) {
            const parts = endpoint.split('?');
            const pathParts = parts[0].split('/');
            const resource = pathParts[0];
            const id = pathParts[1] ? `&id=${pathParts[1]}` : '';
            return `?resource=${resource}${id}&${parts[1]}`;
        } else {
            const pathParts = endpoint.split('/');
            const resource = pathParts[0];
            const id = pathParts[1] ? `&id=${pathParts[1]}` : '';
            return `?resource=${resource}${id}`;
        }
    }

    get(endpoint) { return this.request(this.formatEndpoint(endpoint), 'GET'); }
    post(endpoint, body, isFormData = false) { return this.request(this.formatEndpoint(endpoint), 'POST', body, isFormData); }
    put(endpoint, body) { return this.request(this.formatEndpoint(endpoint), 'PUT', body); }
    delete(endpoint) { return this.request(this.formatEndpoint(endpoint), 'DELETE'); }
}
const api = new API('/backend/index.php');
