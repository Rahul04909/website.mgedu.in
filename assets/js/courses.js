/**
 * MG Education & Social Development Organization
 * Trending Courses Carousel JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  initCoursesCarousel();
});

function initCoursesCarousel() {
  const carouselWrapper = document.querySelector('.mg-carousel-wrapper');
  if (!carouselWrapper) return;

  const track = carouselWrapper.querySelector('.mg-courses-track');
  const prevBtn = carouselWrapper.querySelector('.mg-course-arrow.prev');
  const nextBtn = carouselWrapper.querySelector('.mg-course-arrow.next');

  if (!track || !prevBtn || !nextBtn) return;

  function updateArrowStates() {
    const scrollLeft = track.scrollLeft;
    const maxScroll = track.scrollWidth - track.clientWidth - 5;

    prevBtn.disabled = scrollLeft <= 5;
    nextBtn.disabled = scrollLeft >= maxScroll;
  }

  // Scroll by card width + gap
  function getScrollStep() {
    const card = track.querySelector('.mg-course-card');
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

  // Track scroll event to toggle button active/disabled states
  track.addEventListener('scroll', updateArrowStates, { passive: true });
  window.addEventListener('resize', updateArrowStates, { passive: true });

  // Initial check
  updateArrowStates();

  // Add to cart button micro-interaction
  const cartButtons = track.querySelectorAll('.mg-btn-add-cart');
  cartButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Added';
      btn.style.backgroundColor = '#10b981';
      btn.style.borderColor = '#10b981';
      btn.style.color = '#ffffff';

      setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.removeAttribute('style');
      }, 1500);
    });
  });
}
