let acidenteModalInstance;

document.addEventListener('DOMContentLoaded', () => {
    applyRoleAccess();
    loadObrasSelect();
    loadRhSelects();
    toggleRelatorioFields();

    // Inicializar Modal de Acidente
    const modalEl = document.getElementById('acidenteModal');
    if (modalEl) {
        acidenteModalInstance = new bootstrap.Modal(modalEl);
    }

    document.getElementById('relatorioForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        gerarRelatorio();
    });

    // Enviar formulário de registro de acidentes
    const acForm = document.getElementById('acidenteForm');
    if (acForm) {
        acForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const descEl = document.getElementById('acidenteDescricao');
            const descricao = descEl.value.trim();
            if (!descricao) {
                descEl.classList.add('is-invalid');
                return;
            }
            descEl.classList.remove('is-invalid');

            try {
                await api.post('acidentes', { descricao: descricao });
                acForm.reset();
                acidenteModalInstance.hide();
                alert('Acidente registrado com sucesso!');
                
                // Se estiver no relatório de acidentes, regerar imediatamente
                if (document.getElementById('relTipo').value === 'acidentes') {
                    gerarRelatorio();
                }
            } catch (error) {
                alert(error.message || 'Erro ao registrar acidente.');
            }
        });
    }
});

function openAcidenteModal() {
    if (acidenteModalInstance) {
        const descEl = document.getElementById('acidenteDescricao');
        if (descEl) descEl.classList.remove('is-invalid');
        document.getElementById('acidenteForm').reset();
        acidenteModalInstance.show();
    }
}

function applyRoleAccess() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) return;
    
    const nivel = user.nivel_acesso;
    const relTipo = document.getElementById('relTipo');
    if (!relTipo) return;
    
    Array.from(relTipo.options).forEach(opt => {
        let allow = false;
        if (nivel === 'Administrador') {
            allow = true;
        } else if (nivel === 'RH') {
            allow = (opt.value === 'rh' || opt.value === 'acidentes');
        } else if (nivel === 'Financeiro') {
            allow = (opt.value === 'gastos');
        } else if (nivel === 'Almoxarifado') {
            allow = (opt.value === 'estoque');
        } else if (nivel === 'Engenharia') {
            allow = (opt.value === 'acidentes');
        } else if (nivel === 'Visualização') {
            allow = false;
        }
        if (!allow) {
            opt.remove();
        }
    });
    
    if (relTipo.options.length > 0) {
        relTipo.selectedIndex = 0;
    }
}

async function loadObrasSelect() {
    try {
        const select = document.getElementById('relObraId');
        if (!select) return;
        const obrasList = await api.get('obras');
        obrasList.forEach(obra => {
            select.innerHTML += `<option value="${obra.id}">${obra.nome}</option>`;
        });
    } catch (error) {
        console.error('Erro ao carregar obras para filtro:', error);
    }
}

async function loadRhSelects() {
    try {
        const selectCargo = document.getElementById('relRhCargo');
        if (selectCargo) {
            const funcoes = await api.get('funcoes?status=ativo');
            funcoes.forEach(f => {
                selectCargo.innerHTML += `<option value="${f.id}">${f.nome}</option>`;
            });
        }

        const selectEquipe = document.getElementById('relRhEquipe');
        if (selectEquipe) {
            const equipes = await api.get('equipes?status=ativo');
            equipes.forEach(eq => {
                selectEquipe.innerHTML += `<option value="${eq.id}">${eq.nome}</option>`;
            });
        }
    } catch (error) {
        console.error('Erro ao carregar seletores de RH:', error);
    }
}

function toggleRelatorioFields() {
    const relTipo = document.getElementById('relTipo');
    if (!relTipo) return;
    const tipo = relTipo.value;
    
    // Ocultar divs de filtros específicos
    const divObra = document.getElementById('divObraId');
    const divIni = document.getElementById('divDataInicio');
    const divFim = document.getElementById('divDataFim');
    const divCat = document.getElementById('divCategoria');
    
    const divRhStat = document.getElementById('divRhStatus');
    const divRhCarg = document.getElementById('divRhCargo');
    const divRhEqp = document.getElementById('divRhEquipe');
    const divRhAdmDe = document.getElementById('divRhAdmissaoDe');
    const divRhAdmAte = document.getElementById('divRhAdmissaoAte');
    const divRhDemDe = document.getElementById('divRhDemissaoDe');
    const divRhDemAte = document.getElementById('divRhDemissaoAte');
    
    if (divObra) divObra.style.display = 'none';
    if (divIni) divIni.style.display = 'none';
    if (divFim) divFim.style.display = 'none';
    if (divCat) divCat.style.display = 'none';
    
    if (divRhStat) divRhStat.style.display = 'none';
    if (divRhCarg) divRhCarg.style.display = 'none';
    if (divRhEqp) divRhEqp.style.display = 'none';
    if (divRhAdmDe) divRhAdmDe.style.display = 'none';
    if (divRhAdmAte) divRhAdmAte.style.display = 'none';
    if (divRhDemDe) divRhDemDe.style.display = 'none';
    if (divRhDemAte) divRhDemAte.style.display = 'none';
    
    // Oculta cards de resumo por padrão
    const rhCards = document.getElementById('relatorioRhResumoCards');
    if (rhCards) rhCards.classList.add('d-none');
    
    const acCards = document.getElementById('relatorioAcidentesResumoCards');
    if (acCards) acCards.classList.add('d-none');
    
    if (tipo === 'gastos') {
        if (divObra) divObra.style.display = 'block';
        if (divIni) divIni.style.display = 'block';
        if (divFim) divFim.style.display = 'block';
    } else if (tipo === 'estoque') {
        if (divObra) divObra.style.display = 'block';
        if (divCat) divCat.style.display = 'block';
    } else if (tipo === 'rh') {
        if (divRhStat) divRhStat.style.display = 'block';
        if (divRhCarg) divRhCarg.style.display = 'block';
        if (divRhEqp) divRhEqp.style.display = 'block';
        if (divRhAdmDe) divRhAdmDe.style.display = 'block';
        if (divRhAdmAte) divRhAdmAte.style.display = 'block';
        toggleDismissalDateFields();
    } else if (tipo === 'acidentes') {
        if (divIni) divIni.style.display = 'block';
        if (divFim) divFim.style.display = 'block';
    }
}

function toggleDismissalDateFields() {
    const relRhStatus = document.getElementById('relRhStatus');
    if (!relRhStatus) return;
    const status = relRhStatus.value;
    const divRhDemDe = document.getElementById('divRhDemissaoDe');
    const divRhDemAte = document.getElementById('divRhDemissaoAte');
    
    if (status === 'ativo' || status === 'afastado') {
        if (divRhDemDe) divRhDemDe.style.display = 'none';
        if (divRhDemAte) divRhDemAte.style.display = 'none';
        document.getElementById('relRhDemissaoDe').value = '';
        document.getElementById('relRhDemissaoAte').value = '';
    } else {
        if (divRhDemDe) divRhDemDe.style.display = 'block';
        if (divRhDemAte) divRhDemAte.style.display = 'block';
    }
}

function limparFiltros() {
    const form = document.getElementById('relatorioForm');
    if (form) form.reset();
    toggleRelatorioFields();
    
    document.getElementById('relatorioHead').innerHTML = '';
    document.getElementById('relatorioBody').innerHTML = `
        <tr>
            <td class="text-center text-muted py-4">Selecione o tipo de relatório e clique em "Gerar".</td>
        </tr>
    `;
    const rhCards = document.getElementById('relatorioRhResumoCards');
    if (rhCards) rhCards.classList.add('d-none');
    
    const acCards = document.getElementById('relatorioAcidentesResumoCards');
    if (acCards) acCards.classList.add('d-none');
}

async function gerarRelatorio() {
    const tipo = document.getElementById('relTipo').value;
    
    let url = `relatorios?tipo=${tipo}`;
    let queryParams = [];
    
    if (tipo === 'gastos') {
        const obraId = document.getElementById('relObraId').value;
        const dataInicio = document.getElementById('relDataInicio').value;
        const dataFim = document.getElementById('relDataFim').value;
        if (obraId) queryParams.push(`obra_id=${encodeURIComponent(obraId)}`);
        if (dataInicio && dataFim) {
            queryParams.push(`data_inicio=${encodeURIComponent(dataInicio)}`);
            queryParams.push(`data_fim=${encodeURIComponent(dataFim)}`);
        }
    } else if (tipo === 'estoque') {
        const obraId = document.getElementById('relObraId').value;
        const categoria = document.getElementById('relCategoria').value;
        if (obraId) queryParams.push(`obra_id=${encodeURIComponent(obraId)}`);
        if (categoria) queryParams.push(`categoria=${encodeURIComponent(categoria)}`);
    } else if (tipo === 'rh') {
        const status = document.getElementById('relRhStatus').value;
        const funcaoId = document.getElementById('relRhCargo').value;
        const equipeId = document.getElementById('relRhEquipe').value;
        const admissaoDe = document.getElementById('relRhAdmissaoDe').value;
        const admissaoAte = document.getElementById('relRhAdmissaoAte').value;
        const demissaoDe = document.getElementById('relRhDemissaoDe').value;
        const demissaoAte = document.getElementById('relRhDemissaoAte').value;

        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (funcaoId) queryParams.push(`funcao_id=${encodeURIComponent(funcaoId)}`);
        if (equipeId) queryParams.push(`equipe_id=${encodeURIComponent(equipeId)}`);
        if (admissaoDe) queryParams.push(`admissao_inicio=${encodeURIComponent(admissaoDe)}`);
        if (admissaoAte) queryParams.push(`admissao_fim=${encodeURIComponent(admissaoAte)}`);
        if (demissaoDe) queryParams.push(`demissao_inicio=${encodeURIComponent(demissaoDe)}`);
        if (demissaoAte) queryParams.push(`demissao_fim=${encodeURIComponent(demissaoAte)}`);
    } else if (tipo === 'acidentes') {
        const dataInicio = document.getElementById('relDataInicio').value;
        const dataFim = document.getElementById('relDataFim').value;
        if (dataInicio && dataFim) {
            queryParams.push(`data_inicio=${encodeURIComponent(dataInicio)}`);
            queryParams.push(`data_fim=${encodeURIComponent(dataFim)}`);
        }
    }

    if (queryParams.length > 0) {
        url += `&${queryParams.join('&')}`;
    }

    try {
        const dados = await api.get(url);
        renderizarTabela(tipo, dados);
    } catch (error) {
        console.error('Erro ao gerar relatório:', error);
        alert(error.message || 'Erro ao gerar relatório.');
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

function renderizarTabela(tipo, dados) {
    const thead = document.getElementById('relatorioHead');
    const tbody = document.getElementById('relatorioBody');
    const rhCards = document.getElementById('relatorioRhResumoCards');
    const acCards = document.getElementById('relatorioAcidentesResumoCards');

    thead.innerHTML = '';
    tbody.innerHTML = '';
    if (rhCards) rhCards.classList.add('d-none');
    if (acCards) acCards.classList.add('d-none');

    if (dados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4">Nenhum dado encontrado para os filtros selecionados.</td></tr>';
        return;
    }

    if (tipo === 'gastos') {
        thead.innerHTML = `
            <tr>
                <th>Obra</th>
                <th>Descrição</th>
                <th>Valor (R$)</th>
                <th>Status</th>
                <th>Vencimento</th>
            </tr>
        `;

        let total = 0;
        dados.forEach(item => {
            total += parseFloat(item.valor);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.obra_nome || 'N/A'}</td>
                <td class="no-uppercase">${item.descricao}</td>
                <td>R$ ${parseFloat(item.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                <td>${item.status}</td>
                <td>${formatDate(item.data_vencimento)}</td>
            `;
            tbody.appendChild(tr);
        });

        const trTotal = document.createElement('tr');
        trTotal.innerHTML = `
            <td colspan="2" class="text-end fw-bold">TOTAL:</td>
            <td colspan="3" class="fw-bold">R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(trTotal);

    } else if (tipo === 'estoque') {
        thead.innerHTML = `
            <tr>
                <th>Obra</th>
                <th>Material</th>
                <th>Entradas</th>
                <th>Saídas</th>
                <th>Saldo Disponível</th>
                <th>Unidade</th>
            </tr>
        `;

        dados.forEach(item => {
            const tr = document.createElement('tr');
            let saldoClass = item.saldo <= 0 ? 'text-danger fw-bold' : 'text-success fw-bold';

            tr.innerHTML = `
                <td>${item.obra_nome || 'N/A'}</td>
                <td>${item.nome} <br><small class="text-muted">${item.categoria || 'Outros'}</small></td>
                <td>${item.entradas || 0}</td>
                <td>${item.saidas || 0}</td>
                <td class="${saldoClass}">${item.saldo || 0}</td>
                <td>${item.unidade_medida}</td>
            `;
            tbody.appendChild(tr);
        });
    } else if (tipo === 'rh') {
        if (rhCards) rhCards.classList.remove('d-none');
        
        thead.innerHTML = `
            <tr>
                <th>Nome</th>
                <th>Cargo (Função)</th>
                <th>Equipe</th>
                <th>Status</th>
                <th>Admissão</th>
                <th>Desligamento</th>
                <th>Motivo Desligamento</th>
                <th>Salário Base (R$)</th>
                <th>Horário</th>
            </tr>
        `;

        let totalListados = dados.length;
        let totalAtivos = 0;
        let totalDemitidos = 0;
        let totalSalarios = 0;

        dados.forEach(func => {
            if (func.status === 'demitido') {
                totalDemitidos++;
            } else {
                totalAtivos++;
                totalSalarios += parseFloat(func.salario_base || 0);
            }

            const tr = document.createElement('tr');
            let badgeClass = 'bg-success';
            if (func.status === 'afastado') badgeClass = 'bg-warning text-dark';
            if (func.status === 'demitido') badgeClass = 'bg-danger';

            const statusBadge = `<span class="badge ${badgeClass}">${func.status.toUpperCase()}</span>`;
            const salarioBase = parseFloat(func.salario_base || 0);
            const salarioFormatado = salarioBase > 0 
                ? salarioBase.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : 'Não informado';

            tr.innerHTML = `
                <td class="fw-bold">${func.nome}</td>
                <td>${func.funcao_nome || '<span class="text-muted">Não cadastrada</span>'}</td>
                <td>${func.equipe_nome || '<span class="text-muted">Sem equipe</span>'}</td>
                <td>${statusBadge}</td>
                <td>${formatDate(func.data_admissao)}</td>
                <td>${formatDate(func.data_demissao)}</td>
                <td class="text-wrap no-uppercase" style="max-width: 200px;">${func.motivo_demissao || '-'}</td>
                <td>${salarioFormatado}</td>
                <td>${func.horario_padrao || '-'}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('resumoTotal').textContent = totalListados;
        document.getElementById('resumoAtivos').textContent = totalAtivos;
        document.getElementById('resumoDemitidos').textContent = totalDemitidos;
        document.getElementById('resumoFolha').textContent = totalSalarios.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    } else if (tipo === 'acidentes') {
        if (acCards) acCards.classList.remove('d-none');

        thead.innerHTML = `
            <tr>
                <th>ID</th>
                <th>Data da Ocorrência</th>
                <th>Descrição do Acidente</th>
                <th>Dias Seguros Anteriores</th>
            </tr>
        `;

        let maxDias = 0;
        dados.forEach(item => {
            const dias = parseInt(item.dias_sem_acidentes) || 0;
            if (dias > maxDias) {
                maxDias = dias;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.id}</td>
                <td>${formatDate(item.data_registro)}</td>
                <td class="text-wrap no-uppercase">${item.descricao || 'Sem descrição detalhada'}</td>
                <td><strong>${dias} dias</strong></td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('resumoAcidentesTotal').textContent = dados.length;
        document.getElementById('resumoAcidentesMaxDias').textContent = maxDias + " dias";
    }

    // Gerar e preencher a visualização de impressão timbrada
    renderizarTimbrado(tipo, dados);
}

function renderizarTimbrado(tipo, dados) {
    const theadPrint = document.getElementById('printTableHead');
    const tbodyPrint = document.getElementById('printTableBody');
    const tituloPrint = document.getElementById('printTituloRelatorio');
    const subtituloPrint = document.getElementById('printSubtituloRelatorio');
    
    if (!tbodyPrint || !theadPrint) return;
    theadPrint.innerHTML = '';
    tbodyPrint.innerHTML = '';
    
    // Configurar dados de emissão
    const dataAtual = new Date();
    document.getElementById('printDataEmissao').textContent = dataAtual.toLocaleDateString('pt-BR') + ' ' + dataAtual.toLocaleTimeString('pt-BR');
    
    const user = JSON.parse(localStorage.getItem('user')) || {};
    document.getElementById('printEmissor').textContent = user.nome || 'Usuário do Sistema';
    document.getElementById('printAssinaturaCargo').textContent = `${user.nome || 'Responsável'} | Nível: ${user.nivel_acesso || 'Gestor'}`;
    
    const status = document.getElementById('relRhStatus') ? document.getElementById('relRhStatus').value : '';
    const obraSelect = document.getElementById('relObraId');
    const obraText = (obraSelect && obraSelect.selectedIndex > -1) ? obraSelect.options[obraSelect.selectedIndex].text : 'Todas';
    document.getElementById('printFiltrosTexto').textContent = `Obra: ${obraText} | Relatório: ${tipo.toUpperCase()}`;

    if (tipo === 'gastos') {
        tituloPrint.textContent = "RELATÓRIO DE GASTOS FINANCEIROS";
        subtituloPrint.textContent = "Demonstrativo Geral de Despesas Pagas e Pendentes";
        
        theadPrint.innerHTML = `
            <tr>
                <th>Obra</th>
                <th>Descrição</th>
                <th>Valor (R$)</th>
                <th>Status</th>
                <th>Vencimento</th>
            </tr>
        `;
        
        let total = 0;
        dados.forEach(item => {
            total += parseFloat(item.valor);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.obra_nome || 'N/A'}</td>
                <td>${item.descricao}</td>
                <td>R$ ${parseFloat(item.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                <td>${item.status}</td>
                <td>${formatDate(item.data_vencimento) || '-'}</td>
            `;
            tbodyPrint.appendChild(tr);
        });
        
        const trTotal = document.createElement('tr');
        trTotal.innerHTML = `
            <td colspan="2" class="text-end fw-bold">TOTAL:</td>
            <td colspan="3" class="fw-bold">R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
        `;
        tbodyPrint.appendChild(trTotal);
        
    } else if (tipo === 'estoque') {
        tituloPrint.textContent = "RELATÓRIO DE ESTOQUE DE MATERIAIS";
        subtituloPrint.textContent = "Demonstrativo de Entradas, Saídas e Saldos de Almoxarifado";
        
        theadPrint.innerHTML = `
            <tr>
                <th>Obra</th>
                <th>Material</th>
                <th>Entradas</th>
                <th>Saídas</th>
                <th>Saldo Disponível</th>
                <th>Unidade</th>
            </tr>
        `;
        
        dados.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.obra_nome || 'N/A'}</td>
                <td>${item.nome} (${item.categoria || 'Outros'})</td>
                <td>${item.entradas || 0}</td>
                <td>${item.saidas || 0}</td>
                <td>${item.saldo || 0}</td>
                <td>${item.unidade_medida}</td>
            `;
            tbodyPrint.appendChild(tr);
        });
        
    } else if (tipo === 'rh') {
        tituloPrint.textContent = "RELATÓRIO DE RECURSOS HUMANOS";
        subtituloPrint.textContent = "Documento Geral de Funcionários e Folha Base";
        
        theadPrint.innerHTML = `
            <tr>
                <th>Nome</th>
                <th>Cargo (Função)</th>
                <th>Equipe</th>
                <th>Status</th>
                <th>Admissão</th>
                <th>Desligamento</th>
                <th>Salário Base (R$)</th>
                <th>Horário</th>
            </tr>
        `;
        
        dados.forEach(func => {
            const tr = document.createElement('tr');
            const salarioBase = parseFloat(func.salario_base || 0);
            const salarioFormatado = salarioBase > 0 
                ? salarioBase.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : 'Não informado';
            tr.innerHTML = `
                <td><strong>${func.nome}</strong></td>
                <td>${func.funcao_nome || 'N/A'}</td>
                <td>${func.equipe_nome || 'N/A'}</td>
                <td>${func.status.toUpperCase()}</td>
                <td>${formatDate(func.data_admissao) || '-'}</td>
                <td>${formatDate(func.data_demissao) || '-'}</td>
                <td>${salarioFormatado}</td>
                <td>${func.horario_padrao || '-'}</td>
            `;
            tbodyPrint.appendChild(tr);
        });
        
    } else if (tipo === 'acidentes') {
        tituloPrint.textContent = "RELATÓRIO DE SEGURANÇA E ACIDENTES";
        subtituloPrint.textContent = "Registro Histórico de Ocorrências e Prevenção de Acidentes";
        
        theadPrint.innerHTML = `
            <tr>
                <th>ID</th>
                <th>Data do Registro</th>
                <th>Descrição do Acidente</th>
                <th>Dias Seguros Anteriores</th>
            </tr>
        `;
        
        dados.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.id}</td>
                <td>${formatDate(item.data_registro)}</td>
                <td class="text-wrap">${item.descricao || 'Sem descrição detalhada'}</td>
                <td>${item.dias_sem_acidentes} dias</td>
            `;
            tbodyPrint.appendChild(tr);
        });
    }
}
