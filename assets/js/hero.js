/**
 * MG Education & Social Development Organization
 * Hero Slider JavaScript
 * Pure Image Slider with Autoplay, Touch Swipe, and Pause-on-Hover
 */

document.addEventListener('DOMContentLoaded', () => {
  initHeroSlider();
});

function initHeroSlider() {
  const slider = document.querySelector('.mg-hero-slider');
  if (!slider) return;

  const slides = slider.querySelectorAll('.mg-hero-slide');
  const dots = slider.querySelectorAll('.mg-hero-dot');
  const prevBtn = slider.querySelector('.mg-hero-arrow.prev');
  const nextBtn = slider.querySelector('.mg-hero-arrow.next');

  if (slides.length <= 1) return;

  let currentIndex = 0;
  let autoSlideInterval = null;
  const slideDuration = 5000; // 5 seconds per slide

  // Go to specific slide
  function goToSlide(index) {
    slides[currentIndex].classList.remove('active');
    if (dots[currentIndex]) dots[currentIndex].classList.remove('active');

    currentIndex = (index + slides.length) % slides.length;

    slides[currentIndex].classList.add('active');
    if (dots[currentIndex]) dots[currentIndex].classList.add('active');
  }

  function nextSlide() {
    goToSlide(currentIndex + 1);
  }

  function prevSlide() {
    goToSlide(currentIndex - 1);
  }

  // Autoplay
  function startAutoplay() {
    if (!autoSlideInterval) {
      autoSlideInterval = setInterval(nextSlide, slideDuration);
    }
  }

  function stopAutoplay() {
    if (autoSlideInterval) {
      clearInterval(autoSlideInterval);
      autoSlideInterval = null;
    }
  }

  // Event Listeners for Prev/Next
  if (nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      nextSlide();
      stopAutoplay();
      startAutoplay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', (e) => {
      e.preventDefault();
      prevSlide();
      stopAutoplay();
      startAutoplay();
    });
  }

  // Dots click
  dots.forEach((dot, idx) => {
    dot.addEventListener('click', (e) => {
      e.preventDefault();
      goToSlide(idx);
      stopAutoplay();
      startAutoplay();
    });
  });

  // Pause on hover
  slider.addEventListener('mouseenter', stopAutoplay);
  slider.addEventListener('mouseleave', startAutoplay);

  // Keyboard navigation
  slider.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') {
      nextSlide();
      stopAutoplay();
      startAutoplay();
    } else if (e.key === 'ArrowLeft') {
      prevSlide();
      stopAutoplay();
      startAutoplay();
    }
  });

  // Mobile Touch Swipe Handling
  let touchStartX = 0;
  let touchEndX = 0;
  const swipeThreshold = 40; // minimum px for swipe

  slider.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    stopAutoplay();
  }, { passive: true });

  slider.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
    startAutoplay();
  }, { passive: true });

  function handleSwipe() {
    const diff = touchEndX - touchStartX;
    if (Math.abs(diff) > swipeThreshold) {
      if (diff < 0) {
        // Swiped Left -> Next
        nextSlide();
      } else {
        // Swiped Right -> Prev
        prevSlide();
      }
    }
  }

  // Start initial autoplay
  startAutoplay();
}

