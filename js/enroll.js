/* ============================================================
   enroll.js
   Handles:
     1. National ID / No ID toggle on the enrollment form
     2. Form submission via Fetch API to submit_enrollment.php
     3. Successful registration screen transition
   ============================================================ */

// State variable to track ID presence (1 = Yes, 0 = No)
let hasIdChoice = 1;

/* ── 1. ID Toggle ──────────────────────────────────────────── */

function toggleId(choice) {
  var btnYes     = document.getElementById('btn-yes');
  var btnNo      = document.getElementById('btn-no');
  var idFields   = document.getElementById('id-fields');
  var noIdNotice = document.getElementById('no-id-notice');

  if (choice === 'yes') {
    hasIdChoice = 1;
    btnYes.classList.add('selected');
    btnNo.classList.remove('selected');
    idFields.style.display = 'block';
    noIdNotice.style.display = 'none';
  } else {
    hasIdChoice = 0;
    btnNo.classList.add('selected');
    btnYes.classList.remove('selected');
    idFields.style.display = 'none';
    noIdNotice.style.display = 'block';
  }
}


/* ── 2. Form Submission ────────────────────────────────────── */

function handleSubmit() {
  // Clear previous error
  var errorBanner = document.getElementById('formErrorBanner');
  errorBanner.style.display = 'none';
  errorBanner.textContent = '';

  // Grab form inputs
  var firstName  = document.getElementById('first_name').value.trim();
  var middleName = document.getElementById('middle_name').value.trim();
  var lastName   = document.getElementById('last_name').value.trim();
  var idType     = document.getElementById('id_type').value;
  var idNumber   = document.getElementById('id_number').value.trim();
  var phone      = document.getElementById('phone').value.trim();
  var email      = document.getElementById('email').value.trim();
  var address    = document.getElementById('address').value.trim();
  var course     = document.getElementById('course').value;
  var schedule   = document.getElementById('schedule').value;

  // Frontend Validation
  if (!firstName) {
    showError('Please enter your first name.');
    document.getElementById('first_name').focus();
    return;
  }
  if (!lastName) {
    showError('Please enter your last name.');
    document.getElementById('last_name').focus();
    return;
  }

  // ID validation only if user has selected "Yes"
  if (hasIdChoice === 1) {
    if (!idType) {
      showError('Please select your ID type.');
      document.getElementById('id_type').focus();
      return;
    }
    if (!idNumber) {
      showError('Please enter your ID/Certificate number.');
      document.getElementById('id_number').focus();
      return;
    }
  }

  if (!phone) {
    showError('Please enter your active phone number.');
    document.getElementById('phone').focus();
    return;
  }
  
  if (!address) {
    showError('Please enter your residential address.');
    document.getElementById('address').focus();
    return;
  }
  if (!course) {
    showError('Please select a course of interest.');
    document.getElementById('course').focus();
    return;
  }

  // Prepare submission button
  var submitBtn = document.getElementById('submitBtn');
  var originalBtnText = submitBtn.textContent;
  submitBtn.textContent = 'Submitting Application...';
  submitBtn.classList.add('loading');

  // Build request payload
  var payload = {
    first_name: firstName,
    middle_name: middleName,
    last_name: lastName,
    has_id: hasIdChoice,
    id_type: hasIdChoice === 1 ? idType : '',
    id_number: hasIdChoice === 1 ? idNumber : '',
    phone: phone,
    email: email,
    address: address,
    course: course,
    schedule: schedule
  };

  // Submit via AJAX Fetch POST
  fetch('submit_enrollment.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(function(response) {
    return response.json();
  })
  .then(function(data) {
    // Reset submit button state
    submitBtn.textContent = originalBtnText;
    submitBtn.classList.remove('loading');

    if (data.status === 'success') {
      // Transition to success screen
      document.getElementById('formFieldsContainer').style.display = 'none';
      var successContainer = document.getElementById('successContainer');
      successContainer.style.display = 'flex';
      
      // Update success message text if sent by server
      if (data.message) {
        document.getElementById('successMessageText').textContent = data.message;
      }
    } else {
      showError(data.message || 'An error occurred during submission. Please try again.');
    }
  })
  .catch(function(error) {
    submitBtn.textContent = originalBtnText;
    submitBtn.classList.remove('loading');
    showError('Unable to connect to the server. Please verify your connection.');
    console.error('Error submitting enrollment:', error);
  });
}

// Display error banner
function showError(message) {
  var errorBanner = document.getElementById('formErrorBanner');
  errorBanner.textContent = message;
  errorBanner.style.display = 'block';
  errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Reset form to clear inputs and return from success screen
function resetForm() {
  // Clear values
  document.getElementById('first_name').value = '';
  document.getElementById('middle_name').value = '';
  document.getElementById('last_name').value = '';
  document.getElementById('id_type').value = '';
  document.getElementById('id_number').value = '';
  document.getElementById('phone').value = '';
  document.getElementById('email').value = '';
  document.getElementById('address').value = '';
  document.getElementById('course').value = '';
  document.getElementById('schedule').value = '';
  
  // Reset ID toggle state
  toggleId('yes');

  // Hide success, show fields
  document.getElementById('successContainer').style.display = 'none';
  document.getElementById('formFieldsContainer').style.display = 'block';
  
  // Hide errors
  document.getElementById('formErrorBanner').style.display = 'none';
}
