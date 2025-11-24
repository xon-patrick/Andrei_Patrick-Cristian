document.addEventListener('DOMContentLoaded', function () {
    const detalii = document.getElementById('detalii');
    const btn = document.getElementById('btnDetalii');
    const dataSpan = document.getElementById('dataProdus');

    detalii.classList.add('ascuns');
    btn.setAttribute('aria-expanded', 'false');

    const luni = [
        'Ianuarie','Februarie','Martie',
        'Aprilie','Mai','Iunie',
        'Iulie','August','Septembrie',
        'Octombrie','Noiembrie','Decembrie'];
        
    const now = new Date();
    dataSpan.textContent = now.getDate() + " " + luni[now.getMonth()] + " " + now.getFullYear();

    btn.addEventListener('click', function () {
        detalii.classList.toggle('ascuns');
        const ascuns = detalii.classList.contains('ascuns');
        btn.textContent = ascuns ? 'Afiseaza detalii' : 'Ascunde detalii';
        btn.setAttribute('aria-expanded', String(!ascuns));
    });
});