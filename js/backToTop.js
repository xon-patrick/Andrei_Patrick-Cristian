document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('backToTop');
  if (!btn) return;
  const showAfter = 300; // px scrolled

  function update() {
    if (window.pageYOffset > showAfter) btn.classList.add('visible');
    else btn.classList.remove('visible');
  }
  update();
  window.addEventListener('scroll', update, { passive: true });

  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    btn.blur();
  });
});