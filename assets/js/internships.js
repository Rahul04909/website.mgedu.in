/**
 * MG Education & Social Development Organization
 * Internships Carousel JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  initInternshipsCarousel();
});

function initInternshipsCarousel() {
  const carouselWrapper = document.querySelector('.mg-internships-carousel-wrapper');
  if (!carouselWrapper) return;

  const track = carouselWrapper.querySelector('.mg-internships-track');
  const prevBtn = carouselWrapper.querySelector('.mg-internship-arrow.prev');
  const nextBtn = carouselWrapper.querySelector('.mg-internship-arrow.next');

  if (!track || !prevBtn || !nextBtn) return;

  function updateArrowStates() {
    const scrollLeft = track.scrollLeft;
    const maxScroll = track.scrollWidth - track.clientWidth - 5;

    prevBtn.disabled = scrollLeft <= 5;
    nextBtn.disabled = scrollLeft >= maxScroll;
  }

  function getScrollStep() {
    const card = track.querySelector('.mg-internship-card');
    if (card) {
      return card.offsetWidth + 22;
    }
    return 310;
  }

  prevBtn.addEventListener('click', (e) => {
    e.preventDefault();
    track.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
  });

  nextBtn.addEventListener('click', (e) => {
    e.preventDefault();
    track.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
  });

  track.addEventListener('scroll', updateArrowStates, { passive: true });
  window.addEventListener('resize', updateArrowStates, { passive: true });

  // Initial check
  updateArrowStates();

  // Apply button micro-interaction
  const applyButtons = track.querySelectorAll('.mg-btn-apply');
  applyButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Applied ✓';
      btn.style.backgroundColor = '#10b981';
      btn.style.borderColor = '#10b981';
      btn.style.color = '#ffffff';

      setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.removeAttribute('style');
      }, 2000);
    });
  });
}

