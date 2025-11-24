document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('activitateInput');
    const button = document.getElementById('adaugaBtn');
    const list = document.getElementById('activitati');
    
    const luni = [
        'Ianuarie','Februarie','Martie',
        'Aprilie','Mai','Iunie',
        'Iulie','August','Septembrie',
        'Octombrie','Noiembrie','Decembrie'
    ];
    
    button.addEventListener('click', function() {
        const text = input.value.trim();
        if (!text) return;

        const d = new Date();
        const zi = d.getDate();
        const luna = luni[d.getMonth()];
        const an = d.getFullYear();

        const li = document.createElement('li');
        li.textContent = text + " - Adaugat pe " + zi + " " + luna + " " + an;
        list.appendChild(li);
        input.value = '';
    });


});