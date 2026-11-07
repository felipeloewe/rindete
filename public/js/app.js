const navToggle = document.querySelector('[data-nav-toggle]');
const scrim = document.querySelector('[data-scrim]');
const closeNav = () => document.body.classList.remove('nav-open');
navToggle?.addEventListener('click', () => {
  const open = document.body.classList.toggle('nav-open');
  navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
});
scrim?.addEventListener('click', closeNav);

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.getAttribute('data-password-toggle'));
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Ocultar' : 'Ver';
  });
});

document.querySelector('[data-generate]')?.addEventListener('click', () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
  let password = '';
  for (let i = 0; i < 10; i += 1) {
    password += chars[Math.floor(Math.random() * chars.length)];
  }
  const input = document.getElementById('clave');
  if (input) {
    input.value = password;
    input.type = 'text';
  }
});

const country = document.querySelector('[data-country]');
const countryHint = document.querySelector('[data-country-hint]');
const fiscalLabel = document.querySelector('[data-fiscal-label]');
const fiscalInput = document.querySelector('[data-fiscal-input]');
const fiscalHint = document.querySelector('[data-fiscal-hint]');
const refreshCountry = () => {
  if (!country) return;
  const option = country.selectedOptions[0];
  if (countryHint) countryHint.textContent = option?.dataset.hint || '';
  const documento = option?.dataset.id || '';
  const ejemplo = option?.dataset.ejemplo || '';
  if (documento && fiscalLabel) {
    fiscalLabel.textContent = documento + (fiscalLabel.dataset.fiscalSuffix || '');
  }
  if (fiscalInput) fiscalInput.placeholder = ejemplo || documento;
  if (fiscalHint) fiscalHint.textContent = ejemplo ? 'Ejemplo: ' + ejemplo : '';
};
country?.addEventListener('change', refreshCountry);
refreshCountry();

const emailValido = (value) => {
  const email = String(value || '').trim();
  return email.length > 0 && email.length <= 160 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

const soloDigitos = (value) => String(value || '').replace(/\D+/g, '');

const fechaValida = (anio2, mes, dia) => {
  const y = 2000 + Number(anio2);
  const m = Number(mes);
  const d = Number(dia);
  const date = new Date(y, m - 1, d);
  return date.getFullYear() === y && date.getMonth() === m - 1 && date.getDate() === d;
};

const validarFiscal = (pais, valor, nombre, ejemplo) => {
  const texto = String(valor || '').trim();
  const fallo = 'El ' + nombre + ' no es válido. Ejemplo: ' + ejemplo + '.';
  if (pais === 'CL') {
    const plano = texto.toUpperCase().replace(/[^0-9K]/g, '');
    if (!/^[0-9]{7,8}[0-9K]$/.test(plano)) return fallo;
    const cuerpo = plano.slice(0, -1);
    let suma = 0;
    let factor = 2;
    for (let i = cuerpo.length - 1; i >= 0; i -= 1) {
      suma += Number(cuerpo[i]) * factor;
      factor = factor === 7 ? 2 : factor + 1;
    }
    const resto = 11 - (suma % 11);
    const esperado = resto === 11 ? '0' : (resto === 10 ? 'K' : String(resto));
    return esperado === plano.slice(-1) ? '' : fallo;
  }
  if (pais === 'CO') {
    const digitos = soloDigitos(texto);
    if (!/^[0-9]{9,16}$/.test(digitos)) return 'El NIT debe incluir el dígito de verificación. Ejemplo: ' + ejemplo + '.';
    const base = digitos.slice(0, -1);
    const pesos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
    let suma = 0;
    for (let i = 0; i < base.length; i += 1) suma += Number(base[base.length - 1 - i]) * pesos[i];
    const resto = suma % 11;
    const esperado = resto === 0 || resto === 1 ? resto : 11 - resto;
    return esperado === Number(digitos.slice(-1)) ? '' : fallo;
  }
  if (pais === 'MX') {
    const plano = texto.toUpperCase().replace(/[\s-]+/g, '');
    const chars = Array.from(plano);
    if (chars.length !== 12 && chars.length !== 13) return fallo;
    const letras = chars.length === 13 ? 4 : 3;
    const prefijo = chars.slice(0, letras).join('');
    const fecha = chars.slice(letras, letras + 6).join('');
    if (!/^[A-ZÑ&]+$/.test(prefijo) || !/^[0-9]{6}$/.test(fecha)) return fallo;
    if (!fechaValida(fecha.slice(0, 2), fecha.slice(2, 4), fecha.slice(4, 6))) return 'La fecha dentro del RFC no es válida. Ejemplo: ' + ejemplo + '.';
    const tabla = {};
    Array.from('0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ').forEach((char, i) => { tabla[char] = i; });
    const base = chars.slice(0, -1);
    if (chars.length === 12) base.unshift(' ');
    let suma = 0;
    let factor = 13;
    for (const char of base) {
      if (tabla[char] === undefined) return fallo;
      suma += tabla[char] * factor;
      factor -= 1;
    }
    const resto = suma % 11;
    const esperado = resto === 0 ? '0' : (resto === 1 ? 'A' : String(11 - resto));
    return chars[chars.length - 1] === esperado ? '' : fallo;
  }
  if (pais === 'SV') {
    const digitos = soloDigitos(texto);
    if (!/^[0-9]{14}$/.test(digitos) || !'019'.includes(digitos[0])) return fallo;
    if (!fechaValida(digitos.slice(8, 10), digitos.slice(6, 8), digitos.slice(4, 6))) {
      return 'La fecha dentro del NIT no es válida. Ejemplo: ' + ejemplo + '.';
    }
    const correlativo = digitos.slice(10, 13);
    let esperado = 0;
    if (correlativo <= '100') {
      const pesos = [14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2];
      let suma = 0;
      for (let i = 0; i < 13; i += 1) suma += Number(digitos[i]) * pesos[i];
      esperado = (suma % 11) % 10;
    } else {
      const pesos = [2, 7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
      let suma = 0;
      for (let i = 0; i < 13; i += 1) suma += Number(digitos[i]) * pesos[i];
      const resto = suma % 11;
      esperado = resto <= 1 ? 0 : 11 - resto;
    }
    return Number(digitos[13]) === esperado ? '' : fallo;
  }
  if (pais === 'EC') {
    const digitos = soloDigitos(texto);
    if (!/^[0-9]{13}$/.test(digitos)) return fallo;
    const provincia = Number(digitos.slice(0, 2));
    if ((provincia < 1 || provincia > 24) && provincia !== 30) return fallo;
    const tercero = Number(digitos[2]);
    const cedulaOk = (cedula) => {
      if (!/^[0-9]{10}$/.test(cedula)) return false;
      const prov = Number(cedula.slice(0, 2));
      if (((prov < 1 || prov > 24) && prov !== 30) || Number(cedula[2]) > 5) return false;
      const coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
      let suma = 0;
      for (let i = 0; i < 9; i += 1) {
        let valor = Number(cedula[i]) * coef[i];
        if (valor >= 10) valor -= 9;
        suma += valor;
      }
      return (10 - (suma % 10)) % 10 === Number(cedula[9]);
    };
    let ok = false;
    if (tercero < 6) ok = cedulaOk(digitos.slice(0, 10)) && Number(digitos.slice(10, 13)) >= 1;
    else if (tercero === 6) {
      const coef = [3, 2, 7, 6, 5, 4, 3, 2];
      let suma = 0;
      for (let i = 0; i < 8; i += 1) suma += Number(digitos[i]) * coef[i];
      const resto = suma % 11;
      const dv = resto === 0 ? 0 : 11 - resto;
      ok = dv === Number(digitos[8]) && Number(digitos.slice(9, 13)) >= 1;
    } else if (tercero === 9) {
      const coef = [4, 3, 2, 7, 6, 5, 4, 3, 2];
      let suma = 0;
      for (let i = 0; i < 9; i += 1) suma += Number(digitos[i]) * coef[i];
      const resto = suma % 11;
      const dv = resto === 0 ? 0 : 11 - resto;
      ok = dv === Number(digitos[9]) && Number(digitos.slice(10, 13)) >= 1;
    }
    return ok ? '' : fallo;
  }
  if (pais === 'PE') {
    const digitos = soloDigitos(texto);
    if (!/^(10|15|16|17|20)[0-9]{9}$/.test(digitos)) return fallo;
    const pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    let suma = 0;
    for (let i = 0; i < 10; i += 1) suma += Number(digitos[i]) * pesos[i];
    const resto = 11 - (suma % 11);
    const esperado = resto === 10 ? 0 : (resto === 11 ? 1 : resto);
    return Number(digitos[10]) === esperado ? '' : fallo;
  }
  return 'Elige un país de la lista.';
};

document.querySelectorAll('form').forEach((form) => {
  const email = form.querySelector('[data-email]');
  const fiscal = form.querySelector('[data-fiscal-input]');
  if (!email && !fiscal) return;
  const revisar = () => {
    if (email) {
      const value = email.value.trim();
      email.setCustomValidity(value !== '' && !emailValido(value) ? 'Escribe un correo válido.' : '');
    }
    if (fiscal) {
      const select = form.querySelector('[data-country]');
      const option = select?.selectedOptions[0];
      const nombre = option?.dataset.id || 'documento';
      const ejemplo = option?.dataset.ejemplo || '';
      const valor = fiscal.value.trim();
      if (!valor) fiscal.setCustomValidity(fiscal.required ? 'Escribe el ' + nombre + '. Ejemplo: ' + ejemplo + '.' : '');
      else fiscal.setCustomValidity(validarFiscal(select?.value || '', valor, nombre, ejemplo));
    }
  };
  email?.addEventListener('input', revisar);
  fiscal?.addEventListener('input', revisar);
  form.addEventListener('submit', (event) => {
    revisar();
    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
    }
  });
});

const form = document.querySelector('[data-expense-form]');
if (form) {
  const parseMoney = (value) => {
    let raw = String(value || '').replace(/\s/g, '');
    if (/,\d{1,2}$/.test(raw)) raw = raw.replace(/\./g, '').replace(',', '.');
    else raw = raw.replace(/,/g, '');
    const number = Number(raw);
    return Number.isFinite(number) ? number : 0;
  };
  const money = (value) => {
    const currency = form.dataset.currency || '';
    const decimals = currency === 'CLP' || currency === 'COP' ? 0 : 2;
    return currency + ' ' + parseMoney(value).toLocaleString('es-CO', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
  };
  const setText = (id, value) => {
    const node = document.getElementById(id);
    if (node) node.textContent = value;
  };
  const known = JSON.parse(document.getElementById('expense-index')?.textContent || '[]');
  const update = () => {
    const concepto = form.concepto.value.trim() || 'Nuevo gasto';
    const proveedor = form.proveedor.value.trim() || 'Sin proveedor';
    const categoria = form.category_id.selectedOptions[0]?.text || 'Sin categoría';
    const fecha = form.fecha.value ? form.fecha.value.split('-').reverse().join('/') : 'Sin fecha';
    const tipo = form.tipo_doc.value || 'Sin tipo';
    const nota = form.nota.value.trim();
    setText('pvConcepto', concepto);
    setText('pvProveedor', proveedor);
    setText('pvMonto', money(form.monto.value));
    setText('pvMeta', fecha + ' · ' + categoria);
    setText('pvDoc', [tipo, form.num_doc.value, form.id_fiscal.value].filter(Boolean).join(' · ') || 'Datos fiscales pendientes');
    setText('pvNota', nota || 'Sin nota para el aprobador.');

    const tope = Number(form.category_id.selectedOptions[0]?.dataset.tope || 0);
    const monto = parseMoney(form.monto.value);
    const insights = [];
    const push = (text, kind) => insights.push({ text, kind });
    if (monto > 0 && tope > 0 && monto > tope) {
      push('Supera el tope de la categoría (' + money(tope) + ').', 'bad');
    } else if (monto > 0) {
      push('Dentro de la política de la categoría.', 'good');
    }
    const proveedorKey = proveedor.toLowerCase();
    const doc = (form.num_doc.value || '').trim().toLowerCase();
    const hit = known.find((item) => {
      if (String(item.id) === String(form.dataset.id || '')) return false;
      const sameDoc = doc !== '' && item.num_doc.toLowerCase() === doc;
      const sameBill = item.proveedor.toLowerCase() === proveedorKey && Number(item.monto) === monto && item.fecha === form.fecha.value;
      return sameDoc || sameBill;
    });
    if (hit) push('Posible duplicado de “' + hit.concepto + '”.', 'bad');
    else if (proveedorKey && monto > 0) push('No encontramos un duplicado reciente.', 'good');
    if (!form.id_fiscal.value.trim() || !form.num_doc.value.trim() || !form.tipo_doc.value) {
      push('Faltan datos fiscales para ' + (form.dataset.autoridad || 'la autoridad') + '.', 'bad');
    } else {
      push('Listo para cruce fiscal con ' + (form.dataset.autoridad || 'la autoridad') + '.', 'good');
    }
    const hasFile = form.dataset.hasFile === '1' || (form.foto?.files?.length > 0);
    if (!hasFile && monto > 0) {
      push('Aún no hay comprobante adjunto.', 'bad');
    } else if (hasFile) {
      push('Comprobante listo para la revisión.', 'good');
    }
    const box = document.getElementById('pvInsights');
    box.innerHTML = insights.map((item) => '<div class="insight ' + item.kind + '">' + item.text.replace(/[&<>]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[ch])) + '</div>').join('');
  };
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  update();

  const drop = document.querySelector('[data-drop]');
  const file = form.querySelector('input[type="file"]');
  const photo = document.getElementById('pvPhoto');
  const showFile = () => {
    const selected = file.files?.[0];
    if (!selected || !photo) return;
    if (selected.type.startsWith('image/')) {
      photo.src = URL.createObjectURL(selected);
      photo.hidden = false;
      photo.dataset.existing = '1';
    }
    update();
  };
  file?.addEventListener('change', showFile);
  ['dragover', 'dragenter'].forEach((eventName) => drop?.addEventListener(eventName, (event) => {
    event.preventDefault();
    drop.classList.add('is-over');
  }));
  ['dragleave', 'drop'].forEach((eventName) => drop?.addEventListener(eventName, (event) => {
    event.preventDefault();
    drop.classList.remove('is-over');
  }));
  drop?.addEventListener('drop', (event) => {
    if (!file || !event.dataTransfer.files.length) return;
    file.files = event.dataTransfer.files;
    showFile();
  });
}

const reportForm = document.querySelector('[data-report-form]');
if (reportForm) {
  const total = document.getElementById('reportTotal');
  const currency = reportForm.dataset.currency || '';
  const refresh = () => {
    let sum = 0;
    reportForm.querySelectorAll('input[type="checkbox"]:checked').forEach((box) => {
      sum += Number(box.dataset.monto || 0);
    });
    const decimals = currency === 'CLP' || currency === 'COP' ? 0 : 2;
    if (total) {
      total.textContent = currency + ' ' + sum.toLocaleString('es-CO', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }
  };
  reportForm.addEventListener('change', refresh);
  refresh();
}
