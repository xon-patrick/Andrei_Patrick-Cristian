document.addEventListener('DOMContentLoaded', async () => {

  const placeholder = document.getElementById('nav-placeholder');
  if (!placeholder) return;

  try {
    const resp = await fetch('navbar.html');
    if (!resp.ok) throw new Error('navbar.html fetch failed: ' + resp.status);
    placeholder.innerHTML = await resp.text();


  } catch (err) {
    console.error('Failed to load navbar.html', err);
  }

  let backBtn = document.getElementById('backToTop');
  if (!backBtn) {
    backBtn = document.createElement('button');
    backBtn.id = 'backToTop';
    backBtn.type = 'button';
    backBtn.setAttribute('aria-label', 'Back to top');
    backBtn.title = 'Back to top';
    backBtn.textContent = '↑';
    document.body.appendChild(backBtn);
  }

  const showAfter = 300;
  function update() {
    if (window.pageYOffset > showAfter) backBtn.classList.add('visible');
    else backBtn.classList.remove('visible');
  }
  update();
  window.addEventListener('scroll', update, { passive: true });

  backBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    backBtn.blur();
  });
});

const navSearchForm = document.getElementById("navSearchForm");
const navSearchInput = document.getElementById("navSearchInput");

navSearchForm.addEventListener("submit", function(e) {
  e.preventDefault();
  const query = navSearchInput.value.trim();
  if (!query) return;
  window.location.href = `discover.html?query=${encodeURIComponent(query)}`;
});
