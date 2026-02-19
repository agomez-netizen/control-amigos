// public/js/app.js
document.addEventListener('DOMContentLoaded', () => {
  // ============================
  // Sidebar (desktop) + persistencia
  // ============================
  const shell = document.getElementById('appShell');
  const btnDesktop = document.getElementById('toggleSidebar');
  const storageKey = 'sidebar-collapsed';

  if (shell && btnDesktop) {
    // Restaurar estado guardado
    if (localStorage.getItem(storageKey) === '1') {
      shell.classList.add('collapsed');
    }

    // Toggle desktop
    btnDesktop.addEventListener('click', () => {
      shell.classList.toggle('collapsed');
      localStorage.setItem(storageKey, shell.classList.contains('collapsed') ? '1' : '0');
    });
  }

  // ============================
  // Sidebar (mobile offcanvas)
  // Cerrar cuando se hace click en un link del menú
  // ============================
  document.addEventListener('click', (e) => {
    const insideMobileMenu = e.target.closest('#sidebarMobile');
    if (!insideMobileMenu) return;

    // Si NO fue un link, no hacemos nada
    const link = e.target.closest('a');
    if (!link) return;

    // Si es un dropdown toggle, no cerrar (para que despliegue)
    if (link.classList.contains('dropdown-toggle')) return;
    if (link.getAttribute('data-bs-toggle') === 'dropdown') return;

    // Cerrar offcanvas si Bootstrap está disponible
    const el = document.getElementById('sidebarMobile');
    if (!el) return;
    if (!window.bootstrap || !bootstrap.Offcanvas) return;

    const offcanvas = bootstrap.Offcanvas.getInstance(el);
    if (offcanvas) offcanvas.hide();
  });

  // ============================
  // Tooltips (opcional)
  // ============================
  if (window.bootstrap?.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }
});
