<?php
/**
 * Authentication Portal (Student Login, Student Signup, Staff Login)
 * IIIT Kota Hostel Management System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

// Redirect already authenticated users to their corresponding portal shell
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header('Location: student.php');
        exit;
    }
    if (in_array($_SESSION['role'], ['warden', 'caretaker'], true)) {
        header('Location: staff.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <title>Hostel Portal | IIIT Kota</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    .auth-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: var(--space-6) var(--space-4);
      background: var(--bg-page);
    }
    .auth-brand {
      text-align: center;
      margin-bottom: var(--space-6);
    }
    .auth-brand h1 {
      font-size: var(--text-2xl);
      font-weight: var(--font-weight-semibold);
      letter-spacing: -0.02em;
    }
    .auth-brand p {
      font-size: var(--text-sm);
      color: var(--text-muted);
      margin-top: var(--space-1);
    }
    .auth-card {
      width: 100%;
      max-width: 440px;
      background: var(--bg-surface);
      border: 1px solid var(--border-hairline);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-dropdown);
      overflow: hidden;
    }
    .auth-tabs {
      display: flex;
      border-bottom: 1px solid var(--border-hairline);
      background: var(--bg-subtle);
    }
    .auth-tab-btn {
      flex: 1;
      padding: var(--space-3) var(--space-2);
      font-size: var(--text-sm);
      font-weight: var(--font-weight-medium);
      color: var(--text-secondary);
      background: transparent;
      border: 0;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      transition: color var(--transition-fast), border-color var(--transition-fast), background-color var(--transition-fast);
      text-align: center;
    }
    .auth-tab-btn:hover {
      color: var(--text-main);
    }
    .auth-tab-btn.is-active {
      color: var(--accent);
      background: var(--bg-surface);
      border-bottom-color: var(--accent);
      font-weight: var(--font-weight-semibold);
    }
    .auth-tab-pane {
      display: none;
      padding: var(--space-6);
    }
    .auth-tab-pane.is-active {
      display: block;
    }
    .live-feedback {
      font-size: var(--text-xs);
      margin-top: var(--space-1);
      display: none;
    }
    .live-feedback.is-valid {
      color: var(--color-success);
      display: block;
    }
    .live-feedback.is-invalid {
      color: var(--color-danger);
      display: block;
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-brand">
      <h1>IIIT Kota</h1>
      <p>Hostel Management Portal</p>
    </div>

    <div class="auth-card">
      <!-- Auth Switcher Tabs -->
      <div class="auth-tabs" role="tablist">
        <button type="button" class="auth-tab-btn is-active" id="tab-student-login" onclick="switchAuthTab('student-login')" role="tab" aria-selected="true">
          Student Login
        </button>
        <button type="button" class="auth-tab-btn" id="tab-student-signup" onclick="switchAuthTab('student-signup')" role="tab" aria-selected="false">
          Registration
        </button>
        <button type="button" class="auth-tab-btn" id="tab-staff-login" onclick="switchAuthTab('staff-login')" role="tab" aria-selected="false">
          Staff Portal
        </button>
      </div>

      <!-- Tab 1: Student Login Form -->
      <div class="auth-tab-pane is-active" id="pane-student-login" role="tabpanel">
        <form id="studentLoginForm">
          <div class="form-group">
            <label class="form-label" for="loginCollegeId">College Email ID</label>
            <input class="form-input" id="loginCollegeId" name="college_id" type="email" placeholder="2025kucp1010@iiit.kota.ac.in" required autocomplete="username">
            <div class="form-error"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="loginPassword">Password</label>
            <input class="form-input" id="loginPassword" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
            <div class="form-error"></div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-2);">
            Sign In as Student
          </button>
        </form>
      </div>

      <!-- Tab 2: Student Registration Form -->
      <div class="auth-tab-pane" id="pane-student-signup" role="tabpanel">
        <form id="studentSignupForm">
          <div class="form-group" id="group-signup-id">
            <label class="form-label" for="signupCollegeId">College Email ID</label>
            <input class="form-input" id="signupCollegeId" name="college_id" type="email" placeholder="2025kucp1010@iiit.kota.ac.in" required autocomplete="off">
            <div class="form-hint">Must match: <code>20XXku&lt;branch&gt;&lt;roll&gt;@iiit.kota.ac.in</code> (e.g. <code>2025kucp1010@iiit.kota.ac.in</code>)</div>
            <div class="live-feedback" id="signupIdFeedback"></div>
            <div class="form-error"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="signupPassword">Password</label>
            <input class="form-input" id="signupPassword" name="password" type="password" minlength="8" placeholder="Min 8 chars, e.g. Hostel@2025" required autocomplete="new-password">
            <div class="form-hint">Min 8 characters — must include uppercase, lowercase, digit, and special character (e.g. <code>Hostel@2025</code>).</div>
            <div class="live-feedback" id="signupPasswordFeedback"></div>
            <div class="form-error"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="signupGender">Gender</label>
            <select class="form-select" id="signupGender" name="gender" required>
              <option value="">Select Gender</option>
              <option value="male">Male (Boys Hostel)</option>
              <option value="female">Female (Girls Hostel)</option>
            </select>
            <div class="form-hint">Hostel allocation is assigned automatically based on gender.</div>
            <div class="form-error"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="signupBranch">Branch / Discipline</label>
            <select class="form-select" id="signupBranch" name="branch" required>
              <option value="">Select Branch</option>
              <option value="Computer Science and Engineering">Computer Science &amp; Engineering (CSE)</option>
              <option value="Electronics and Communication Engineering">Electronics &amp; Communication (ECE)</option>
              <option value="Artificial Intelligence and Data Science">AI &amp; Data Science (AI &amp; DS)</option>
            </select>
            <div class="form-error"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="signupRoomNo">Allocated Room No.</label>
            <input class="form-input" id="signupRoomNo" name="room_no" type="text" placeholder="e.g. 101 or A-204" required autocomplete="off">
            <div class="form-error"></div>
          </div>

          <button type="submit" class="btn btn-primary" id="btnSignupSubmit" style="width: 100%; margin-top: var(--space-2);">
            Submit Registration
          </button>
        </form>
      </div>

      <!-- Tab 3: Staff Login Form -->
      <div class="auth-tab-pane" id="pane-staff-login" role="tabpanel">
        <form id="staffLoginForm">
          <div class="form-group">
            <label class="form-label" for="staffUsername">Staff Username</label>
            <input class="form-input" id="staffUsername" name="staff_username" type="text" placeholder="e.g. warden_boys or caretaker_girls" required autocomplete="username">
            <div class="form-error"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="staffCode">Secure Access Code</label>
            <input class="form-input" id="staffCode" name="code" type="password" placeholder="Access Code" required autocomplete="current-password">
            <div class="form-error"></div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-2);">
            Sign In as Staff
          </button>
        </form>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
  <script>
    // Tab Switcher
    function switchAuthTab(tabName) {
      document.querySelectorAll('.auth-tab-btn').forEach(btn => {
        const isActive = (btn.id === 'tab-' + tabName);
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      document.querySelectorAll('.auth-tab-pane').forEach(pane => {
        pane.classList.toggle('is-active', pane.id === 'pane-' + tabName);
      });
    }

    // Live Inline Regex Feedback for Student Registration ID (^20\d{2}ku[a-z]{2,5}\d{2,5}@(iiitkota|iiit\.kota)\.ac\.in$)
    const regexPattern = /^20\d{2}ku[a-z]{2,5}\d{2,5}@(iiitkota|iiit\.kota)\.ac\.in$/i;
    const signupIdInput = document.getElementById('signupCollegeId');
    const feedbackEl = document.getElementById('signupIdFeedback');
    const signupBtn = document.getElementById('btnSignupSubmit');

    if (signupIdInput && feedbackEl) {
      signupIdInput.addEventListener('input', () => {
        const val = signupIdInput.value.trim();
        if (val.length === 0) {
          feedbackEl.className = 'live-feedback';
          feedbackEl.textContent = '';
          signupBtn.disabled = false;
          return;
        }

        if (regexPattern.test(val)) {
          feedbackEl.className = 'live-feedback is-valid';
          feedbackEl.textContent = 'Valid IIIT Kota student email ID.';
          signupBtn.disabled = false;
        } else {
          feedbackEl.className = 'live-feedback is-invalid';
          feedbackEl.textContent = 'Format must be 20XXku<branch><roll>@iiit.kota.ac.in (e.g. 2025kucp1010@iiit.kota.ac.in)';
        }
      });
    }

    // Live Password Complexity Feedback (mirrors server-side rules in api/auth_signup.php)
    const passwordInput   = document.getElementById('signupPassword');
    const passwordFeedback = document.getElementById('signupPasswordFeedback');

    function checkPasswordComplexity(val) {
      if (val.length === 0) return { ok: null, msg: '' };
      if (val.length < 8)              return { ok: false, msg: 'Too short — minimum 8 characters.' };
      if (!/[A-Z]/.test(val))          return { ok: false, msg: 'Add at least one uppercase letter (A–Z).' };
      if (!/[a-z]/.test(val))          return { ok: false, msg: 'Add at least one lowercase letter (a–z).' };
      if (!/[0-9]/.test(val))          return { ok: false, msg: 'Add at least one digit (0–9).' };
      if (!/[^A-Za-z0-9]/.test(val))   return { ok: false, msg: 'Add at least one special character (@, #, !, $, …).' };
      return { ok: true, msg: 'Password meets all requirements.' };
    }

    if (passwordInput && passwordFeedback) {
      passwordInput.addEventListener('input', () => {
        const result = checkPasswordComplexity(passwordInput.value);
        if (result.ok === null) {
          passwordFeedback.className = 'live-feedback';
          passwordFeedback.textContent = '';
        } else if (result.ok) {
          passwordFeedback.className = 'live-feedback is-valid';
          passwordFeedback.textContent = result.msg;
        } else {
          passwordFeedback.className = 'live-feedback is-invalid';
          passwordFeedback.textContent = result.msg;
        }
      });
    }

    // Bind Student Login
    bindForm('#studentLoginForm', '../api/auth_login_student.php', (res) => {
      toast('Login successful. Redirecting to student portal...', 'success');
      setTimeout(() => {
        window.location.href = res.data.redirect || 'student.php';
      }, 300);
    });

    // Bind Student Signup
    bindForm('#studentSignupForm', '../api/auth_signup.php', (res) => {
      toast(res.data.message || 'Registration submitted successfully!', 'success');
      // Reset live feedback indicators
      if (feedbackEl) {
        feedbackEl.className = 'live-feedback';
        feedbackEl.textContent = '';
      }
      if (passwordFeedback) {
        passwordFeedback.className = 'live-feedback';
        passwordFeedback.textContent = '';
      }
      // Switch back to Student Login tab after successful registration
      setTimeout(() => {
        switchAuthTab('student-login');
      }, 1500);
    });

    // Bind Staff Login
    bindForm('#staffLoginForm', '../api/auth_login_staff.php', (res) => {
      toast('Staff login verified. Redirecting...', 'success');
      setTimeout(() => {
        window.location.href = res.data.redirect || 'staff.php';
      }, 300);
    });
  </script>
</body>
</html>

