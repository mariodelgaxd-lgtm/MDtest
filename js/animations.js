// Theme Toggle and Animations System
(function() {
  'use strict';

  // ===== THEME TOGGLE =====
  const themeToggle = {
    init() {
      const toggle = document.getElementById('theme-toggle');
      if (!toggle) return;
      
      // Check saved theme or default to dark
      const savedTheme = localStorage.getItem('theme') || 'dark';
      document.documentElement.setAttribute('data-theme', savedTheme);
      
      if (savedTheme === 'light') {
        toggle.classList.add('active');
      }
      
      toggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        toggle.classList.toggle('active');
        
        // Trigger custom event
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: newTheme } }));
      });
    }
  };

  // ===== SCROLL ANIMATIONS =====
  const scrollAnimations = {
    init() {
      const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
      
      if (revealElements.length === 0) return;
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('active');
            
            // Handle stagger for children
            const staggerChildren = entry.target.querySelectorAll('.stagger-child');
            staggerChildren.forEach((child, index) => {
              child.style.animationDelay = `${index * 0.1}s`;
              child.classList.add('animate-fade-in');
            });
          }
        });
      }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      });
      
      revealElements.forEach(el => observer.observe(el));
    }
  };

  // ===== NAVBAR SCROLL EFFECT =====
  const navbarScroll = {
    init() {
      const navbar = document.querySelector('.navbar-custom');
      if (!navbar) return;
      
      let lastScroll = 0;
      
      window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
          navbar.classList.add('scrolled');
        } else {
          navbar.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
      }, { passive: true });
    }
  };

  // ===== COUNTER ANIMATION =====
  const counterAnimation = {
    init() {
      const counters = document.querySelectorAll('.counter');
      if (counters.length === 0) return;
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const counter = entry.target;
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = parseInt(counter.getAttribute('data-duration')) || 2000;
            const prefix = counter.getAttribute('data-prefix') || '';
            const suffix = counter.getAttribute('data-suffix') || '';
            
            this.animate(counter, target, duration, prefix, suffix);
            observer.unobserve(counter);
          }
        });
      }, { threshold: 0.5 });
      
      counters.forEach(counter => observer.observe(counter));
    },
    
    animate(element, target, duration, prefix, suffix) {
      const start = 0;
      const increment = target / (duration / 16);
      let current = start;
      
      const update = () => {
        current += increment;
        if (current < target) {
          element.textContent = prefix + Math.floor(current) + suffix;
          requestAnimationFrame(update);
        } else {
          element.textContent = prefix + target + suffix;
        }
      };
      
      update();
    }
  };

  // ===== SMOOTH SCROLL =====
  const smoothScroll = {
    init() {
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
    }
  };

  // ===== PROGRESS BAR ANIMATION =====
  const progressBars = {
    init() {
      const bars = document.querySelectorAll('.progress-custom-fill');
      if (bars.length === 0) return;
      
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const bar = entry.target;
            const width = bar.getAttribute('data-width');
            if (width) {
              setTimeout(() => {
                bar.style.width = width + '%';
              }, 200);
            }
            observer.unobserve(bar);
          }
        });
      }, { threshold: 0.5 });
      
      bars.forEach(bar => observer.observe(bar));
    }
  };

  // ===== PARALLAX EFFECT =====
  const parallax = {
    init() {
      const elements = document.querySelectorAll('.parallax');
      if (elements.length === 0) return;
      
      window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        elements.forEach(el => {
          const speed = el.getAttribute('data-speed') || 0.5;
          el.style.transform = `translateY(${scrolled * speed}px)`;
        });
      }, { passive: true });
    }
  };

  // ===== LOADING SKELETON =====
  const skeletonLoading = {
    init() {
      const skeletons = document.querySelectorAll('.skeleton');
      
      // Remove skeleton class after content loads
      setTimeout(() => {
        skeletons.forEach(skeleton => {
          skeleton.classList.remove('skeleton');
        });
      }, 1500);
    }
  };

  // ===== TYPING EFFECT =====
  const typingEffect = {
    init() {
      const elements = document.querySelectorAll('.typing-effect');
      if (elements.length === 0) return;
      
      elements.forEach(el => {
        const text = el.getAttribute('data-text');
        const speed = parseInt(el.getAttribute('data-speed')) || 50;
        
        if (text) {
          this.type(el, text, speed);
        }
      });
    },
    
    type(element, text, speed) {
      let i = 0;
      element.textContent = '';
      
      const typeChar = () => {
        if (i < text.length) {
          element.textContent += text.charAt(i);
          i++;
          setTimeout(typeChar, speed);
        }
      };
      
      typeChar();
    }
  };

  // ===== MAGNETIC BUTTON EFFECT =====
  const magneticButtons = {
    init() {
      const buttons = document.querySelectorAll('.magnetic');
      if (buttons.length === 0) return;
      
      buttons.forEach(button => {
        button.addEventListener('mousemove', (e) => {
          const rect = button.getBoundingClientRect();
          const x = e.clientX - rect.left - rect.width / 2;
          const y = e.clientY - rect.top - rect.height / 2;
          
          button.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
        });
        
        button.addEventListener('mouseleave', () => {
          button.style.transform = 'translate(0, 0)';
        });
      });
    }
  };

  // ===== TOOLTIP SYSTEM =====
  const tooltipSystem = {
    init() {
      const tooltips = document.querySelectorAll('[data-tooltip]');
      
      tooltips.forEach(el => {
        el.classList.add('tooltip-custom');
      });
    }
  };

  // ===== MOBILE MENU =====
  const mobileMenu = {
    init() {
      const toggler = document.querySelector('.navbar-toggler');
      const menu = document.querySelector('.navbar-collapse');
      
      if (!toggler || !menu) return;
      
      toggler.addEventListener('click', () => {
        menu.classList.toggle('show');
      });
      
      // Close menu on link click
      menu.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
          menu.classList.remove('show');
        });
      });
    }
  };

  // ===== TEST ANIMATIONS =====
  const testAnimations = {
    init() {
      // Animate question cards on test page
      const questions = document.querySelectorAll('.question-card');
      questions.forEach((q, i) => {
        q.style.animationDelay = `${i * 0.1}s`;
        q.classList.add('animate-fade-in');
      });
    }
  };

  // ===== PARTICLES BACKGROUND (LIGHTWEIGHT) =====
  const particles = {
    init() {
      const container = document.getElementById('particles');
      if (!container) return;
      
      const particleCount = 25;
      const colors = ['#6366f1', '#8b5cf6', '#a78bfa'];
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
          position: absolute;
          width: ${Math.random() * 4 + 2}px;
          height: ${Math.random() * 4 + 2}px;
          background: ${colors[Math.floor(Math.random() * colors.length)]};
          border-radius: 50%;
          left: ${Math.random() * 100}%;
          top: ${Math.random() * 100}%;
          opacity: ${Math.random() * 0.5 + 0.1};
          animation: float ${Math.random() * 10 + 10}s ease-in-out infinite;
          animation-delay: ${Math.random() * 5}s;
          pointer-events: none;
        `;
        container.appendChild(particle);
      }
    }
  };

  // ===== INIT ALL =====
  document.addEventListener('DOMContentLoaded', () => {
    themeToggle.init();
    scrollAnimations.init();
    navbarScroll.init();
    counterAnimation.init();
    smoothScroll.init();
    progressBars.init();
    parallax.init();
    skeletonLoading.init();
    typingEffect.init();
    magneticButtons.init();
    tooltipSystem.init();
    mobileMenu.init();
    testAnimations.init();
    particles.init();
  });

})();
