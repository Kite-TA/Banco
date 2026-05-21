document.addEventListener('DOMContentLoaded', () => {

    cargarMovimientos();

});

// ════════════════════════════════════════
// VARIABLES
// ════════════════════════════════════════

let transaccionesOriginales = [];

// ════════════════════════════════════════
// ETIQUETAS
// ════════════════════════════════════════

const ETIQUETAS = {

    deposito: {
        label: 'Depósito',
        clase: 'badge-deposito'
    },

    retiro: {
        label: 'Retiro',
        clase: 'badge-retiro'
    },

    transferencia_enviada: {
        label: 'Transf. enviada',
        clase: 'badge-transferencia_enviada'
    },

    transferencia_recibida: {
        label: 'Transf. recibida',
        clase: 'badge-transferencia_recibida'
    }

};

// ════════════════════════════════════════
// FORMATEAR DINERO
// ════════════════════════════════════════

function formatMoney(valor) {

    return Number(valor).toLocaleString(
        'es-MX',
        {
            style: 'currency',
            currency: 'MXN'
        }
    );

}

// ════════════════════════════════════════
// FORMATEAR FECHA
// ════════════════════════════════════════

function formatFecha(fechaStr) {

    const d = new Date(fechaStr);

    if (isNaN(d.getTime())) {

        return 'Fecha inválida';
    }

    return d.toLocaleDateString(
        'es-MX',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }
    )
    + ' '
    +
    d.toLocaleTimeString(
        'es-MX',
        {
            hour: '2-digit',
            minute: '2-digit'
        }
    );

}

// ════════════════════════════════════════
// RENDER TABLA
// ════════════════════════════════════════

function renderizarMovimientos(txs) {

    const tbody =
        document.getElementById(
            'tbody-movimientos'
        );

    if (!tbody) return;

    if (!txs || txs.length === 0) {

        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    No hay movimientos
                </td>
            </tr>
        `;

        return;
    }

    tbody.innerHTML = txs.map(tx => {

        const tipoClave =
            (tx.tipo || '').toLowerCase();

        const etiq =
            ETIQUETAS[tipoClave];

        const esSalida =
            tipoClave === 'retiro'
            ||
            tipoClave === 'transferencia_enviada';

        const signo =
            esSalida ? '-' : '+';

        const color =
            esSalida
            ? 'red'
            : 'green';

        return `
            <tr>

                <td>
                    ${formatFecha(tx.fecha)}
                </td>

                <td>
                    <span class="${etiq.clase}">
                        ${etiq.label}
                    </span>
                </td>

                <td>
                    ${tx.descripcion || '—'}
                </td>

                <td>
                    ${tx.cuenta_relacionada || '—'}
                </td>

                <td style="color:${color}; font-weight:bold;">
                    ${signo}
                    ${formatMoney(tx.monto)}
                </td>

                <td>
                    ${formatMoney(tx.saldo_despues || 0)}
                </td>

            </tr>
        `;

    }).join('');

}

// ════════════════════════════════════════
// FILTROS
// ════════════════════════════════════════

function aplicarFiltros() {

    const fechaDesde =
        document.getElementById(
            'filtro-fecha-desde'
        ).value;

    const fechaHasta =
        document.getElementById(
            'filtro-fecha-hasta'
        ).value;

    const tipoFiltro =
        document.getElementById(
            'filtro-tipo'
        ).value;

    let txsFiltradas =
        transaccionesOriginales;

    // FILTRO TIPO

    if (tipoFiltro) {

        txsFiltradas =
            txsFiltradas.filter(
                tx => tx.tipo === tipoFiltro
            );
    }

    // FILTRO FECHAS

    if (fechaDesde || fechaHasta) {

        txsFiltradas =
            txsFiltradas.filter(tx => {

                const fechaTx =
                    new Date(tx.fecha)
                    .toISOString()
                    .split('T')[0];

                if (
                    fechaDesde
                    &&
                    fechaTx < fechaDesde
                ) return false;

                if (
                    fechaHasta
                    &&
                    fechaTx > fechaHasta
                ) return false;

                return true;

            });
    }

    document.getElementById(
        'total-movimientos'
    ).textContent =
        txsFiltradas.length;

    renderizarMovimientos(
        txsFiltradas
    );

}

// ════════════════════════════════════════
// LIMPIAR FILTROS
// ════════════════════════════════════════

function limpiarFiltros() {

    document.getElementById(
        'filtro-fecha-desde'
    ).value = '';

    document.getElementById(
        'filtro-fecha-hasta'
    ).value = '';

    document.getElementById(
        'filtro-tipo'
    ).value = '';

    renderizarMovimientos(
        transaccionesOriginales
    );

}

// ════════════════════════════════════════
// CARGAR MOVIMIENTOS
// ════════════════════════════════════════

async function cargarMovimientos() {

    try {

        const cuentaId =
            localStorage.getItem('cuentaId')
            ||
            localStorage.getItem('cuenta_id');

        if (!cuentaId) {

            throw new Error(
                'No se encontró cuenta'
            );
        }

        const resp = await fetch(
            `../../backend_em/obtener_movimientos.php?cuentaId=${cuentaId}`
        );

        const datos =
            await resp.json();

        console.log(datos);

        transaccionesOriginales =
            datos.transacciones || [];

        document.getElementById(
            'total-movimientos'
        ).textContent =
            transaccionesOriginales.length;

        renderizarMovimientos(
            transaccionesOriginales
        );

    } catch (error) {

        console.error(error);

    }
}