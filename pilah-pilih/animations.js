// ===== SMOOTH SCROLL ANIMATIONS =====
// Modern motion design with Intersection Observer for scroll-triggered animations

class SmoothAnimations {
  constructor() {
    this.observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };
    this.init();
  }

  init() {
    this.setupScrollAnimations();
    this.setupHoverEffects();
    this.setupNavbarScroll();
    this.setupParallax();
  }

  // ===== SCROLL-TRIGGERED ANIMATIONS =====
  setupScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          // Add staggered animation delay for multiple elements
          const delay = index * 100;
          entry.target.style.animationDelay = `${delay}ms`;
          entry.target.classList.add('animate-fade-up');
          observer.unobserve(entry.target);
        }
      });
    }, this.observerOptions);

    // Observe all elements with animation data attributes
    const animElements = document.querySelectorAll('[data-animate]');
    animElements.forEach(el => observer.observe(el));

    // Also observe feature boxes, cards, and sections
    const cards = document.querySelectorAll('.feature-box, .testi-card, .partner-card, .value-card');
    cards.forEach(card => {
      card.setAttribute('data-animate', 'true');
      observer.observe(card);
    });

    // Observe section titles
    const titles = document.querySelectorAll('.section-title-wrap, .main-title, .section-title');
    titles.forEach(title => {
      title.setAttribute('data-animate', 'true');
      observer.observe(title);
    });
  }

  // ===== HOVER EFFECTS =====
  setupHoverEffects() {
    // Button hover effects
    const buttons = document.querySelectorAll('.btn-utama, .btn-sekunder, .btn-cta, .nav-link');
    buttons.forEach(btn => {
      btn.addEventListener('mouseenter', this.addHoverEffect);
      btn.addEventListener('mouseleave', this.removeHoverEffect);
    });

    // Card hover effects
    const cards = document.querySelectorAll('.feature-box, .testi-card, .partner-card, .value-card, .mitra-card');
    cards.forEach(card => {
      card.addEventListener('mouseenter', () => card.classList.add('elevated'));
      card.addEventListener('mouseleave', () => card.classList.remove('elevated'));
    });
  }

  addHoverEffect(e) {
    e.target.closest('a, button')?.classList.add('hover-active');
  }

  removeHoverEffect(e) {
    e.target.closest('a, button')?.classList.remove('hover-active');
  }

  // ===== NAVBAR SCROLL EFFECT =====
  setupNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    let lastScrollTop = 0;
    window.addEventListener('scroll', () => {
      const scrollTop = window.scrollY;

      if (scrollTop > 100) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }

      lastScrollTop = scrollTop;
    });
  }

  // ===== PARALLAX EFFECT =====
  setupParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    if (parallaxElements.length === 0) return;

    window.addEventListener('scroll', () => {
      parallaxElements.forEach(el => {
        const scrollPosition = window.scrollY;
        const elementOffset = el.offsetTop;
        const distance = scrollPosition - elementOffset;
        const parallaxSpeed = 0.5;

        if (distance > -window.innerHeight && distance < window.innerHeight) {
          el.style.transform = `translateY(${distance * parallaxSpeed}px)`;
        }
      });
    }, { passive: true });
  }

  // ===== COUNTER ANIMATION =====
  animateCounter(element, target, duration = 2000) {
    let current = 0;
    const increment = target / (duration / 16);
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        element.textContent = target;
        clearInterval(timer);
      } else {
        element.textContent = Math.floor(current);
      }
    }, 16);
  }

  // ===== TEXT REVEAL ANIMATION =====
  setupTextReveal() {
    const textElements = document.querySelectorAll('[data-text-reveal]');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('text-revealed');
          observer.unobserve(entry.target);
        }
      });
    }, this.observerOptions);

    textElements.forEach(el => observer.observe(el));
  }
}

// ===== SMOOTH SCROLL BEHAVIOR =====
function setupSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (href !== '#') {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });
}

// ===== PAGE LOAD ANIMATION =====
function setupPageLoadAnimation() {
  document.body.style.opacity = '0';
  window.addEventListener('load', () => {
    document.body.style.transition = 'opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
    document.body.style.opacity = '1';
  });
}

// ===== INTERSECTION OBSERVER FOR LAZY LOADING IMAGES =====
function setupLazyLoadImages() {
  const images = document.querySelectorAll('img[data-lazy]');
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.lazy;
          img.classList.add('loaded');
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '50px' });

    images.forEach(img => imageObserver.observe(img));
  }
}

// ===== STAGGERED LIST ANIMATIONS =====
function setupStaggeredAnimations() {
  const lists = document.querySelectorAll('[data-stagger]');
  lists.forEach(list => {
    const items = list.querySelectorAll('li, .item, > div');
    items.forEach((item, index) => {
      item.style.opacity = '0';
      item.style.animation = `fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s forwards`;
    });
  });
}

// ===== INITIALIZE EVERYTHING ON DOM READY =====
document.addEventListener('DOMContentLoaded', () => {
  // Initialize smooth animations
  new SmoothAnimations();
  
  // Setup additional features
  setupSmoothScroll();
  setupPageLoadAnimation();
  setupLazyLoadImages();
  setupStaggeredAnimations();

  // Reinitialize Lucide icons if present
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
});

// ===== UTILITY: ADD ANIMATION CLASSES TO ELEMENTS =====
window.addAnimationClass = function(selector, animationClass) {
  const elements = document.querySelectorAll(selector);
  elements.forEach((el, index) => {
    setTimeout(() => {
      el.classList.add(animationClass);
    }, index * 100);
  });
};

// ===== UTILITY: SCROLL TO ELEMENT =====
window.scrollToElement = function(elementId, offset = 0) {
  const element = document.getElementById(elementId);
  if (element) {
    const topPosition = element.offsetTop - offset;
    window.scrollTo({ top: topPosition, behavior: 'smooth' });
  }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = SmoothAnimations;
}
