(function () {
  const body = document.body;
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const MOBILE_BREAKPOINT = 1100;
  const SIDEBAR_KEY = 'posg_sidebar_collapsed';

  function isMobile() {
    return window.matchMedia('(max-width: 1100px)').matches;
  }

  function applyDesktopCollapse() {
    const collapsed = localStorage.getItem(SIDEBAR_KEY) === '1';
    body.classList.toggle('sidebar-collapsed', collapsed && !isMobile());
  }

  function toggleSidebar() {
    if (!sidebar) return;

    if (isMobile()) {
      sidebar.classList.toggle('show');
      return;
    }

    const willCollapse = !body.classList.contains('sidebar-collapsed');
    body.classList.toggle('sidebar-collapsed', willCollapse);
    localStorage.setItem(SIDEBAR_KEY, willCollapse ? '1' : '0');
  }

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', toggleSidebar);
  }

  document.addEventListener('click', (e) => {
    if (!isMobile() || !sidebar) return;
    if (!sidebar.classList.contains('show')) return;
    const insideSidebar = e.target.closest('#sidebar');
    const isToggle = e.target.closest('#sidebarToggle');
    if (!insideSidebar && !isToggle) {
      sidebar.classList.remove('show');
    }
  });

  window.addEventListener('resize', () => {
    if (!sidebar) return;
    if (!isMobile()) {
      sidebar.classList.remove('show');
    }
    applyDesktopCollapse();
  });

  applyDesktopCollapse();

  document.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (body.classList.contains('sidebar-collapsed') && !isMobile()) {
        toggleSidebar();
      }
      const section = btn.closest('[data-menu-section]');
      if (!section) return;
      section.classList.toggle('open');
      const expanded = section.classList.contains('open');
      btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      const msg = form.getAttribute('data-confirm') || 'هل أنت متأكد؟';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  const salesChart = document.getElementById('salesChart');
  if (salesChart && window.Chart) {
    try {
      const payload = JSON.parse(salesChart.dataset.chart || '{}');
      new Chart(salesChart, {
        type: 'line',
        data: {
          labels: payload.labels || [],
          datasets: [{
            label: 'المبيعات',
            data: payload.values || [],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,.18)',
            fill: true,
            tension: .3
          }]
        },
        options: {
          plugins: {legend: {display: false}},
          scales: {
            y: {beginAtZero: true}
          }
        }
      });
    } catch (e) {
      console.error(e);
    }
  }

  const headerDateTime = document.getElementById('headerDateTime');
  if (headerDateTime) {
    const dateFmt = new Intl.DateTimeFormat('ar-EG', {
      weekday: 'short',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
    const timeFmt = new Intl.DateTimeFormat('ar-EG', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    });

    const renderNow = () => {
      const now = new Date();
      headerDateTime.textContent = `${dateFmt.format(now)} - ${timeFmt.format(now)}`;
    };

    renderNow();
    setInterval(renderNow, 1000);
  }
})();
