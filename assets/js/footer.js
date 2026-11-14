/**
 * MG Education & Social Development Organization
 * Footer JavaScript (Back to Top & Interactions)
 */

document.addEventListener('DOMContentLoaded', () => {
  initBackToTop();
  initNewsletterForm();
});

function initBackToTop() {
  const backToTopBtn = document.getElementById('mgBackToTop');
  if (!backToTopBtn) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 350) {
      backToTopBtn.classList.add('show');
    } else {
      backToTopBtn.classList.remove('show');
    }
  }, { passive: true });

  backToTopBtn.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
}

function initNewsletterForm() {
  const form = document.getElementById('mgFooterNewsForm');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = form.querySelector('.mg-footer-news-input');
    const btn = form.querySelector('.mg-footer-news-btn');

    if (input && input.value.trim() !== '') {
      const originalText = btn.textContent;
      btn.textContent = 'Subscribed ✓';
      btn.style.backgroundColor = '#10b981';
      input.value = '';

      setTimeout(() => {
        btn.textContent = originalText;
        btn.removeAttribute('style');
      }, 2500);
    }
  });
}

