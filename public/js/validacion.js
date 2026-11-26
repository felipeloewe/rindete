(function () {
    var dialogo = document.getElementById('aviso-campos');
    if (!dialogo || !dialogo.showModal) {
        return;
    }
    var lista = dialogo.querySelector('ul');
    var pendientes = [];
    var avisando = false;

    function nombre(campo) {
        if (campo.type === 'checkbox') {
            var seccion = campo.closest('section');
            var titulo = seccion ? seccion.querySelector('h2') : null;
            if (titulo && titulo.textContent.trim() !== '') {
                return titulo.textContent.trim();
            }
        }
        var etiqueta = campo.closest('label');
        if (!etiqueta) {
            return 'Este campo';
        }
        var copia = etiqueta.cloneNode(true);
        copia.querySelectorAll('input, select, textarea, small, button').forEach(function (nodo) {
            nodo.remove();
        });
        var texto = (copia.textContent || '').replace(/\s+/g, ' ').trim();
        return texto || 'Este campo';
    }

    function marcar(campo) {
        campo.classList.add('falta');
        campo.setAttribute('aria-invalid', 'true');
    }

    function soltar(campo) {
        // validity.valid no dispara el evento invalid. checkValidity() sí, y el aviso
        // se abría de nuevo en cada tecla mientras el campo seguía corto.
        if (campo.validity && campo.validity.valid) {
            campo.classList.remove('falta');
            campo.removeAttribute('aria-invalid');
        }
    }

    document.addEventListener('invalid', function (evento) {
        var campo = evento.target;
        if (!campo || !campo.form || campo.form.method === 'dialog') {
            return;
        }
        evento.preventDefault();
        marcar(campo);
        pendientes.push(campo);
        if (avisando) {
            return;
        }
        avisando = true;
        window.setTimeout(function () {
            var vistos = [];
            var nombres = [];
            pendientes.forEach(function (item) {
                if (vistos.indexOf(item) !== -1) {
                    return;
                }
                vistos.push(item);
                var titulo = nombre(item);
                if (nombres.indexOf(titulo) === -1) {
                    nombres.push(titulo);
                }
            });
            pendientes = [];
            avisando = false;
            lista.replaceChildren();
            nombres.forEach(function (titulo) {
                var item = document.createElement('li');
                item.textContent = titulo;
                lista.appendChild(item);
            });
            if (!dialogo.open) {
                dialogo.showModal();
            }
            var primero = vistos[0];
            dialogo.addEventListener('close', function () {
                if (primero && document.contains(primero)) {
                    primero.focus();
                }
            }, { once: true });
        }, 0);
    }, true);

    document.addEventListener('input', function (evento) {
        if (evento.target && evento.target.form) {
            soltar(evento.target);
        }
    });
    document.addEventListener('change', function (evento) {
        if (evento.target && evento.target.form) {
            soltar(evento.target);
        }
    });
}());
