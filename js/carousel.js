/* ============================================================
   carousel.js
   Handles:
     1. Home page photo carousel
     2. Instructor carousel on the More page
   ============================================================ */


/* ── 1. Home Page Carousel ─────────────────────────────────── */

var currentSlide  = 0;
var totalSlides   = 4;
var autoSlideTimer;

function updateCarousel() {
  var track = document.getElementById('carouselTrack');
  if (!track) return;
  track.style.transform = 'translateX(-' + currentSlide * 100 + '%)';
  document.querySelectorAll('.dot').forEach(function (dot, i) {
    dot.classList.toggle('active', i === currentSlide);
  });
}

function moveSlide(direction) {
  currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
  updateCarousel();
  resetAutoSlide();
}

function goSlide(index) {
  currentSlide = index;
  updateCarousel();
  resetAutoSlide();
}

function resetAutoSlide() {
  clearInterval(autoSlideTimer);
  autoSlideTimer = setInterval(function () { moveSlide(1); }, 4500);
}

/* Start home carousel if the track element exists on this page */
if (document.getElementById('carouselTrack')) {
  autoSlideTimer = setInterval(function () { moveSlide(1); }, 4500);
}


/* ── 2. Instructor Carousel (More page) ────────────────────── */

(function () {
  var TOTAL = 5;
  var instrCurrent = 0;
  var instrTimer;

  function updateInstr() {
    var track = document.getElementById('instrCarouselTrack');
    if (!track) return;
    track.style.transform = 'translateX(-' + instrCurrent * 100 + '%)';
    document.querySelectorAll('.instr-dot').forEach(function (d, i) {
      d.classList.toggle('active', i === instrCurrent);
    });
  }

  function resetInstrTimer() {
    clearInterval(instrTimer);
    /* random interval 3 s – 6 s for a natural feel */
    var delay = 3000 + Math.floor(Math.random() * 3000);
    instrTimer = setTimeout(function () {
      var next;
      do { next = Math.floor(Math.random() * TOTAL); } while (next === instrCurrent);
      instrCurrent = next;
      updateInstr();
      resetInstrTimer();
    }, delay);
  }

  window.instrMove = function (dir) {
    instrCurrent = (instrCurrent + dir + TOTAL) % TOTAL;
    updateInstr();
    resetInstrTimer();
  };

  window.instrGo = function (idx) {
    instrCurrent = idx;
    updateInstr();
    resetInstrTimer();
  };

  /* Start instructor carousel only if its track exists on this page */
  if (document.getElementById('instrCarouselTrack')) {
    resetInstrTimer();
  }

})();
