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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=7">
  <style>
    .auth-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: var(--space-8) var(--space-4);
      background: radial-gradient(circle at 50% 15%, #3A3534 0%, #2B2726 85%);
    }

    /* Centered Emblem Logo and Institute Name */
    .auth-brand {
      text-align: center;
      margin-bottom: var(--space-6);
      display: flex;
      flex-direction: column;
      align-items: center;
      animation: logoFadeInSlideDown 500ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .brand-logo-badge {
      width: 76px;
      height: 76px;
      border-radius: 50%;
      background: #F3E9E2;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: var(--space-3);
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45), 0 0 0 2px var(--accent);
      overflow: hidden;
      padding: 4px;
      cursor: pointer;
    }

    /* Logo: rotate 360deg smoothly (0.8s ease) on hover (Requirement 1) */
    .brand-logo-img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      border-radius: 50%;
      transition: transform 0.8s ease;
      will-change: transform;
    }

    .brand-logo-badge:hover .brand-logo-img {
      transform: rotate(360deg);
    }

    .auth-brand h1 {
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: -0.015em;
      color: var(--text-main);
      max-width: 480px;
      margin: 0 auto;
      line-height: 1.35;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }

    .auth-brand p {
      font-size: var(--text-base);
      color: var(--text-secondary);
      margin-top: var(--space-2);
      font-weight: 500;
      letter-spacing: 0.02em;
    }

    .auth-card {
      width: 100%;
      max-width: 460px;
      background: linear-gradient(165deg, rgba(58, 53, 52, 0.98) 0%, rgba(43, 39, 38, 0.99) 100%);
      border: 1px solid rgba(166, 124, 99, 0.28);
      border-radius: var(--radius-xl);
      box-shadow: 0 20px 48px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.05);
      overflow: hidden;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      animation: fadeInSlideUp 500ms ease-out;
    }

    .auth-tabs {
      display: flex;
      background: var(--bg-subtle);
      border-bottom: 1px solid var(--border-hairline);
      padding: 6px;
      gap: 6px;
    }

    .auth-tab-btn {
      flex: 1;
      padding: 0.65rem 0.5rem;
      min-height: 44px;
      font-size: var(--text-sm);
      font-weight: var(--font-weight-medium);
      color: var(--text-secondary);
      background: transparent;
      border: 0;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: all var(--transition-fast);
      text-align: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .auth-tab-btn:hover {
      color: var(--text-main);
      background: rgba(166, 124, 99, 0.12);
    }

    .auth-tab-btn.is-active {
      color: var(--text-main);
      background: var(--accent);
      font-weight: var(--font-weight-semibold);
      box-shadow: 0 4px 12px rgba(114, 75, 57, 0.45);
    }

    .auth-tab-pane {
      display: none;
      padding: var(--space-6);
      opacity: 0;
      transform: translateY(8px);
    }

    .auth-tab-pane.is-active {
      display: block;
      opacity: 1;
      transform: translateY(0);
      animation: tabFadeIn 250ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .live-feedback {
      font-size: var(--text-xs);
      margin-top: var(--space-1);
      display: none;
      font-weight: var(--font-weight-medium);
    }

    .live-feedback.is-valid {
      color: var(--color-success);
      display: block;
    }

    .live-feedback.is-invalid {
      color: var(--color-danger);
      display: block;
    }

    @keyframes logoFadeInSlideDown {
      from {
        opacity: 0;
        transform: translateY(-18px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInSlideUp {
      from {
        opacity: 0;
        transform: translateY(14px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes tabFadeIn {
      from {
        opacity: 0;
        transform: translateY(8px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .auth-brand,
      .auth-card,
      .auth-tab-pane.is-active,
      .brand-logo-img {
        animation: none !important;
        transition: none !important;
        transform: none !important;
      }
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <!-- Centered Emblem Logo and Full College Name Above Card -->
    <div class="auth-brand">
      <div class="brand-logo-badge">
        <img src="images/iiitkota.webp" onerror="this.onerror=null; this.src='https://cdn.iiitkota.ac.in/site/iiitkota.webp';" alt="IIIT Kota logo" class="brand-logo-img">
      </div>
      <h1>Indian Institute of Information Technology, Kota</h1>
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
            <div class="password-wrapper">
              <input class="form-input" id="loginPassword" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
              <button type="button" class="password-toggle-btn" data-target="loginPassword" aria-label="Show password">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
            <div class="form-error"></div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-3);">
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
            <div class="password-wrapper">
              <input class="form-input" id="signupPassword" name="password" type="password" minlength="8" placeholder="Min 8 chars, e.g. Hostel@2025" required autocomplete="new-password">
              <button type="button" class="password-toggle-btn" data-target="signupPassword" aria-label="Show password">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
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

          <button type="submit" class="btn btn-primary" id="btnSignupSubmit" style="width: 100%; margin-top: var(--space-3);">
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
            <div class="password-wrapper">
              <input class="form-input" id="staffCode" name="code" type="password" placeholder="Access Code" required autocomplete="current-password">
              <button type="button" class="password-toggle-btn" data-target="staffCode" aria-label="Show password">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
            <div class="form-error"></div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-3);">
            Sign In as Staff
          </button>
        </form>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js?v=7"></script>
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
