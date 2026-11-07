const menuBtn = document.querySelector('[data-menu]');
const nav = document.querySelector('[data-nav]');
menuBtn?.addEventListener('click', () => {
  const open = nav.classList.toggle('is-open');
  menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
});

nav?.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => {
    nav.classList.remove('is-open');
    menuBtn?.setAttribute('aria-expanded', 'false');
  });
});

document.addEventListener('click', (event) => {
  document.querySelectorAll('.nav-drop[open]').forEach((drop) => {
    if (!drop.contains(event.target)) {
      drop.removeAttribute('open');
    }
  });
});

document.documentElement.classList.add('js');
const revealed = document.querySelectorAll('[data-reveal]');
if (revealed.length && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    });
  }, { threshold: 0.18, rootMargin: '0px 0px -6% 0px' });
  revealed.forEach((node) => io.observe(node));
} else {
  revealed.forEach((node) => node.classList.add('is-in'));
}

const bar = document.querySelector('.nav');
const onScroll = () => bar?.classList.toggle('is-stuck', window.scrollY > 8);
document.addEventListener('scroll', onScroll, { passive: true });
onScroll();
