// ===== PAGE NAVIGATION SYSTEM =====
let currentPage = 0;
const totalPages = 3;
let isAnimating = false;

function goToPage(pageIndex, direction = null) {
  if (pageIndex === currentPage || isAnimating) return;
  if (pageIndex < 0 || pageIndex >= totalPages) return;

  isAnimating = true;
  const currentEl = document.getElementById(`page${currentPage}`);
  const nextEl = document.getElementById(`page${pageIndex}`);

  // Determine direction
  if (direction === null) {
    direction = pageIndex > currentPage ? 'left' : 'right';
  }

  // Remove active from current
  if (currentEl) {
    currentEl.classList.remove('active', 'slide-in-left', 'slide-in-right');
  }

  // Set animation class based on direction
  const animClass = direction === 'left' ? 'slide-in-left' : 'slide-in-right';

  // Activate next page with animation
  if (nextEl) {
    nextEl.classList.add('active', animClass);
  }

  // Update current page
  currentPage = pageIndex;

  // Update indicators
  updateIndicators();

  // Update arrow states
  updateArrows();

  // Scroll to top smoothly
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Re-initialize Lucide icons for new page
  setTimeout(() => {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }, 100);

  // Allow animation again after transition
  setTimeout(() => {
    isAnimating = false;
    // Trigger scroll animations on new page
    triggerScrollAnimations();
  }, 700);

  // Prevent default on footer links
  if (window.event && window.event.preventDefault) {
    window.event.preventDefault();
  }
}

function nextPage() {
  if (currentPage < totalPages - 1) {
    goToPage(currentPage + 1, 'left');
  }
}

function prevPage() {
  if (currentPage > 0) {
    goToPage(currentPage - 1, 'right');
  }
}

function updateIndicators() {
  const dots = document.querySelectorAll('.indicator-dot');
  dots.forEach((dot, index) => {
    dot.classList.toggle('active', index === currentPage);
  });
}

function updateArrows() {
  const leftBtn = document.getElementById('navLeft');
  const rightBtn = document.getElementById('navRight');
  if (leftBtn && rightBtn) {
    leftBtn.disabled = currentPage === 0;
    rightBtn.disabled = currentPage === totalPages - 1;
  }
}

// ===== KEYBOARD NAVIGATION =====
document.addEventListener('keydown', (e) => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
    e.preventDefault();
    nextPage();
  } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
    e.preventDefault();
    prevPage();
  }
});

// ===== TOUCH/SWIPE NAVIGATION =====
let touchStartX = 0;
let touchEndX = 0;
let touchStartY = 0;
let touchEndY = 0;

document.addEventListener('touchstart', (e) => {
  touchStartX = e.changedTouches[0].screenX;
  touchStartY = e.changedTouches[0].screenY;
}, { passive: true });

document.addEventListener('touchend', (e) => {
  touchEndX = e.changedTouches[0].screenX;
  touchEndY = e.changedTouches[0].screenY;
  handleSwipe();
}, { passive: true });

function handleSwipe() {
  const diffX = touchStartX - touchEndX;
  const diffY = touchStartY - touchEndY;
  // Only handle horizontal swipes (not vertical scrolling)
  if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 80) {
    if (diffX > 0) {
      // Swipe left -> next page
      nextPage();
    } else {
      // Swipe right -> previous page
      prevPage();
    }
  }
}

// ===== SCROLL ANIMATIONS =====
function triggerScrollAnimations() {
  if (typeof IntersectionObserver === 'undefined') return;
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  });

  // Observe all animatable elements in the active page
  const activePage = document.getElementById(`page${currentPage}`);
  if (activePage) {
    const elements = activePage.querySelectorAll('.feature-card, .highlight-row, .cta-card, .section-label, .section-title');
    elements.forEach((el, index) => {
      el.classList.add('animate-on-scroll');
      el.style.transitionDelay = `${index * 0.08}s`;
      observer.observe(el);
    });
  }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Set first page as active
  const firstPage = document.getElementById('page0');
  if (firstPage) {
    firstPage.classList.add('active');
  }

  // Update navigation state
  updateIndicators();
  updateArrows();

  // Trigger initial scroll animations
  setTimeout(() => {
    triggerScrollAnimations();
  }, 300);
});

let wheelDebounce = false;
document.addEventListener('wheel', (e) => {
  // Only trigger if user is near the top of the page
  if (window.scrollY > 200) return;
  if (wheelDebounce) return;
  
  if (Math.abs(e.deltaY) < 50) return;
  
  wheelDebounce = true;
  if (e.deltaY > 50) {
    nextPage();
  } else if (e.deltaY < -50) {
    prevPage();
  }
  setTimeout(() => {
    wheelDebounce = false;
  }, 1200);
}, { passive: true });
