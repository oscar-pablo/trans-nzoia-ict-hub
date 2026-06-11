/* ============================================================
   enroll.js
   Handles:
     1. National ID / No ID toggle on the enrollment form
     2. Form submission
   ============================================================ */


/* ── 1. ID Toggle ──────────────────────────────────────────── */

function toggleId(choice) {
  var btnYes     = document.getElementById('btn-yes');
  var btnNo      = document.getElementById('btn-no');
  var idFields   = document.getElementById('id-fields');
  var noIdNotice = document.getElementById('no-id-notice');

  if (choice === 'yes') {
    btnYes.classList.add('selected');
    btnNo.classList.remove('selected');
    idFields.classList.remove('hidden');
    noIdNotice.classList.remove('show');
  } else {
    btnNo.classList.add('selected');
    btnYes.classList.remove('selected');
    idFields.classList.add('hidden');
    noIdNotice.classList.add('show');
  }
}


/* ── 2. Form Submission ────────────────────────────────────── */

function handleSubmit() {
  /* Basic validation */
  var firstName = document.querySelector('input[placeholder="Enter your first name"]');
  var lastName  = document.querySelector('input[placeholder="Enter your last name"]');
  var phone     = document.querySelector('input[type="tel"]');
  var address   = document.querySelector('input[placeholder="Village, Sub-County, Trans-Nzoia"]');
  var course    = document.querySelector('.form-select');

  if (!firstName.value.trim()) {
    alert('Please enter your first name.');
    firstName.focus();
    return;
  }
  if (!lastName.value.trim()) {
    alert('Please enter your last name.');
    lastName.focus();
    return;
  }
  if (!phone.value.trim()) {
    alert('Please enter your phone number.');
    phone.focus();
    return;
  }
  if (!address.value.trim()) {
    alert('Please enter your residential address.');
    address.focus();
    return;
  }
  if (!course.value) {
    alert('Please select a course.');
    course.focus();
    return;
  }

  alert(
    'Thank you for applying!\n\n' +
    'We will contact you within 24 hours to confirm your enrollment.\n\n' +
    'Welcome to Trans-Nzoia Community ICT Hub!'
  );
}
