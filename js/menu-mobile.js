(function () {
    function telaCompacta() {
        return window.matchMedia('(max-width: 1024px)').matches;
    }

    function fecharOutros(menu, atual) {
        var itens = menu.children;
        for (var i = 0; i < itens.length; i++) {
            if (itens[i] !== atual) {
                itens[i].classList.remove('submenu-aberto');
            }
        }
    }

    function envolverTabelas() {
        var tabelas = document.querySelectorAll('table');
        for (var i = 0; i < tabelas.length; i++) {
            var table = tabelas[i];
            if (table.closest('.table-responsive') || table.closest('.cmp-table-wrap') || table.closest('.ui-datepicker')) {
                continue;
            }
            if (table.parentElement && table.parentElement.closest('table')) {
                continue;
            }
            var wrap = document.createElement('div');
            wrap.className = 'table-responsive';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        }
    }

    function textoCelula(celula) {
        var texto = (celula.textContent || '').replace(/\s+/g, ' ').trim();
        return texto;
    }

    function ehCabecalho(tr) {
        var cells = tr.children;
        for (var i = 0; i < cells.length; i++) {
            var c = cells[i];
            if (c.tagName === 'TH') {
                return true;
            }
            var bg = (c.getAttribute('bgcolor') || '').toLowerCase();
            if (bg && bg !== '#ffffff' && bg !== '#fff' && bg !== 'white' && bg !== '#fffffa') {
                return true;
            }
        }
        return false;
    }

    function rotulosExpandidos(headerRow) {
        var rotulos = [];
        var cells = headerRow.cells;
        for (var i = 0; i < cells.length; i++) {
            var span = parseInt(cells[i].getAttribute('colspan') || '1', 10);
            if (!span || span < 1) {
                span = 1;
            }
            var texto = textoCelula(cells[i]);
            for (var s = 0; s < span; s++) {
                rotulos.push(texto);
            }
        }
        return rotulos;
    }

    function celulaEhAcao(td, rotulo) {
        if (!td || td.className.indexOf('oculto-mobile') !== -1) {
            return false;
        }
        if (rotulo) {
            var nome = rotulo.toLowerCase();
            if (nome === 'ação' || nome === 'ações' || nome === 'acao' || nome === 'acoes') {
                return true;
            }
        }
        var img = td.querySelector('img');
        if (!img) {
            return false;
        }
        var largura = parseInt(img.getAttribute('width') || '20', 10);
        if (largura > 48) {
            return false;
        }
        var texto = textoCelula(td);
        return texto.length < 24;
    }

    function agruparAcoes(row) {
        var cells = row.cells;
        var grupo = [];
        for (var c = 0; c < cells.length; c++) {
            if (cells[c].classList.contains('rv-acao') && cells[c].className.indexOf('oculto-mobile') === -1) {
                grupo.push(cells[c]);
            }
        }
        if (!grupo.length) {
            return;
        }
        var primeira = grupo[0];
        primeira.removeAttribute('data-label');
        for (var i = 1; i < grupo.length; i++) {
            while (grupo[i].firstChild) {
                primeira.appendChild(grupo[i].firstChild);
            }
            grupo[i].classList.add('rv-acao-oculta');
            grupo[i].removeAttribute('data-label');
        }
        separarRotuloAcoes(primeira);
    }

    function separarRotuloAcoes(td) {
        if (!td || td.getAttribute('data-rv-acoes') === '1') {
            return;
        }
        td.removeAttribute('data-label');
        var barra = document.createElement('div');
        barra.className = 'rv-acoes-botoes';
        while (td.firstChild) {
            barra.appendChild(td.firstChild);
        }
        var rotulo = document.createElement('span');
        rotulo.className = 'rv-acoes-rotulo';
        rotulo.appendChild(document.createTextNode('Ações'));
        td.appendChild(rotulo);
        td.appendChild(barra);
        td.setAttribute('data-rv-acoes', '1');
    }

    function limparRecuo(el) {
        if (!el || el.nodeType !== 1) {
            return;
        }
        var tag = el.tagName;
        if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA' || tag === 'BUTTON' || tag === 'A') {
            return;
        }
        var nodes = el.childNodes;
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].nodeType === 3) {
                nodes[i].nodeValue = nodes[i].nodeValue.replace(/^[\u00a0\s]+/, '');
            } else if (nodes[i].nodeType === 1) {
                limparRecuo(nodes[i]);
            }
        }
    }

    function normalizarLabels() {
        if (!telaCompacta()) {
            return;
        }
        var tabelas = document.querySelectorAll('#conteudo form table, #cont form table');
        for (var i = 0; i < tabelas.length; i++) {
            var table = tabelas[i];
            if (table.classList.contains('rv-card-table') || table.getAttribute('border') === '2') {
                continue;
            }
            table.classList.add('rv-form');
            if (table.getAttribute('data-rv-label') === '1') {
                continue;
            }
            limparRecuo(table);
            table.setAttribute('data-rv-label', '1');
        }
    }

    function prepararCartoes() {
        if (!telaCompacta()) {
            return;
        }
        var tabelas = document.querySelectorAll('#conteudo table, #cont table');
        for (var i = 0; i < tabelas.length; i++) {
            var table = tabelas[i];
            if (table.getAttribute('data-rv-card') === '1' || table.closest('.ui-datepicker')) {
                continue;
            }
            if ((table.getAttribute('border') || '') === '0') {
                continue;
            }
            if (table.classList.contains('cmp-campos') || table.classList.contains('pf-temp') || table.classList.contains('pf-feriados') || table.classList.contains('pf-totais')) {
                continue;
            }
            if (table.parentElement && table.parentElement.closest('table')) {
                continue;
            }
            var linhas = table.rows;
            if (!linhas || linhas.length < 2 || !ehCabecalho(linhas[0])) {
                continue;
            }
            if (linhas[0].cells.length < 3) {
                continue;
            }
            var rotulos = rotulosExpandidos(linhas[0]);
            linhas[0].classList.add('rv-card-head');
            for (var r = 1; r < linhas.length; r++) {
                var cells = linhas[r].cells;
                if (cells.length === 1 && cells[0].getAttribute('colspan')) {
                    cells[0].classList.add('rv-card-full');
                    continue;
                }
                for (var c = 0; c < cells.length; c++) {
                    var rotulo = rotulos[c] || '';
                    if (!cells[c].getAttribute('data-label') && rotulo) {
                        cells[c].setAttribute('data-label', rotulo);
                    }
                    if (celulaEhAcao(cells[c], rotulo)) {
                        cells[c].classList.add('rv-acao');
                    }
                }
                agruparAcoes(linhas[r]);
            }
            table.classList.add('rv-card-table');
            table.setAttribute('data-rv-card', '1');
        }
    }

    function iniciar() {
        var menu = document.getElementById('menu');
        var toggle = document.getElementById('menuToggle');
        envolverTabelas();
        normalizarLabels();
        prepararCartoes();
        if (!menu || !toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            var aberto = menu.classList.toggle('menu-aberto');
            toggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            toggle.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
        });

        var itens = menu.children;
        for (var i = 0; i < itens.length; i++) {
            (function (li) {
                if (!li || li.tagName !== 'LI') {
                    return;
                }
                var temSub = false;
                var link = null;
                for (var c = 0; c < li.children.length; c++) {
                    if (li.children[c].tagName === 'UL') {
                        temSub = true;
                    }
                    if (li.children[c].tagName === 'A' && !link) {
                        link = li.children[c];
                    }
                }
                if (!temSub) {
                    return;
                }
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'submenu-toggle';
                btn.setAttribute('aria-label', 'Abrir submenu');
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = '▾';
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var aberto = li.classList.toggle('submenu-aberto');
                    btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                    btn.setAttribute('aria-label', aberto ? 'Fechar submenu' : 'Abrir submenu');
                    fecharOutros(menu, li);
                });
                if (link && link.nextSibling) {
                    li.insertBefore(btn, link.nextSibling);
                } else {
                    li.appendChild(btn);
                }
                if (link) {
                    link.addEventListener('click', function (e) {
                        var href = link.getAttribute('href');
                        var toque = window.matchMedia('(hover: none)').matches;
                        if ((!href || href === '#') && (telaCompacta() || toque)) {
                            e.preventDefault();
                            var aberto = li.classList.toggle('submenu-aberto');
                            btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                            fecharOutros(menu, li);
                        }
                    });
                }
            })(itens[i]);
        }

        menu.addEventListener('click', function (e) {
            if (!telaCompacta()) {
                return;
            }
            var alvo = e.target;
            while (alvo && alvo !== menu && alvo.tagName !== 'A') {
                alvo = alvo.parentNode;
            }
            if (!alvo || alvo.tagName !== 'A') {
                return;
            }
            var href = alvo.getAttribute('href');
            if (href && href !== '#') {
                menu.classList.remove('menu-aberto');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Abrir menu');
            }
        });

        document.addEventListener('click', function (e) {
            if (!telaCompacta()) {
                return;
            }
            if (menu.contains(e.target) || toggle.contains(e.target)) {
                return;
            }
            menu.classList.remove('menu-aberto');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Abrir menu');
        });

        window.addEventListener('resize', function () {
            normalizarLabels();
            prepararCartoes();
            if (!telaCompacta()) {
                menu.classList.remove('menu-aberto');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Abrir menu');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
