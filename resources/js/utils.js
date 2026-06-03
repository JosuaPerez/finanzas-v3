export const formatCurrency = (value) => {
    // Si el valor está vacío o es cero, devolvemos un formato limpio
    if (value === null || value === undefined || isNaN(value)) {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: 'DOP',
        }).format(0);
    }

    // Formatear el número real
    return new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: 'DOP',
    }).format(value);
};