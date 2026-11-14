/**
 * MG Education & Social Development Organization
 * Header Functionality (Accessibility, Mobile Menu, Search Modal, Theme)
 */

document.addEventListener('DOMContentLoaded', () => {
  initFontResizer();
  initThemeSwitcher();
  initMobileMenu();
  initSearchModal();
  initLanguageSelector();
});

/**
 * 1. Font Size Resizer (A-, A, A+)
 */
function initFontResizer() {
  const btnDecrease = document.getElementById('mgFontDecrease');
  const btnReset = document.getElementById('mgFontReset');
  const btnIncrease = document.getElementById('mgFontIncrease');
  const fontBtns = [btnDecrease, btnReset, btnIncrease];

  let currentScale = parseInt(localStorage.getItem('mg_font_scale')) || 100;

  function applyFontScale(scale, activeBtn) {
    document.documentElement.style.setProperty('--mg-font-scale', `${scale}%`);
    localStorage.setItem('mg_font_scale', scale);
    fontBtns.forEach(btn => btn && btn.classList.remove('active'));
    if (activeBtn) activeBtn.classList.add('active');
  }

  // Restore saved scale
  if (currentScale === 90 && btnDecrease) btnDecrease.classList.add('active');
  else if (currentScale === 110 && btnIncrease) btnIncrease.classList.add('active');
  else if (btnReset) btnReset.classList.add('active');
  document.documentElement.style.setProperty('--mg-font-scale', `${currentScale}%`);

  if (btnDecrease) {
    btnDecrease.addEventListener('click', (e) => {
      e.preventDefault();
      applyFontScale(90, btnDecrease);
    });
  }

  if (btnReset) {
    btnReset.addEventListener('click', (e) => {
      e.preventDefault();
      applyFontScale(100, btnReset);
    });
  }

  if (btnIncrease) {
    btnIncrease.addEventListener('click', (e) => {
      e.preventDefault();
      applyFontScale(110, btnIncrease);
    });
  }
}

/**
 * 2. Theme / Contrast Switcher (Normal White / High-Contrast Dark)
 */
function initThemeSwitcher() {
  const circleWhite = document.getElementById('mgThemeDefault');
  const circleBlack = document.getElementById('mgThemeContrast');

  const savedTheme = localStorage.getItem('mg_theme_mode');
  if (savedTheme === 'high-contrast') {
    document.body.classList.add('mg-high-contrast');
  }

  if (circleWhite) {
    circleWhite.addEventListener('click', () => {
      document.body.classList.remove('mg-high-contrast');
      localStorage.setItem('mg_theme_mode', 'default');
    });
  }

  if (circleBlack) {
    circleBlack.addEventListener('click', () => {
      document.body.classList.add('mg-high-contrast');
      localStorage.setItem('mg_theme_mode', 'high-contrast');
    });
  }
}

/**
 * 3. Mobile Navigation & Dropdowns
 */
function initMobileMenu() {
  const toggleBtn = document.getElementById('mgNavToggle');
  const navMenu = document.getElementById('mgNavMenu');

  if (toggleBtn && navMenu) {
    toggleBtn.addEventListener('click', () => {
      navMenu.classList.toggle('show');
      const isExpanded = navMenu.classList.contains('show');
      toggleBtn.setAttribute('aria-expanded', isExpanded);
      const icon = toggleBtn.querySelector('i');
      if (icon) {
        if (isExpanded) {
          icon.classList.remove('fa-bars');
          icon.classList.add('fa-xmark');
        } else {
          icon.classList.remove('fa-xmark');
          icon.classList.add('fa-bars');
        }
      }
    });

    // Mobile Dropdown click handling
    const dropdownItems = navMenu.querySelectorAll('.mg-has-dropdown');
    dropdownItems.forEach((item) => {
      const link = item.querySelector('.mg-nav-link');
      if (link) {
        link.addEventListener('click', (e) => {
          if (window.innerWidth <= 991) {
            e.preventDefault();
            item.classList.toggle('dropdown-open');
          }
        });
      }
    });
  }
}

/**
 * 4. Search Modal Overlay
 */
function initSearchModal() {
  const openBtn = document.getElementById('mgSearchOpen');
  const closeBtn = document.getElementById('mgSearchClose');
  const overlay = document.getElementById('mgSearchOverlay');
  const searchInput = document.getElementById('mgSearchInput');

  if (openBtn && overlay) {
    openBtn.addEventListener('click', (e) => {
      e.preventDefault();
      overlay.classList.add('active');
      setTimeout(() => {
        if (searchInput) searchInput.focus();
      }, 100);
    });
  }

  if (closeBtn && overlay) {
    closeBtn.addEventListener('click', () => {
      overlay.classList.remove('active');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        overlay.classList.remove('active');
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
      overlay.classList.remove('active');
    }
  });
}

/**
 * 5. Bilingual Language Selector
 */
function initLanguageSelector() {
  const langEn = document.getElementById('mgLangEn');
  const langHi = document.getElementById('mgLangHi');

  if (langEn && langHi) {
    langEn.addEventListener('click', (e) => {
      e.preventDefault();
      langEn.classList.add('active');
      langHi.classList.remove('active');
      localStorage.setItem('mg_lang', 'en');
    });

    langHi.addEventListener('click', (e) => {
      e.preventDefault();
      langHi.classList.add('active');
      langEn.classList.remove('active');
      localStorage.setItem('mg_lang', 'hi');
    });
  }
}

