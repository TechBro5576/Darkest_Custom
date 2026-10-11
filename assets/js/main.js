/* ============================================================
   Darkest Custom — Site interactions
   Single IIFE. No PHP. No emoji. No dependencies.
   ============================================================ */

(function () {
  'use strict';

  // ------------------------------------------------------------
  //  Utility: safe query helpers
  // ------------------------------------------------------------
  const $  = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  // ------------------------------------------------------------
  //  Navbar shadow on scroll
  // ------------------------------------------------------------
  const navbar = $('#siteNavbar');
  if (navbar) {
    const onScroll = () => {
      navbar.classList.toggle('scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ------------------------------------------------------------
  //  Close mobile nav after a link is tapped
  // ------------------------------------------------------------
  const navCollapse = $('#navMain');
  if (navCollapse) {
    $$('a', navCollapse).forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && navCollapse.classList.contains('show')) {
          const toggler = $('.navbar-toggler');
          if (toggler) toggler.click();
        }
      });
    });
  }

  // ------------------------------------------------------------
  //  Reveal on scroll
  // ------------------------------------------------------------
  const reveals = $$('.reveal');
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
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  $$('a[href^="#"]').forEach(link => {
    const href = link.getAttribute('href');
    if (!href || href === '#') return;

    link.addEventListener('click', (e) => {
      let target = null;
      try { target = document.querySelector(href); } catch { return; }
      if (!target) return;

      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - 80;

      window.scrollTo({
        top,
        behavior: prefersReduced ? 'auto' : 'smooth',
      });
    });
  });

  // ------------------------------------------------------------
  //  Image error fallback — collapse broken images cleanly
  // ------------------------------------------------------------
  $$('img').forEach(img => {
    img.addEventListener('error', () => {
      img.style.opacity = '0';
      img.style.visibility = 'hidden';
    });
  });

  // ------------------------------------------------------------
  //  Mobile sticky CTA — shows after hero, hides near footer
  // ------------------------------------------------------------
  const stickyCta = $('#mobileStickyCta');
  if (stickyCta) {
    const hero = $('.hero');
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
  const heroImg = $('.hero-image-wrap > img');
  if (heroImg && !prefersReduced && window.matchMedia('(min-width: 992px)').matches) {
    let raf = false;
    window.addEventListener('scroll', () => {
      if (raf) return;
      raf = true;
      requestAnimationFrame(() => {
        const y = Math.min(window.scrollY * 0.06, 40);
        heroImg.style.transform = `translateY(${y}px) scale(1.03)`;
        raf = false;
      });
    }, { passive: true });
  }

  // ------------------------------------------------------------
  //  Smart WhatsApp float — label updates by section
  //  Reads phone number from data attribute (never from inline PHP)
  // ------------------------------------------------------------
  const waFloat = $('#waFloat');
  const waLabel = $('#waLabel');
  if (waFloat && waLabel) {
    const waNumber = waFloat.dataset.waNumber
      || (waFloat.href.match(/wa\.me\/(\d+)/) || [])[1]
      || '';

    const sectionMap = [
      { sel: '.hero',                text: 'Need a quote?' },
      { sel: '#categories',          text: 'Not sure? Ask us' },
      { sel: '.section-customizer',  text: 'Need design help?' },
      { sel: '.section-bulk',        text: 'Bulk order help' },
      { sel: '#quote',               text: 'Prefer WhatsApp?' },
      { sel: '#faq',                 text: 'Still have questions?' },
    ];

    const setLabel = (text) => {
      waLabel.textContent = text;
      if (waNumber) {
        waFloat.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(text + ' — from Darkest Custom')}`;
      }
    };

    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const match = sectionMap.find(s => $(s.sel) === entry.target);
          if (match) setLabel(match.text);
        });
      }, { threshold: 0.5, rootMargin: '-80px 0px -40% 0px' });

      sectionMap.forEach(s => {
        const el = $(s.sel);
        if (el) io.observe(el);
      });
    }
  }

  // ------------------------------------------------------------
  //  Wishlist (localStorage-backed)
  // ------------------------------------------------------------
  const WISH_KEY = 'darkest_wishlist';
  const readWish = () => {
    try { return JSON.parse(localStorage.getItem(WISH_KEY) || '[]'); }
    catch { return []; }
  };
  const writeWish = (arr) => {
    try { localStorage.setItem(WISH_KEY, JSON.stringify(arr)); } catch {}
  };

  $$('.product-wish').forEach(btn => {
    const id = btn.dataset.wish;
    if (!id) return;

    if (readWish().includes(id)) btn.classList.add('active');

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const list = readWish();
      const idx = list.indexOf(id);

      if (idx === -1) {
        list.push(id);
        btn.classList.add('active');
      } else {
        list.splice(idx, 1);
        btn.classList.remove('active');
      }
      writeWish(list);
    });
  });

  // ------------------------------------------------------------
  //  Live activity ticker — rotates through real-sounding messages
  // ------------------------------------------------------------
  const tickerEl = $('#activityTicker');
  if (tickerEl) {
    const messages = [
      'Someone in Lagos just ordered a Custom Mug — 12 orders in the last 24 hours',
      'Someone in Abuja just customized a T-Shirt — printing in Asaba',
      'Someone in Port Harcourt just ordered a Branded Cap — 3 mugs left in this week\u2019s batch',
      'Someone in Asaba just uploaded a logo — average reply time: 8 minutes',
      'Someone in Kano just ordered a Gift Box — delivered in 3 days',
    ];

    let i = 0;
    setInterval(() => {
      i = (i + 1) % messages.length;
      tickerEl.classList.add('is-swapping');
      setTimeout(() => {
        tickerEl.textContent = messages[i];
        tickerEl.classList.remove('is-swapping');
      }, 300);
    }, 5000);
  }

  // ------------------------------------------------------------
  //  Customizer teaser — live logo upload + size control
  //  Matches the redesigned mug structure in index.php
  // ------------------------------------------------------------
  const demoUpload = $('#demoLogoUpload');
  const demoLogo   = $('#customizerLogo');
  const demoSize   = $('#demoLogoSize');
  const MAX_BYTES  = 8 * 1024 * 1024;

  const applyLogoSize = (px) => {
    if (!demoLogo) return;
    if (demoLogo.classList.contains('has-image')) {
      demoLogo.style.width  = px + 'px';
      demoLogo.style.height = px + 'px';
    } else {
      demoLogo.style.fontSize = Math.round(px * 0.24) + 'px';
    }
  };

  if (demoUpload && demoLogo) {
    demoUpload.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;

      if (file.size > MAX_BYTES) {
        alert('File must be under 8 MB.');
        demoUpload.value = '';
        return;
      }

      const okTypes = ['image/png', 'image/jpeg', 'image/svg+xml'];
      if (!okTypes.includes(file.type)) {
        alert('Please upload a PNG, JPG, or SVG.');
        demoUpload.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (ev) => {
        demoLogo.textContent = '';
        demoLogo.style.backgroundImage = `url(${ev.target.result})`;
        demoLogo.classList.add('has-image');

        if (demoSize) applyLogoSize(parseInt(demoSize.value, 10));
      };
      reader.readAsDataURL(file);
    });
  }

  if (demoSize && demoLogo) {
    demoSize.addEventListener('input', (e) => {
      applyLogoSize(parseInt(e.target.value, 10));
    });
  }

  // ------------------------------------------------------------
  //  Quote form — client validation + AJAX submit
  //  Reads WhatsApp number from data attribute, never inline PHP
  // ------------------------------------------------------------
  const quoteForm  = $('#quoteForm');
  const quoteToast = $('#quoteToast');

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

      [nameEl, phoneEl, interestEl].forEach(el => el && el.classList.remove('is-error'));

      let valid = true;
      if (name.length < 2)                       { nameEl?.classList.add('is-error');     valid = false; }
      if (phone.replace(/\D/g, '').length < 10)  { phoneEl?.classList.add('is-error');    valid = false; }
      if (!interest)                             { interestEl?.classList.add('is-error'); valid = false; }
      if (!valid) return;

      const btn = quoteForm.querySelector('button[type="submit"]');
      const originalHtml = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = 'Sending…'; }

      try {
        const res  = await fetch('/api/quote-submit.php', {
          method: 'POST',
          body: new FormData(quoteForm),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
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

    // Live: clear error styling as user fixes a field
    ['name', 'phone', 'interest'].forEach(field => {
      const el = quoteForm.elements[field];
      if (!el) return;
      el.addEventListener('input', () => el.classList.remove('is-error'));
      el.addEventListener('change', () => el.classList.remove('is-error'));
    });
  }

  // ------------------------------------------------------------
  //  Smooth reveal of card media (avoid image pop-in)
  // ------------------------------------------------------------
  const lazyImgs = $$('img[loading="lazy"]');
  if ('IntersectionObserver' in window && lazyImgs.length) {
    const imgIo = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.complete) {
            img.style.opacity = '1';
          } else {
            img.addEventListener('load', () => { img.style.opacity = '1'; }, { once: true });
          }
          imgIo.unobserve(img);
        }
      });
    }, { rootMargin: '100px' });

    lazyImgs.forEach(img => {
      img.style.opacity = '0';
      img.style.transition = 'opacity 400ms ease';
      imgIo.observe(img);
    });
  }

})();