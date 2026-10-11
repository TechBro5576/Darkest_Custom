/* ============================================================
   Darkest Custom — Site interactions
   All logic lives inside one IIFE. No PHP in this file.
   ============================================================ */

(function () {
  'use strict';

  // ------------------------------------------------------------
  //  Navbar shadow on scroll
  // ------------------------------------------------------------
  const navbar = document.getElementById('siteNavbar');
  if (navbar) {
    const onScroll = () => {
      navbar.classList.toggle('scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ------------------------------------------------------------
  //  Close mobile menu after a nav link is clicked
  // ------------------------------------------------------------
  const navCollapse = document.getElementById('navMain');
  if (navCollapse) {
    navCollapse.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && navCollapse.classList.contains('show')) {
          const toggler = document.querySelector('.navbar-toggler');
          if (toggler) toggler.click();
        }
      });
    });
  }

  // ------------------------------------------------------------
  //  Reveal on scroll
  // ------------------------------------------------------------
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    reveals.forEach(el => io.observe(el));
  } else {
    reveals.forEach(el => el.classList.add('is-visible'));
  }

  // ------------------------------------------------------------
  //  Smooth scroll for in-page anchors
  // ------------------------------------------------------------
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    const href = link.getAttribute('href');
    if (!href || href === '#') return;
    link.addEventListener('click', (e) => {
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        const top = target.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });

  // ------------------------------------------------------------
  //  Image error fallback
  // ------------------------------------------------------------
  document.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', () => {
      img.style.display = 'none';
    });
  });

  // ------------------------------------------------------------
  //  Mobile sticky CTA visibility
  // ------------------------------------------------------------
  const stickyCta = document.getElementById('mobileStickyCta');
  if (stickyCta) {
    const hero = document.querySelector('.hero');
    const onScroll = () => {
      if (!hero) return;
      const past = window.scrollY > hero.offsetHeight * 0.9;
      const nearEnd = window.scrollY + window.innerHeight > document.body.offsetHeight - 400;
      stickyCta.classList.toggle('is-visible', past && !nearEnd);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ------------------------------------------------------------
  //  Hero image parallax (desktop only, respects reduced motion)
  // ------------------------------------------------------------
  const heroImg = document.querySelector('.hero-image-wrap img');
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (heroImg && !prefersReduced && window.matchMedia('(min-width: 992px)').matches) {
    let raf = false;
    window.addEventListener('scroll', () => {
      if (raf) return;
      raf = true;
      requestAnimationFrame(() => {
        const y = Math.min(window.scrollY * 0.06, 40);
        heroImg.style.transform = `translateY(${y}px) scale(1.02)`;
        raf = false;
      });
    }, { passive: true });
  }

  // ------------------------------------------------------------
  //  Smart WhatsApp float label
  //  Reads the phone number from a data attribute on the anchor —
  //  never from inline PHP in this file.
  // ------------------------------------------------------------
  const waFloat = document.getElementById('waFloat');
  const waLabel = document.getElementById('waLabel');
  if (waFloat && waLabel) {
    const waNumber = waFloat.dataset.waNumber || waFloat.href.match(/wa\.me\/(\d+)/)?.[1] || '';
    const sections = [
      { sel: '.hero',               text: 'Need a quote?' },
      { sel: '#categories',         text: 'Not sure? Ask us' },
      { sel: '#quote',              text: 'Prefer WhatsApp?' },
      { sel: '.section-bulk',       text: 'Bulk order help' },
      { sel: '.section-customizer', text: 'Need design help?' },
    ];

    const setWaLabel = (text) => {
      waLabel.textContent = text;
      if (waNumber) {
        waFloat.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(text + ' — from Darkest Custom')}`;
      }
    };

    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const match = sections.find(s => document.querySelector(s.sel) === entry.target);
            if (match) setWaLabel(match.text);
          }
        });
      }, { threshold: 0.5 });

      sections.forEach(s => {
        const el = document.querySelector(s.sel);
        if (el) io.observe(el);
      });
    }
  }

  // ------------------------------------------------------------
  //  Wishlist (localStorage)
  // ------------------------------------------------------------
  const wishKey = 'darkest_wishlist';
  const readWish  = () => { try { return JSON.parse(localStorage.getItem(wishKey) || '[]'); } catch { return []; } };
  const writeWish = (arr) => { try { localStorage.setItem(wishKey, JSON.stringify(arr)); } catch {} };

  document.querySelectorAll('.product-wish').forEach(btn => {
    const id = btn.dataset.wish;
    if (!id) return;
    if (readWish().includes(id)) btn.classList.add('active');
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const list = readWish();
      const idx = list.indexOf(id);
      if (idx === -1) { list.push(id); btn.classList.add('active'); }
      else            { list.splice(idx, 1); btn.classList.remove('active'); }
      writeWish(list);
    });
  });

  // ------------------------------------------------------------
  //  Live activity ticker rotation
  // ------------------------------------------------------------
  const tickerEl = document.getElementById('activityTicker');
  if (tickerEl) {
    const messages = [
      'Someone in Lagos just ordered a Custom Mug · 12 orders in the last 24 hours',
      'Someone in Abuja just customized a T-Shirt · printing in Asaba',
      'Someone in Port Harcourt just ordered a Branded Cap · 3 mugs left in this week’s batch',
      'Someone in Asaba just uploaded a logo · average reply time: 8 minutes',
      'Someone in Kano just ordered a Gift Box · delivered in 3 days',
    ];
    let i = 0;
    setInterval(() => {
      i = (i + 1) % messages.length;
      tickerEl.classList.add('is-swapping');
      setTimeout(() => {
        tickerEl.textContent = messages[i];
        tickerEl.classList.remove('is-swapping');
      }, 300);
    }, 4500);
  }

  // ------------------------------------------------------------
  //  Customizer teaser demo
  // ------------------------------------------------------------
  const demoUpload = document.getElementById('demoLogoUpload');
  const demoLogo   = document.getElementById('customizerLogo');
  const demoSize   = document.getElementById('demoLogoSize');

  if (demoUpload && demoLogo) {
    demoUpload.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      if (file.size > 8 * 1024 * 1024) {
        alert('File must be under 8MB.');
        return;
      }
      const reader = new FileReader();
      reader.onload = (ev) => {
        demoLogo.textContent = '';
        demoLogo.style.backgroundImage = `url(${ev.target.result})`;
        demoLogo.classList.add('has-image');
        // Preserve current size
        if (demoSize) {
          const size = parseInt(demoSize.value, 10);
          demoLogo.style.width  = size + 'px';
          demoLogo.style.height = size + 'px';
        }
      };
      reader.readAsDataURL(file);
    });
  }

  if (demoSize && demoLogo) {
    demoSize.addEventListener('input', (e) => {
      const size = parseInt(e.target.value, 10);
      if (demoLogo.classList.contains('has-image')) {
        demoLogo.style.width  = size + 'px';
        demoLogo.style.height = size + 'px';
      } else {
        demoLogo.style.fontSize = (size * 0.24) + 'px';
      }
    });
  }

  // ------------------------------------------------------------
  //  Quote form submit
  //  Endpoint: /api/quote-submit.php
  //  Phone number for WhatsApp handoff comes from the form’s
  //  data-wa-number attribute — no PHP inside this file.
  // ------------------------------------------------------------
  const quoteForm  = document.getElementById('quoteForm');
  const quoteToast = document.getElementById('quoteToast');
  if (quoteForm) {
    const waNumber = quoteForm.dataset.waNumber || '';

    quoteForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const nameEl     = quoteForm.elements['name'];
      const phoneEl    = quoteForm.elements['phone'];
      const interestEl = quoteForm.elements['interest'];
      const qtyEl      = quoteForm.elements['quantity'];

      const name     = (nameEl?.value || '').trim();
      const phone    = (phoneEl?.value || '').trim();
      const interest = (interestEl?.value || '').trim();

      let valid = true;
      [nameEl, phoneEl, interestEl].forEach(el => el && el.classList.remove('is-error'));
      if (name.length < 2)                        { nameEl?.classList.add('is-error');     valid = false; }
      if (phone.replace(/\D/g, '').length < 10)   { phoneEl?.classList.add('is-error');    valid = false; }
      if (!interest)                              { interestEl?.classList.add('is-error'); valid = false; }
      if (!valid) return;

      const btn = quoteForm.querySelector('button[type="submit"]');
      const originalHtml = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = 'Sending…'; }

      try {
        const res  = await fetch('/api/quote-submit.php', { method: 'POST', body: new FormData(quoteForm) });
        const data = await res.json().catch(() => ({ ok: false }));

        if (data.ok) {
          quoteForm.reset();
          if (quoteToast) {
            quoteToast.hidden = false;
            setTimeout(() => { quoteToast.hidden = true; }, 5000);
          }
          if (waNumber) {
            const text = encodeURIComponent(
              `Hi Darkest Custom, I just submitted a quote request.\n` +
              `Name: ${name}\n` +
              `WhatsApp: ${phone}\n` +
              `Interest: ${interest}\n` +
              `Quantity: ${qtyEl?.value || '-'}`
            );
            window.open(`https://wa.me/${waNumber}?text=${text}`, '_blank', 'noopener');
          }
        } else {
          alert('Could not send. Please try WhatsApp instead.');
        }
      } catch {
        alert('Could not send. Please try WhatsApp instead.');
      } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
      }
    });
  }

})();