/**
 * IIIT Kota Hostel Management System
 * Shared Vanilla JavaScript Core Foundation
 * Target: XAMPP, PHP 8+, Vanilla Modern Web
 */

'use strict';

/**
 * Global CSRF & API Fetcher Contract
 * Automatically attaches X-CSRF-Token from <meta name="csrf"> or <meta name="csrf-token">.
 * Parses {ok: bool, data?: any, error?: string}.
 * Toasts on failure and throws on !ok.
 *
 * @param {string} url Endpoint URL
 * @param {RequestInit} [opts={}] Fetch options
 * @returns {Promise<{ok: boolean, data?: any, error?: string}>}
 */
async function fetchJSON(url, opts = {}) {
  const headers = new Headers(opts.headers || {});

  // Extract CSRF token from page meta tag
  const metaCsrf = document.querySelector('meta[name="csrf"]')?.getAttribute('content')
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '';

  if (metaCsrf && !headers.has('X-CSRF-Token')) {
    headers.set('X-CSRF-Token', metaCsrf);
  }

  if (!headers.has('Accept')) {
    headers.set('Accept', 'application/json');
  }

  // Automatically serialize object bodies to JSON if not FormData or URLSearchParams
  let body = opts.body;
  if (body && typeof body === 'object' && !(body instanceof FormData) && !(body instanceof URLSearchParams)) {
    if (!headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json; charset=utf-8');
    }
    body = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetch(url, {
      ...opts,
      headers,
      body,
    });
  } catch (networkErr) {
    const errorMsg = 'Network connection failed. Please check your connection.';
    toast(errorMsg, 'error');
    throw new Error(errorMsg);
  }

  // Parse JSON response according to contract {ok: bool, data?: any, error?: string}
  let json;
  try {
    json = await response.json();
  } catch (parseErr) {
    const errorMsg = `Server returned an invalid response (${response.status})`;
    toast(errorMsg, 'error');
    const err = new Error(errorMsg);
    err.status = response.status;
    throw err;
  }

  if (!response.ok || !json.ok) {
    const errorMsg = (json && json.error) ? json.error : `Request failed (${response.status})`;
    toast(errorMsg, 'error');
    const err = new Error(errorMsg);
    err.status = response.status;
    err.response = json;
    throw err;
  }

  return json;
}

/**
 * Toast Notification Contract
 * Slides in, auto-dismisses after 3 seconds.
 *
 * @param {string} msg Message to display (escaped safely via textContent)
 * @param {'success'|'error'|'info'} [type='success'] Notification variant
 */
function toast(msg, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
  }

  const toastEl = document.createElement('div');
  toastEl.className = `toast toast-${type}`;
  toastEl.setAttribute('role', 'alert');

  // Inline SVG icons based on variant (No emojis)
  let iconSvg = '';
  if (type === 'success') {
    iconSvg = '<svg class="icon toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
  } else if (type === 'error') {
    iconSvg = '<svg class="icon toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>';
  } else {
    iconSvg = '<svg class="icon toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
  }

  toastEl.innerHTML = iconSvg;

  const msgSpan = document.createElement('span');
  msgSpan.className = 'toast-message';
  msgSpan.textContent = String(msg || '');
  toastEl.appendChild(msgSpan);

  let timer = null;

  function dismissToast(el) {
    if (timer) clearTimeout(timer);
    el.classList.add('toast-hiding');
    setTimeout(() => {
      if (el.parentElement) {
        el.parentElement.removeChild(el);
      }
    }, 200);
  }

  // Manual dismiss on click
  toastEl.addEventListener('click', () => dismissToast(toastEl));

  container.appendChild(toastEl);

  // Auto-dismiss after 3000ms
  timer = setTimeout(() => {
    dismissToast(toastEl);
  }, 3000);
}

/**
 * Modal Dialog Open Contract
 * Fades and scales in modal, activates backdrop, prevents body scrolling.
 *
 * @param {string} id Element ID of the modal backdrop or inner modal
 */
function openModal(id) {
  const el = document.getElementById(id);
  if (!el) return;

  const backdrop = el.classList.contains('modal-backdrop')
    ? el
    : el.closest('.modal-backdrop');

  if (!backdrop) return;

  backdrop.classList.add('is-active');
  document.body.style.overflow = 'hidden';

  // Trap escape key to close
  const onKeyDown = (e) => {
    if (e.key === 'Escape') {
      closeModal();
      document.removeEventListener('keydown', onKeyDown);
    }
  };
  document.addEventListener('keydown', onKeyDown);

  // Close on backdrop overlay click
  backdrop.onclick = (e) => {
    if (e.target === backdrop) {
      closeModal();
    }
  };
}

/**
 * Modal Dialog Close Contract
 * Fades out all active modals and restores body scrolling.
 */
function closeModal() {
  const activeBackdrops = document.querySelectorAll('.modal-backdrop.is-active');
  activeBackdrops.forEach((b) => {
    b.classList.remove('is-active');
  });
  document.body.style.overflow = '';
}

/**
 * AJAX Form Binding Contract
 * Handles submit, FormData, button loading state, inline error display, resets on success.
 *
 * @param {HTMLFormElement|string} formEl Form element or CSS selector
 * @param {string} endpoint Target API URL
 * @param {Function} onSuccess Callback on successful response
 */
function bindForm(formEl, endpoint, onSuccess) {
  const form = typeof formEl === 'string' ? document.querySelector(formEl) : formEl;
  if (!form || !(form instanceof HTMLFormElement)) {
    console.warn('bindForm: Invalid form element provided.', formEl);
    return;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Reset previous inline errors
    form.querySelectorAll('.form-group.has-error').forEach((group) => {
      group.classList.remove('has-error');
      const errEl = group.querySelector('.form-error');
      if (errEl) errEl.textContent = '';
    });

    const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('button.btn-primary');
    if (submitBtn) {
      submitBtn.classList.add('is-loading');
      submitBtn.disabled = true;
    }

    try {
      const formData = new FormData(form);
      const result = await fetchJSON(endpoint, {
        method: 'POST',
        body: formData,
      });

      // Call success handler
      if (typeof onSuccess === 'function') {
        onSuccess(result, form);
      }

      form.reset();
    } catch (err) {
      // Highlight inline field errors if provided in error payload data
      if (err.response && err.response.data && typeof err.response.data === 'object') {
        for (const [fieldName, errorText] of Object.entries(err.response.data)) {
          const input = form.querySelector(`[name="${fieldName}"]`);
          if (input) {
            const group = input.closest('.form-group') || input.parentElement;
            if (group) {
              group.classList.add('has-error');
              let errorEl = group.querySelector('.form-error');
              if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'form-error';
                group.appendChild(errorEl);
              }
              errorEl.textContent = String(errorText);
            }
          }
        }
      }
    } finally {
      if (submitBtn) {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
      }
    }
  });
}

/**
 * Sidebar Tab Section Loader Contract
 * Fetches HTML or JSON content for a sidebar tab and fades it smoothly into #content.
 *
 * @param {string} name Section identifier (e.g. 'complaints', 'mess', 'notices')
 * @param {string} [customUrl] Optional custom URL override
 */
async function loadSection(name, customUrl) {
  const contentEl = document.getElementById('content');
  if (!contentEl) return;

  // Update active navigation state in sidebar
  document.querySelectorAll('.sidebar .nav-link[data-section]').forEach((tab) => {
    tab.classList.toggle('is-active', tab.dataset.section === name);
  });

  // Check if a client-side template exists (useful for component demo or cached tabs)
  const templateEl = document.getElementById(`template-${name}`);
  if (templateEl) {
    contentEl.classList.remove('fade-in');
    void contentEl.offsetWidth; // Force reflow to restart animation
    contentEl.innerHTML = templateEl.innerHTML;
    contentEl.classList.add('fade-in');
    initPasswordToggles();
    document.dispatchEvent(new CustomEvent('sectionLoaded', { detail: { name } }));
    return;
  }

  // Otherwise fetch from endpoint
  const url = customUrl || `api/section.php?name=${encodeURIComponent(name)}`;

  contentEl.classList.remove('fade-in');
  void contentEl.offsetWidth;

  try {
    const res = await fetch(url, {
      headers: { Accept: 'text/html, application/json' },
    });

    if (!res.ok) {
      throw new Error(`Failed to load ${name} (${res.status})`);
    }

    const contentType = res.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || 'Failed to load section');
      contentEl.innerHTML = json.html || json.data || '';
    } else {
      const html = await res.text();
      contentEl.innerHTML = html;
    }

    contentEl.classList.add('fade-in');
    initPasswordToggles();
    document.dispatchEvent(new CustomEvent('sectionLoaded', { detail: { name } }));
  } catch (err) {
    toast(err.message || 'Error loading section', 'error');
  }
}

// ------------------------------------------------------------------------------
// Interactive Helpers: Show/Hide Password & Mobile Sidebar Navigation
// ------------------------------------------------------------------------------
function initPasswordToggles() {
  document.querySelectorAll('.password-toggle-btn').forEach((btn) => {
    if (btn.dataset.initialized) return;
    btn.dataset.initialized = 'true';
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;

      const isPassword = (input.type === 'password');
      input.type = isPassword ? 'text' : 'password';
      btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      btn.innerHTML = isPassword
        ? `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initPasswordToggles();

  // Sticky header soft shadow once page is scrolled (Requirement 2)
  const appTopHeader = document.getElementById('appTopHeader') || document.querySelector('.app-top-header');
  if (appTopHeader) {
    const updateHeaderShadow = () => {
      if (window.scrollY > 8) {
        appTopHeader.classList.add('is-scrolled');
      } else {
        appTopHeader.classList.remove('is-scrolled');
      }
    };
    window.addEventListener('scroll', updateHeaderShadow, { passive: true });
    updateHeaderShadow();
  }

  // Set data-label attributes for stacked table cards on phones (Requirement 7)
  const applyTableLabels = (root = document) => {
    root.querySelectorAll('table').forEach((table) => {
      const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
      if (headers.length === 0) return;
      table.querySelectorAll('tbody tr').forEach((tr) => {
        const cells = tr.querySelectorAll('td');
        cells.forEach((td, idx) => {
          if (!td.getAttribute('data-label') && headers[idx]) {
            td.setAttribute('data-label', headers[idx]);
          }
        });
      });
    });
  };

  applyTableLabels();

  // Watch for dynamic table updates across all sections
  if (typeof MutationObserver !== 'undefined' && document.body) {
    const tableObserver = new MutationObserver(() => {
      applyTableLabels(document);
    });
    tableObserver.observe(document.body, { childList: true, subtree: true });
  }

  const mobileToggle = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');

  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener('click', () => {
      sidebar.classList.toggle('is-open');
    });
  }

  if (backdrop && sidebar) {
    backdrop.addEventListener('click', () => {
      sidebar.classList.remove('is-open');
    });
  }

  // Auto-close sidebar on mobile after selecting a navigation tab
  document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.remove('is-open');
      }
    });
  });
});

// ------------------------------------------------------------------------------
// Student Portal Module (Dashboard & Complaints)
// ------------------------------------------------------------------------------
let dashboardNoticeTimer = null;
let complaintsPollTimer = null;
let latestNoticeId = 0;

function clearStudentTimers() {
  if (dashboardNoticeTimer) { clearInterval(dashboardNoticeTimer); dashboardNoticeTimer = null; }
  if (complaintsPollTimer) { clearInterval(complaintsPollTimer); complaintsPollTimer = null; }
}

/**
 * Initializes Student Overview Dashboard with 30s Notice Board Polling
 */
async function initStudentDashboard() {
  clearStudentTimers();

  const noticeContainer = document.getElementById('dashboardNoticesList');
  if (!noticeContainer) return;

  async function fetchNotices() {
    try {
      const res = await fetchJSON('../api/notice_list.php');
      if (res.ok && Array.isArray(res.data)) {
        renderNoticeList(res.data, noticeContainer);
      }
    } catch (err) {
      console.warn('Dashboard notice poll failed:', err.message);
    }
  }

  // Initial load
  await fetchNotices();

  // 30-second polling for notice board updates
  dashboardNoticeTimer = setInterval(fetchNotices, 30000);
}

/**
 * Renders notice list safely with textContent escaping
 */
function renderNoticeList(notices, container) {
  if (!container) return;
  container.innerHTML = '';

  if (notices.length === 0) {
    container.innerHTML = `
      <div style="padding: var(--space-6); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">
        No announcements posted at this time.
      </div>
    `;
    return;
  }

  notices.forEach((notice) => {
    if (notice.id > latestNoticeId) latestNoticeId = notice.id;

    const item = document.createElement('div');
    item.className = 'notice-item';
    item.style.padding = 'var(--space-3) var(--space-4)';
    item.style.borderBottom = '1px solid var(--border-hairline)';
    item.style.display = 'flex';
    item.style.flexDirection = 'column';
    item.style.gap = 'var(--space-1)';

    const header = document.createElement('div');
    header.style.display = 'flex';
    header.style.justifyContent = 'space-between';
    header.style.fontSize = 'var(--text-xs)';

    const author = document.createElement('span');
    author.style.fontWeight = 'var(--font-weight-medium)';
    author.style.color = 'var(--accent)';
    author.textContent = `${notice.posted_by_name} (${notice.posted_by_role ? notice.posted_by_role.charAt(0).toUpperCase() + notice.posted_by_role.slice(1) : 'Staff'})`;

    const time = document.createElement('span');
    time.style.color = 'var(--text-muted)';
    time.textContent = notice.created_at;

    header.appendChild(author);
    header.appendChild(time);

    const body = document.createElement('div');
    body.style.fontSize = 'var(--text-sm)';
    body.style.color = 'var(--text-main)';
    body.style.lineHeight = '1.45';
    body.textContent = notice.message;

    item.appendChild(header);
    item.appendChild(body);
    container.appendChild(item);
  });
}

/**
 * Initializes Complaints Section (AJAX Form, Image Preview, Table & 20s Polling)
 */
async function initComplaintsSection() {
  clearStudentTimers();

  const form = document.getElementById('complaintForm');
  const tableBody = document.getElementById('complaintsTableBody');
  const fileInput = document.getElementById('complaintImageInput');
  const previewBox = document.getElementById('complaintPreviewBox');
  const previewImg = document.getElementById('complaintPreviewImg');
  const removeFileBtn = document.getElementById('complaintRemoveImgBtn');

  // Image Preview Logic
  if (fileInput && previewBox && previewImg) {
    fileInput.onchange = () => {
      const file = fileInput.files[0];
      if (!file) {
        previewBox.style.display = 'none';
        previewImg.src = '';
        return;
      }

      // Check max size 2MB
      if (file.size > 2 * 1024 * 1024) {
        toast('Image exceeds 2MB limit. Please choose a smaller image.', 'error');
        fileInput.value = '';
        previewBox.style.display = 'none';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        previewImg.src = e.target.result;
        previewBox.style.display = 'flex';
      };
      reader.readAsDataURL(file);
    };

    if (removeFileBtn) {
      removeFileBtn.onclick = () => {
        fileInput.value = '';
        previewBox.style.display = 'none';
        previewImg.src = '';
      };
    }
  }

  // Complaints Table Loader & Synchronizer
  async function loadComplaints() {
    try {
      const res = await fetchJSON('../api/complaint_list.php');
      if (res.ok && Array.isArray(res.data)) {
        renderComplaintsTable(res.data);
      }
    } catch (err) {
      console.warn('Complaints load failed:', err.message);
    }
  }

  // Form Binding via fetchJSON
  if (form) {
    form.onsubmit = async (e) => {
      e.preventDefault();

      form.querySelectorAll('.has-error').forEach((g) => g.classList.remove('has-error'));
      form.querySelectorAll('.form-error').forEach((err) => { err.textContent = ''; });

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.add('is-loading');
        submitBtn.disabled = true;
      }

      try {
        const formData = new FormData(form);
        const res = await fetchJSON('../api/complaint_create.php', {
          method: 'POST',
          body: formData,
        });

        toast(res.data?.message || 'Complaint submitted successfully!', 'success');
        form.reset();
        if (previewBox) previewBox.style.display = 'none';
        if (previewImg) previewImg.src = '';

        // Reload complaints list immediately
        await loadComplaints();
      } catch (err) {
        if (err.response && err.response.data && typeof err.response.data === 'object') {
          for (const [field, msg] of Object.entries(err.response.data)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
              const group = input.closest('.form-group') || input.parentElement;
              if (group) {
                group.classList.add('has-error');
                let errEl = group.querySelector('.form-error');
                if (!errEl) {
                  errEl = document.createElement('div');
                  errEl.className = 'form-error';
                  group.appendChild(errEl);
                }
                errEl.textContent = String(msg);
              }
            }
          }
        }
      } finally {
        if (submitBtn) {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;
        }
      }
    };
  }

  // Initial load
  await loadComplaints();

  // 20-second status polling
  complaintsPollTimer = setInterval(loadComplaints, 20000);
}

/**
 * Renders complaints table with badge color transitions
 */
function renderComplaintsTable(complaints) {
  const tableBody = document.getElementById('complaintsTableBody');
  const emptyState = document.getElementById('complaintsEmptyState');
  const tableContainer = document.getElementById('complaintsTableContainer');

  if (!tableBody) return;

  if (complaints.length === 0) {
    if (tableContainer) tableContainer.style.display = 'none';
    if (emptyState) emptyState.style.display = 'block';
    return;
  }

  if (tableContainer) tableContainer.style.display = 'block';
  if (emptyState) emptyState.style.display = 'none';

  complaints.forEach((c) => {
    let row = document.getElementById(`complaint-row-${c.id}`);

    if (row) {
      // Row exists: check if status changed to apply animated transition
      const badge = row.querySelector('.badge');
      if (badge && !badge.classList.contains(`badge-${c.status}`)) {
        badge.className = `badge badge-${c.status}`;
        badge.textContent = c.status.charAt(0).toUpperCase() + c.status.slice(1);
      }
    } else {
      // New row creation
      row = document.createElement('tr');
      row.id = `complaint-row-${c.id}`;

      // Ticket ID
      const tdId = document.createElement('td');
      tdId.style.fontFamily = 'var(--font-mono)';
      tdId.style.fontWeight = 'var(--font-weight-medium)';
      tdId.textContent = `#${c.id}`;

      // Category
      const tdCat = document.createElement('td');
      tdCat.textContent = c.category;

      // Description
      const tdDesc = document.createElement('td');
      tdDesc.style.maxWidth = '260px';
      tdDesc.style.whiteSpace = 'nowrap';
      tdDesc.style.overflow = 'hidden';
      tdDesc.style.textOverflow = 'ellipsis';
      tdDesc.textContent = c.description;

      // Attachment
      const tdMedia = document.createElement('td');
      if (c.has_image) {
        const btnMedia = document.createElement('button');
        btnMedia.type = 'button';
        btnMedia.className = 'btn btn-secondary btn-sm';
        btnMedia.textContent = 'View Photo';
        btnMedia.onclick = () => viewComplaintMedia(c.id);
        tdMedia.appendChild(btnMedia);
      } else {
        tdMedia.textContent = 'None';
        tdMedia.style.color = 'var(--text-muted)';
        tdMedia.style.fontSize = 'var(--text-xs)';
      }

      // Date
      const tdDate = document.createElement('td');
      tdDate.style.fontSize = 'var(--text-xs)';
      tdDate.style.color = 'var(--text-muted)';
      tdDate.textContent = c.created_at ? c.created_at.split(' ')[0] : '';

      // Status Badge
      const tdStatus = document.createElement('td');
      const badge = document.createElement('span');
      badge.className = `badge badge-${c.status}`;
      badge.textContent = c.status.charAt(0).toUpperCase() + c.status.slice(1);
      tdStatus.appendChild(badge);

      row.appendChild(tdId);
      row.appendChild(tdCat);
      row.appendChild(tdDesc);
      row.appendChild(tdMedia);
      row.appendChild(tdDate);
      row.appendChild(tdStatus);

      tableBody.appendChild(row);
    }
  });
}

/**
 * Streams complaint attachment in secure modal dialog
 */
function viewComplaintMedia(complaintId) {
  const modalImg = document.getElementById('modalComplaintImg');
  const modalTitle = document.getElementById('modalImageTitle');
  if (modalTitle) modalTitle.textContent = 'Complaint Photo Attachment';
  if (modalImg) {
    modalImg.src = `../api/media.php?id=${complaintId}&type=complaint`;
  }
  openModal('complaintImageModal');
}

/**
 * Streams lost & found attachment in secure modal dialog
 */
function viewLostFoundMedia(itemId) {
  const modalImg = document.getElementById('modalComplaintImg');
  const modalTitle = document.getElementById('modalImageTitle');
  if (modalTitle) modalTitle.textContent = 'Lost & Found Photo';
  if (modalImg) {
    modalImg.src = `../api/media.php?id=${itemId}&type=lostfound`;
  }
  openModal('complaintImageModal');
}

/**
 * Initializes Mess Menu & Anonymous Feedback Module
 */
async function initMessSection() {
  clearStudentTimers();

  const tbody = document.getElementById('messMenuTableBody');
  const badge = document.getElementById('todayDayBadge');
  if (!tbody) return;

  try {
    const res = await fetchJSON('../api/mess_menu.php');
    if (!res.ok || !res.data) return;

    const { today_day, today_date, rated_meals, menu } = res.data;

    if (badge) {
      badge.textContent = `Today: ${today_day.toUpperCase()} (${today_date})`;
    }

    tbody.innerHTML = '';

    const starSvg = '<svg viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem; fill: currentColor;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';

    menu.forEach((meal) => {
      const isToday = (meal.day === today_day);
      const isRated = rated_meals.includes(meal.id);

      const tr = document.createElement('tr');
      if (isToday) {
        tr.style.backgroundColor = 'var(--accent-subtle)';
        tr.style.fontWeight = 'var(--font-weight-medium)';
      }

      // Day
      const tdDay = document.createElement('td');
      tdDay.textContent = meal.day;
      if (isToday) {
        const todayTag = document.createElement('span');
        todayTag.className = 'badge badge-in-progress';
        todayTag.style.fontSize = '10px';
        todayTag.style.marginLeft = 'var(--space-2)';
        todayTag.textContent = 'TODAY';
        tdDay.appendChild(todayTag);
      }

      // Meal Type
      const tdMeal = document.createElement('td');
      tdMeal.style.textTransform = 'capitalize';
      tdMeal.textContent = meal.meal_type;

      // Menu Items
      const tdItems = document.createElement('td');
      tdItems.textContent = meal.items;

      // Rating Column
      const tdRating = document.createElement('td');
      tdRating.style.textAlign = 'right';

      if (!isToday) {
        tdRating.innerHTML = `<span style="font-size: var(--text-xs); color: var(--text-muted);">Available ${meal.day}</span>`;
      } else if (isRated) {
        tdRating.innerHTML = `
          <div style="display: inline-flex; align-items: center; gap: var(--space-2);">
            <span class="badge badge-resolved">Rated</span>
            <div class="star-rating" style="opacity: 0.6; pointer-events: none;">
              ${'<span style="color: #f5a623;">' + starSvg + '</span>'.repeat(5)}
            </div>
          </div>
        `;
      } else {
        // Active 5-star interactive rating for today
        const ratingContainer = document.createElement('div');
        ratingContainer.className = 'star-rating';
        ratingContainer.id = `rating-box-${meal.id}`;

        for (let starVal = 1; starVal <= 5; starVal++) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'star-rating-btn';
          btn.dataset.rating = String(starVal);
          btn.dataset.meal = String(meal.id);
          btn.setAttribute('aria-label', `${starVal} Stars`);
          btn.innerHTML = starSvg;

          // Star click handler
          btn.onclick = async () => {
            ratingContainer.querySelectorAll('button').forEach((b) => { b.disabled = true; });
            await submitMessRating(meal.id, starVal, tdRating);
          };

          ratingContainer.appendChild(btn);
        }

        tdRating.appendChild(ratingContainer);
      }

      tr.appendChild(tdDay);
      tr.appendChild(tdMeal);
      tr.appendChild(tdItems);
      tr.appendChild(tdRating);

      tbody.appendChild(tr);
    });
  } catch (err) {
    console.warn('Failed to load mess menu:', err.message);
  }
}

/**
 * Submits anonymous mess feedback and locks rating component
 */
async function submitMessRating(mealId, rating, containerEl) {
  try {
    const res = await fetchJSON('../api/mess_feedback.php', {
      method: 'POST',
      body: { meal_id: mealId, rating: rating },
    });

    toast(res.data?.message || 'Thank you! Rating recorded anonymously.', 'success');

    const starSvg = '<svg viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem; fill: currentColor;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';

    // Render locked state
    containerEl.innerHTML = `
      <div style="display: inline-flex; align-items: center; gap: var(--space-2);">
        <span class="badge badge-resolved">Rated</span>
        <div class="star-rating" style="opacity: 0.6; pointer-events: none;">
          ${'<span style="color: #f5a623;">' + starSvg + '</span>'.repeat(rating)}
        </div>
      </div>
    `;
  } catch (err) {
    // Error handled by fetchJSON toast
  }
}

/**
 * Lost & Found Module: State & Table Rendering
 */
let lostFoundItems = [];
let currentLostFoundFilter = 'all';

async function initLostFoundSection() {
  clearStudentTimers();

  const form = document.getElementById('lostFoundForm');
  const fileInput = document.getElementById('lfImageInput');
  const previewBox = document.getElementById('lfPreviewBox');
  const previewImg = document.getElementById('lfPreviewImg');
  const removeFileBtn = document.getElementById('lfRemoveImgBtn');

  // Image Preview Logic
  if (fileInput && previewBox && previewImg) {
    fileInput.onchange = () => {
      const file = fileInput.files[0];
      if (!file) {
        previewBox.style.display = 'none';
        previewImg.src = '';
        return;
      }

      if (file.size > 2 * 1024 * 1024) {
        toast('Image exceeds 2MB limit. Please choose a smaller image.', 'error');
        fileInput.value = '';
        previewBox.style.display = 'none';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        previewImg.src = e.target.result;
        previewBox.style.display = 'flex';
      };
      reader.readAsDataURL(file);
    };

    if (removeFileBtn) {
      removeFileBtn.onclick = () => {
        fileInput.value = '';
        previewBox.style.display = 'none';
        previewImg.src = '';
      };
    }
  }

  // Bind Form Submission
  if (form) {
    form.onsubmit = async (e) => {
      e.preventDefault();

      form.querySelectorAll('.has-error').forEach((g) => g.classList.remove('has-error'));
      form.querySelectorAll('.form-error').forEach((err) => { err.textContent = ''; });

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.add('is-loading');
        submitBtn.disabled = true;
      }

      try {
        const formData = new FormData(form);
        const res = await fetchJSON('../api/lostfound_create.php', {
          method: 'POST',
          body: formData,
        });

        toast(res.data?.message || 'Listing published successfully!', 'success');
        form.reset();
        if (previewBox) previewBox.style.display = 'none';
        if (previewImg) previewImg.src = '';

        // Reload listings immediately
        await loadLostFoundItems();
      } catch (err) {
        if (err.response && err.response.data && typeof err.response.data === 'object') {
          for (const [field, msg] of Object.entries(err.response.data)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
              const group = input.closest('.form-group') || input.parentElement;
              if (group) {
                group.classList.add('has-error');
                let errEl = group.querySelector('.form-error');
                if (!errEl) {
                  errEl = document.createElement('div');
                  errEl.className = 'form-error';
                  group.appendChild(errEl);
                }
                errEl.textContent = String(msg);
              }
            }
          }
        }
      } finally {
        if (submitBtn) {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;
        }
      }
    };
  }

  // Load items
  await loadLostFoundItems();
}

async function loadLostFoundItems() {
  try {
    const res = await fetchJSON('../api/lostfound_list.php');
    if (res.ok && Array.isArray(res.data)) {
      lostFoundItems = res.data;
      renderLostFoundTable();
    }
  } catch (err) {
    console.warn('Failed to load lost and found items:', err.message);
  }
}

function renderLostFoundTable() {
  const tableBody = document.getElementById('lostFoundTableBody');
  const tableContainer = document.getElementById('lostFoundTableContainer');
  const emptyState = document.getElementById('lostFoundEmptyState');

  if (!tableBody) return;

  const filtered = lostFoundItems.filter((item) => {
    if (currentLostFoundFilter === 'lost') return item.type === 'lost';
    if (currentLostFoundFilter === 'found') return item.type === 'found';
    if (currentLostFoundFilter === 'active') return item.status === 'active';
    return true; // 'all'
  });

  if (filtered.length === 0) {
    if (tableContainer) tableContainer.style.display = 'none';
    if (emptyState) emptyState.style.display = 'block';
    return;
  }

  if (tableContainer) tableContainer.style.display = 'block';
  if (emptyState) emptyState.style.display = 'none';

  tableBody.innerHTML = '';

  filtered.forEach((item) => {
    const tr = document.createElement('tr');
    tr.id = `lf-row-${item.id}`;

    // Type Badge (Lost = Red/Amber, Found = Green)
    const tdType = document.createElement('td');
    const typeBadge = document.createElement('span');
    typeBadge.className = (item.type === 'found') ? 'badge badge-approved' : 'badge badge-rejected';
    typeBadge.textContent = item.type.toUpperCase();
    tdType.appendChild(typeBadge);

    // Description
    const tdDesc = document.createElement('td');
    tdDesc.textContent = item.item_description;

    // Photo
    const tdPhoto = document.createElement('td');
    if (item.has_image) {
      const btnPhoto = document.createElement('button');
      btnPhoto.type = 'button';
      btnPhoto.className = 'btn btn-secondary btn-sm';
      btnPhoto.textContent = 'View Photo';
      btnPhoto.onclick = () => viewLostFoundMedia(item.id);
      tdPhoto.appendChild(btnPhoto);
    } else {
      tdPhoto.textContent = 'None';
      tdPhoto.style.color = 'var(--text-muted)';
      tdPhoto.style.fontSize = 'var(--text-xs)';
    }

    // Date
    const tdDate = document.createElement('td');
    tdDate.style.fontSize = 'var(--text-xs)';
    tdDate.style.color = 'var(--text-muted)';
    tdDate.textContent = item.date_logged;

    // Contact
    const tdContact = document.createElement('td');
    tdContact.style.fontSize = 'var(--text-xs)';
    tdContact.innerHTML = `
      <div style="font-weight: var(--font-weight-medium); color: var(--text-main);">${item.contact_email}</div>
      <div style="color: var(--text-muted);">Room ${item.contact_room || 'N/A'}</div>
    `;

    // Status Badge
    const tdStatus = document.createElement('td');
    const statusBadge = document.createElement('span');
    statusBadge.id = `lf-status-${item.id}`;
    statusBadge.className = (item.status === 'resolved') ? 'badge badge-resolved' : 'badge badge-pending';
    statusBadge.textContent = item.status.charAt(0).toUpperCase() + item.status.slice(1);
    tdStatus.appendChild(statusBadge);

    // Action (Only owner can mark resolved)
    const tdAction = document.createElement('td');
    tdAction.style.textAlign = 'right';

    if (item.is_owner && item.status === 'active') {
      const resolveBtn = document.createElement('button');
      resolveBtn.type = 'button';
      resolveBtn.className = 'btn btn-secondary btn-sm';
      resolveBtn.textContent = 'Mark Resolved';
      resolveBtn.onclick = () => resolveLostFoundItem(item.id, tr, resolveBtn);
      tdAction.appendChild(resolveBtn);
    }

    tr.appendChild(tdType);
    tr.appendChild(tdDesc);
    tr.appendChild(tdPhoto);
    tr.appendChild(tdDate);
    tr.appendChild(tdContact);
    tr.appendChild(tdStatus);
    tr.appendChild(tdAction);

    tableBody.appendChild(tr);
  });
}

/**
 * Marks lost/found listing as resolved
 */
async function resolveLostFoundItem(id, rowEl, btnEl) {
  if (btnEl) {
    btnEl.classList.add('is-loading');
    btnEl.disabled = true;
  }

  try {
    const res = await fetchJSON('../api/lostfound_resolve.php', {
      method: 'POST',
      body: { id },
    });

    toast(res.data?.message || 'Item marked as resolved.', 'success');

    // Update in-memory item
    const item = lostFoundItems.find((i) => i.id === id);
    if (item) item.status = 'resolved';

    // Smooth UI transition: update badge and fade out button
    const badgeEl = document.getElementById(`lf-status-${id}`);
    if (badgeEl) {
      badgeEl.className = 'badge badge-resolved';
      badgeEl.textContent = 'Resolved';
    }

    if (btnEl) {
      btnEl.style.transition = 'opacity var(--transition-fast)';
      btnEl.style.opacity = '0';
      setTimeout(() => { if (btnEl.parentElement) btnEl.parentElement.innerHTML = ''; }, 200);
    }
  } catch (err) {
    if (btnEl) {
      btnEl.classList.remove('is-loading');
      btnEl.disabled = false;
    }
  }
}

/**
 * Filter button switcher for Lost & Found
 */
function filterLostFound(filter) {
  currentLostFoundFilter = filter;
  const group = document.getElementById('lfFilterButtonGroup');
  if (group) {
    group.querySelectorAll('.btn').forEach((b) => b.classList.remove('is-active'));
    const activeBtn = document.getElementById(`lfFilter${filter.charAt(0).toUpperCase() + filter.slice(1)}`);
    if (activeBtn) activeBtn.classList.add('is-active');
  }
  renderLostFoundTable();
}

// Section Loaded Router for Student & Staff Portals
document.addEventListener('sectionLoaded', (e) => {
  const sectionName = e.detail?.name;
  if (sectionName !== 'lost_found') {
    stopStaffLFTimer();
  }
  if (sectionName === 'overview') {
    initStudentDashboard();
  } else if (sectionName === 'complaints') {
    if (document.getElementById('staffComplaintTableBody')) {
      initStaffComplaints();
    } else {
      initComplaintsSection();
    }
  } else if (sectionName === 'mess') {
    if (document.getElementById('staffMessAnalyticsTableBody')) {
      initStaffMessSection();
    } else {
      initMessSection();
    }
  } else if (sectionName === 'lost_found') {
    if (document.getElementById('staffLFTableBody')) {
      initStaffLostFound();
    } else {
      initLostFoundSection();
    }
  } else if (sectionName === 'approvals') {
    initStaffApprovals();
  } else if (sectionName === 'notices') {
    if (document.getElementById('staffNoticeForm')) {
      initStaffNotices();
    }
  } else {
    clearStudentTimers();
  }
});

// ------------------------------------------------------------------------------
// Staff Portal: Lost & Found Management
// ------------------------------------------------------------------------------
let staffLFItems = [];
let staffLFTimer = null;

function stopStaffLFTimer() {
  if (staffLFTimer) {
    clearInterval(staffLFTimer);
    staffLFTimer = null;
  }
}

// Auto-pause timer when browser tab is hidden, resume when visible if on LF desk
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    stopStaffLFTimer();
  } else if (document.getElementById('staffLFTableBody')) {
    stopStaffLFTimer();
    staffLFTimer = setInterval(() => refreshStaffLF(true), 30000);
  }
});

async function initStaffLostFound() {
  stopStaffLFTimer();

  const tableBody      = document.getElementById('staffLFTableBody');
  const tableContainer = document.getElementById('staffLFTableContainer');
  const emptyState     = document.getElementById('staffLFEmptyState');
  const loadingEl      = document.getElementById('staffLFLoading');

  if (!tableBody) return;

  tableBody.innerHTML = '';
  if (tableContainer) tableContainer.style.display = 'none';
  if (emptyState)     emptyState.style.display = 'none';
  if (loadingEl)      loadingEl.style.display = 'block';

  try {
    const res = await fetchJSON('../api/staff_lostfound_list.php');
    if (loadingEl) loadingEl.style.display = 'none';

    if (!res.ok || !Array.isArray(res.data)) {
      toast(res.error || 'Failed to load lost and found listings.', 'error');
      return;
    }

    staffLFItems = res.data;
    applyStaffLFFilters();

    // Auto-refresh every 30 seconds while section is open
    staffLFTimer = setInterval(() => refreshStaffLF(true), 30000);
  } catch (err) {
    if (loadingEl) loadingEl.style.display = 'none';
    toast('Network error loading lost and found.', 'error');
  }
}

async function refreshStaffLF(silent = true) {
  const tableBody = document.getElementById('staffLFTableBody');
  if (!tableBody) {
    stopStaffLFTimer();
    return;
  }

  const prevScrollY = window.scrollY;

  try {
    const res = await fetchJSON('../api/staff_lostfound_list.php');
    if (!res.ok || !Array.isArray(res.data)) {
      if (!silent) toast(res.error || 'Failed to load lost and found listings.', 'error');
      return;
    }

    staffLFItems = res.data;
    applyStaffLFFilters();
    window.scrollTo({ top: prevScrollY, behavior: 'instant' });
  } catch (err) {
    if (!silent) toast('Network error refreshing listings.', 'error');
  }
}

function applyStaffLFFilters() {
  const tableBody      = document.getElementById('staffLFTableBody');
  const tableContainer = document.getElementById('staffLFTableContainer');
  const emptyState     = document.getElementById('staffLFEmptyState');
  const countEl        = document.getElementById('staffLFCount');

  if (!tableBody) return;

  const typeFilter   = document.getElementById('staffLFTypeFilter')?.value || 'all';
  const statusFilter = document.getElementById('staffLFStatusFilter')?.value || 'active';
  const query        = (document.getElementById('staffLFSearch')?.value || '').trim().toLowerCase();

  const filtered = staffLFItems.filter((item) => {
    // Type filter
    if (typeFilter !== 'all' && item.type !== typeFilter) {
      return false;
    }
    // Status filter
    if (statusFilter !== 'all' && item.status !== statusFilter) {
      return false;
    }
    // Search query over item_description or poster college_id
    if (query !== '') {
      const descMatch = (item.item_description || '').toLowerCase().includes(query);
      const idMatch   = (item.college_id || '').toLowerCase().includes(query);
      if (!descMatch && !idMatch) {
        return false;
      }
    }
    return true;
  });

  if (countEl) {
    countEl.textContent = `Showing ${filtered.length} of ${staffLFItems.length} items`;
  }

  tableBody.innerHTML = '';

  if (filtered.length === 0) {
    if (tableContainer) tableContainer.style.display = 'none';
    if (emptyState)     emptyState.style.display = 'flex';
    return;
  }

  if (emptyState)     emptyState.style.display = 'none';
  if (tableContainer) tableContainer.style.display = 'block';

  renderStaffLFTableRows(filtered);
}

function renderStaffLFTableRows(items) {
  const tableBody = document.getElementById('staffLFTableBody');
  if (!tableBody) return;

  const userRole = document.body.dataset.role || '';
  const isWarden = (userRole === 'warden');

  items.forEach((item) => {
    const tr = document.createElement('tr');
    tr.id = `staff-lf-${item.id}`;

    // 1. Date
    const tdDate = document.createElement('td');
    tdDate.textContent = item.date_logged || '';
    tdDate.style.whiteSpace = 'nowrap';
    tdDate.style.color = 'var(--text-secondary)';

    // 2. Type badge
    const tdType = document.createElement('td');
    const typeBadge = document.createElement('span');
    typeBadge.className = (item.type === 'found') ? 'badge badge-approved' : 'badge badge-rejected';
    typeBadge.textContent = item.type ? (item.type.charAt(0).toUpperCase() + item.type.slice(1)) : '';
    tdType.appendChild(typeBadge);

    // 3. Item Description
    const tdDesc = document.createElement('td');
    tdDesc.textContent = item.item_description || '';
    tdDesc.style.maxWidth = '260px';
    tdDesc.style.wordBreak = 'break-word';

    // 4. Posted By (college ID)
    const tdPoster = document.createElement('td');
    tdPoster.textContent = item.college_id || '—';
    tdPoster.style.fontWeight = 'var(--font-weight-medium)';

    // 5. Room No
    const tdRoom = document.createElement('td');
    tdRoom.textContent = item.room_no || '—';
    tdRoom.style.color = 'var(--text-secondary)';

    // 6. Photo
    const tdPhoto = document.createElement('td');
    if (item.has_image) {
      const img = document.createElement('img');
      img.src = `../api/media.php?id=${encodeURIComponent(item.id)}&type=lostfound`;
      img.alt = 'Thumbnail';
      img.className = 'lf-thumbnail';
      img.style.width = '36px';
      img.style.height = '36px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = 'var(--radius-sm)';
      img.style.border = '1px solid var(--border-color)';
      img.style.cursor = 'pointer';
      img.title = 'Click to view full photo';
      img.onclick = () => viewLostFoundMedia(item.id);
      tdPhoto.appendChild(img);
    } else {
      tdPhoto.textContent = '—';
      tdPhoto.style.color = 'var(--text-muted)';
    }

    // 7. Status + Resolved Info
    const tdStatus = document.createElement('td');
    const statusBadge = document.createElement('span');
    statusBadge.id = `staff-lf-status-${item.id}`;
    statusBadge.className = (item.status === 'resolved') ? 'badge badge-resolved' : 'badge badge-pending';
    statusBadge.textContent = item.status ? (item.status.charAt(0).toUpperCase() + item.status.slice(1)) : '';
    tdStatus.appendChild(statusBadge);

    if (item.status === 'resolved') {
      const resolvedInfo = document.createElement('div');
      resolvedInfo.id = `staff-lf-resolved-info-${item.id}`;
      resolvedInfo.style.fontSize = '11px';
      resolvedInfo.style.color = 'var(--text-muted)';
      resolvedInfo.style.marginTop = '4px';
      resolvedInfo.style.whiteSpace = 'nowrap';
      if (item.resolved_at) {
        resolvedInfo.textContent = `Resolved ${item.resolved_at} by ${item.resolved_by_role || 'staff'}`;
      } else {
        resolvedInfo.textContent = 'Resolved —';
      }
      tdStatus.appendChild(resolvedInfo);
    }

    // 8. Action
    const tdAction = document.createElement('td');
    tdAction.style.textAlign = 'right';
    tdAction.id = `staff-lf-action-${item.id}`;

    if (item.status === 'active') {
      const resolveBtn = document.createElement('button');
      resolveBtn.type = 'button';
      resolveBtn.className = 'btn btn-secondary btn-sm';
      resolveBtn.id = `staff-lf-btn-${item.id}`;
      resolveBtn.textContent = 'Mark Resolved';
      resolveBtn.onclick = () => promptStaffResolveLF(item.id);
      tdAction.appendChild(resolveBtn);
    } else if (item.status === 'resolved' && isWarden) {
      const reopenBtn = document.createElement('button');
      reopenBtn.type = 'button';
      reopenBtn.className = 'btn btn-secondary btn-sm';
      reopenBtn.id = `staff-lf-btn-${item.id}`;
      reopenBtn.textContent = 'Reopen';
      reopenBtn.onclick = () => promptStaffReopenLF(item.id);
      tdAction.appendChild(reopenBtn);
    }

    tr.appendChild(tdDate);
    tr.appendChild(tdType);
    tr.appendChild(tdDesc);
    tr.appendChild(tdPoster);
    tr.appendChild(tdRoom);
    tr.appendChild(tdPhoto);
    tr.appendChild(tdStatus);
    tr.appendChild(tdAction);
    tableBody.appendChild(tr);
  });
}

function promptStaffResolveLF(id) {
  const item = staffLFItems.find((i) => i.id === id);
  const modalTitle = document.getElementById('lfConfirmTitle');
  const modalBody  = document.getElementById('lfConfirmBody');
  const confirmBtn = document.getElementById('lfConfirmBtn');

  if (modalTitle) modalTitle.textContent = 'Mark Item as Resolved';
  if (modalBody)  modalBody.textContent  = 'Mark this item as resolved?';

  if (confirmBtn) {
    confirmBtn.textContent = 'Confirm';
    confirmBtn.classList.remove('is-loading');
    confirmBtn.disabled = false;
    confirmBtn.onclick = async () => {
      confirmBtn.classList.add('is-loading');
      confirmBtn.disabled = true;

      try {
        const res = await fetchJSON('../api/lostfound_resolve.php', {
          method: 'POST',
          body: { id },
        });

        if (!res.ok) {
          toast(res.error || 'Could not resolve listing.', 'error');
          confirmBtn.classList.remove('is-loading');
          confirmBtn.disabled = false;
          return;
        }

        closeModal();
        toast('Item marked as resolved.', 'success');

        if (item) {
          item.status = 'resolved';
          item.resolved_at = res.data?.resolved_at || new Date().toISOString().replace('T', ' ').slice(0, 19);
          item.resolved_by_role = res.data?.resolved_by_role || (document.body.dataset.role || 'staff');
        }

        const statusFilter = document.getElementById('staffLFStatusFilter')?.value || 'active';
        const row = document.getElementById(`staff-lf-${id}`);
        if (statusFilter === 'active' && row) {
          row.style.transition = 'opacity var(--transition-normal), transform var(--transition-normal)';
          row.style.opacity = '0';
          row.style.transform = 'translateX(-8px)';
          setTimeout(() => {
            applyStaffLFFilters();
          }, 200);
        } else {
          applyStaffLFFilters();
        }
      } catch (err) {
        toast('Network error. Please try again.', 'error');
        confirmBtn.classList.remove('is-loading');
        confirmBtn.disabled = false;
      }
    };
  }

  openModal('lfConfirmModal');
}

function promptStaffReopenLF(id) {
  const item = staffLFItems.find((i) => i.id === id);
  const modalTitle = document.getElementById('lfConfirmTitle');
  const modalBody  = document.getElementById('lfConfirmBody');
  const confirmBtn = document.getElementById('lfConfirmBtn');

  if (modalTitle) modalTitle.textContent = 'Reopen Listing';
  if (modalBody)  modalBody.textContent  = 'Reopen this resolved listing and make it active again?';

  if (confirmBtn) {
    confirmBtn.textContent = 'Reopen';
    confirmBtn.classList.remove('is-loading');
    confirmBtn.disabled = false;
    confirmBtn.onclick = async () => {
      confirmBtn.classList.add('is-loading');
      confirmBtn.disabled = true;

      try {
        const res = await fetchJSON('../api/lostfound_reopen.php', {
          method: 'POST',
          body: { id },
        });

        if (!res.ok) {
          toast(res.error || 'Could not reopen listing.', 'error');
          confirmBtn.classList.remove('is-loading');
          confirmBtn.disabled = false;
          return;
        }

        closeModal();
        toast('Item reopened.', 'success');

        if (item) {
          item.status = 'active';
          item.resolved_at = null;
          item.resolved_by_role = null;
        }

        const statusFilter = document.getElementById('staffLFStatusFilter')?.value || 'active';
        const row = document.getElementById(`staff-lf-${id}`);
        if (statusFilter === 'resolved' && row) {
          row.style.transition = 'opacity var(--transition-normal), transform var(--transition-normal)';
          row.style.opacity = '0';
          row.style.transform = 'translateX(-8px)';
          setTimeout(() => {
            applyStaffLFFilters();
          }, 200);
        } else {
          applyStaffLFFilters();
        }
      } catch (err) {
        toast('Network error. Please try again.', 'error');
        confirmBtn.classList.remove('is-loading');
        confirmBtn.disabled = false;
      }
    };
  }

  openModal('lfConfirmModal');
}

// ------------------------------------------------------------------------------
// Staff Portal: Student Registration Approvals
// ------------------------------------------------------------------------------
async function initStaffApprovals() {
  const tableBody = document.getElementById('staffPendingTableBody');
  const tableContainer = document.getElementById('staffPendingTableContainer');
  const emptyState = document.getElementById('staffPendingEmptyState');

  if (!tableBody) return;

  try {
    const res = await fetchJSON('../api/student_pending.php');
    if (!res.ok || !Array.isArray(res.data) || res.data.length === 0) {
      if (tableContainer) tableContainer.style.display = 'none';
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (tableContainer) tableContainer.style.display = 'block';
    if (emptyState) emptyState.style.display = 'none';
    tableBody.innerHTML = '';

    res.data.forEach((student) => {
      const tr = document.createElement('tr');
      tr.id = `pending-student-${student.id}`;

      // College ID
      const tdCollegeId = document.createElement('td');
      tdCollegeId.style.fontWeight = 'var(--font-weight-medium)';
      tdCollegeId.textContent = student.college_id;

      // Gender
      const tdGender = document.createElement('td');
      tdGender.style.textTransform = 'capitalize';
      tdGender.textContent = student.gender;

      // Branch
      const tdBranch = document.createElement('td');
      tdBranch.textContent = student.branch;

      // Room No
      const tdRoom = document.createElement('td');
      tdRoom.textContent = student.room_no || 'N/A';

      // Registered Date
      const tdDate = document.createElement('td');
      tdDate.style.fontSize = 'var(--text-xs)';
      tdDate.style.color = 'var(--text-muted)';
      tdDate.textContent = (student.created_at || '').split(' ')[0];

      // Actions: Approve & Reject buttons
      const tdAction = document.createElement('td');
      tdAction.style.textAlign = 'right';

      const actionGroup = document.createElement('div');
      actionGroup.style.display = 'inline-flex';
      actionGroup.style.gap = 'var(--space-2)';

      const approveBtn = document.createElement('button');
      approveBtn.type = 'button';
      approveBtn.className = 'btn btn-primary btn-sm';
      approveBtn.textContent = 'Approve';
      approveBtn.onclick = () => decideStudent(student.id, 'approved', tr, approveBtn, rejectBtn);

      const rejectBtn = document.createElement('button');
      rejectBtn.type = 'button';
      rejectBtn.className = 'btn btn-secondary btn-sm';
      rejectBtn.style.color = 'var(--color-danger)';
      rejectBtn.textContent = 'Reject';
      rejectBtn.onclick = () => decideStudent(student.id, 'rejected', tr, rejectBtn, approveBtn);

      actionGroup.appendChild(approveBtn);
      actionGroup.appendChild(rejectBtn);
      tdAction.appendChild(actionGroup);

      tr.appendChild(tdCollegeId);
      tr.appendChild(tdGender);
      tr.appendChild(tdBranch);
      tr.appendChild(tdRoom);
      tr.appendChild(tdDate);
      tr.appendChild(tdAction);

      tableBody.appendChild(tr);
    });
  } catch (err) {
    console.warn('Failed to load pending students:', err.message);
  }
}

async function decideStudent(studentId, decision, rowEl, activeBtn, otherBtn) {
  if (activeBtn) {
    activeBtn.classList.add('is-loading');
    activeBtn.disabled = true;
  }
  if (otherBtn) {
    otherBtn.disabled = true;
  }

  try {
    const res = await fetchJSON('../api/student_decide.php', {
      method: 'POST',
      body: { student_id: studentId, decision: decision },
    });

    toast(res.data?.message || `Student registration ${decision}.`, 'success');

    // Smooth row fade-out transition
    if (rowEl) {
      rowEl.style.transition = 'opacity var(--transition-normal), transform var(--transition-normal)';
      rowEl.style.opacity = '0';
      rowEl.style.transform = 'translateY(-4px)';
      setTimeout(() => {
        if (rowEl.parentElement) {
          rowEl.remove();
          const tbody = document.getElementById('staffPendingTableBody');
          const tableContainer = document.getElementById('staffPendingTableContainer');
          const emptyState = document.getElementById('staffPendingEmptyState');
          if (tbody && tbody.children.length === 0) {
            if (tableContainer) tableContainer.style.display = 'none';
            if (emptyState) emptyState.style.display = 'block';
          }
        }
      }, 200);
    }
  } catch (err) {
    if (activeBtn) {
      activeBtn.classList.remove('is-loading');
      activeBtn.disabled = false;
    }
    if (otherBtn) {
      otherBtn.disabled = false;
    }
  }
}

// ------------------------------------------------------------------------------
// Staff Portal: Official Notices Management
// ------------------------------------------------------------------------------
async function initStaffNotices() {
  const form = document.getElementById('staffNoticeForm');
  if (form) {
    form.onsubmit = async (e) => {
      e.preventDefault();
      form.querySelectorAll('.form-error').forEach((el) => { el.textContent = ''; });
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.add('is-loading');
        submitBtn.disabled = true;
      }

      try {
        const formData = new FormData(form);
        const res = await fetchJSON('../api/notice_create.php', {
          method: 'POST',
          body: formData,
        });

        toast(res.data?.message || 'Notice broadcast published successfully.', 'success');
        form.reset();
        await loadStaffNotices();
      } catch (err) {
        if (err.response && err.response.data && typeof err.response.data === 'object') {
          for (const [field, msg] of Object.entries(err.response.data)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
              const group = input.closest('.form-group') || input.parentElement;
              if (group) {
                group.classList.add('has-error');
                let errEl = group.querySelector('.form-error');
                if (errEl) errEl.textContent = String(msg);
              }
            }
          }
        }
      } finally {
        if (submitBtn) {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;
        }
      }
    };
  }

  await loadStaffNotices();
}

async function loadStaffNotices() {
  const container = document.getElementById('staffNoticesList');
  const emptyState = document.getElementById('staffNoticeEmptyState');
  if (!container) return;

  try {
    const res = await fetchJSON('../api/notice_list.php');
    if (!res.ok || !Array.isArray(res.data) || res.data.length === 0) {
      container.innerHTML = '';
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';
    container.innerHTML = '';

    res.data.forEach((notice) => {
      const card = document.createElement('div');
      card.id = `staff-notice-${notice.id}`;
      card.style.background = 'var(--bg-surface)';
      card.style.border = '1px solid var(--border-hairline)';
      card.style.borderRadius = 'var(--radius-md)';
      card.style.padding = 'var(--space-4)';
      card.style.display = 'flex';
      card.style.justifyContent = 'space-between';
      card.style.alignItems = 'flex-start';
      card.style.gap = 'var(--space-4)';

      const contentBox = document.createElement('div');
      contentBox.style.flex = '1';

      const meta = document.createElement('div');
      meta.style.display = 'flex';
      meta.style.gap = 'var(--space-2)';
      meta.style.alignItems = 'center';
      meta.style.marginBottom = 'var(--space-2)';
      meta.style.fontSize = 'var(--text-xs)';
      meta.style.color = 'var(--text-muted)';

      const authorSpan = document.createElement('strong');
      authorSpan.style.color = 'var(--text-main)';
      authorSpan.textContent = notice.posted_by_name || 'Staff';

      const roleSpan = document.createElement('span');
      roleSpan.className = 'badge badge-in-progress';
      roleSpan.style.fontSize = '10px';
      roleSpan.textContent = (notice.posted_by_role || 'staff').toUpperCase();

      const timeSpan = document.createElement('span');
      timeSpan.textContent = notice.created_at || '';

      meta.appendChild(authorSpan);
      meta.appendChild(roleSpan);
      meta.appendChild(timeSpan);

      const msgP = document.createElement('p');
      msgP.style.margin = '0';
      msgP.style.fontSize = 'var(--text-sm)';
      msgP.style.lineHeight = '1.5';
      msgP.style.whiteSpace = 'pre-wrap';
      msgP.textContent = notice.message;

      contentBox.appendChild(meta);
      contentBox.appendChild(msgP);

      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'btn btn-secondary btn-sm';
      deleteBtn.style.color = 'var(--color-danger)';
      deleteBtn.textContent = 'Delete';
      deleteBtn.onclick = () => deleteStaffNotice(notice.id, card, deleteBtn);

      card.appendChild(contentBox);
      card.appendChild(deleteBtn);

      container.appendChild(card);
    });
  } catch (err) {
    console.warn('Failed to load notices:', err.message);
  }
}

async function deleteStaffNotice(noticeId, cardEl, btnEl) {
  if (btnEl) {
    btnEl.classList.add('is-loading');
    btnEl.disabled = true;
  }

  try {
    const res = await fetchJSON('../api/notice_delete.php', {
      method: 'POST',
      body: { id: noticeId },
    });

    toast(res.data?.message || 'Notice removed successfully.', 'success');

    if (cardEl) {
      cardEl.style.transition = 'opacity var(--transition-normal), transform var(--transition-normal)';
      cardEl.style.opacity = '0';
      cardEl.style.transform = 'translateY(-4px)';
      setTimeout(() => {
        if (cardEl.parentElement) {
          cardEl.remove();
          const list = document.getElementById('staffNoticesList');
          const emptyState = document.getElementById('staffNoticeEmptyState');
          if (list && list.children.length === 0 && emptyState) {
            emptyState.style.display = 'block';
          }
        }
      }, 200);
    }
  } catch (err) {
    if (btnEl) {
      btnEl.classList.remove('is-loading');
      btnEl.disabled = false;
    }
  }
}

// ------------------------------------------------------------------------------
// Staff Portal: Complaints Desk
// ------------------------------------------------------------------------------
let staffComplaintCategoryFilter = '';

async function initStaffComplaints() {
  await loadStaffComplaints();
}

async function filterStaffComplaints(category) {
  staffComplaintCategoryFilter = category || '';
  await loadStaffComplaints();
}

async function loadStaffComplaints() {
  const tableBody = document.getElementById('staffComplaintTableBody');
  const tableContainer = document.getElementById('staffComplaintTableContainer');
  const emptyState = document.getElementById('staffComplaintEmptyState');

  if (!tableBody) return;

  try {
    const url = staffComplaintCategoryFilter
      ? `../api/staff_complaint_list.php?category=${encodeURIComponent(staffComplaintCategoryFilter)}`
      : '../api/staff_complaint_list.php';

    const res = await fetchJSON(url);
    if (!res.ok || !Array.isArray(res.data) || res.data.length === 0) {
      if (tableContainer) tableContainer.style.display = 'none';
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (tableContainer) tableContainer.style.display = 'block';
    if (emptyState) emptyState.style.display = 'none';
    tableBody.innerHTML = '';

    res.data.forEach((c) => {
      const tr = document.createElement('tr');
      tr.id = `staff-complaint-${c.id}`;

      // Category
      const tdCategory = document.createElement('td');
      const catBadge = document.createElement('span');
      catBadge.className = 'badge badge-in-progress';
      catBadge.textContent = c.category;
      tdCategory.appendChild(catBadge);

      // Student Info
      const tdStudent = document.createElement('td');
      tdStudent.style.fontSize = 'var(--text-xs)';
      const collegeDiv = document.createElement('div');
      collegeDiv.style.fontWeight = 'var(--font-weight-medium)';
      collegeDiv.style.color = 'var(--text-main)';
      collegeDiv.textContent = c.college_id;
      const roomDiv = document.createElement('div');
      roomDiv.style.color = 'var(--text-muted)';
      roomDiv.textContent = `Room ${c.room_no || 'N/A'}`;
      tdStudent.appendChild(collegeDiv);
      tdStudent.appendChild(roomDiv);

      // Description
      const tdDesc = document.createElement('td');
      tdDesc.style.maxWidth = '300px';
      tdDesc.style.wordBreak = 'break-word';
      tdDesc.textContent = c.description;

      // Photo
      const tdPhoto = document.createElement('td');
      if (c.has_image) {
        const photoBtn = document.createElement('button');
        photoBtn.type = 'button';
        photoBtn.className = 'btn btn-secondary btn-sm';
        photoBtn.textContent = 'View Photo';
        photoBtn.onclick = () => viewComplaintMedia(c.id);
        tdPhoto.appendChild(photoBtn);
      } else {
        tdPhoto.textContent = 'None';
        tdPhoto.style.fontSize = 'var(--text-xs)';
        tdPhoto.style.color = 'var(--text-muted)';
      }

      // Logged Date
      const tdDate = document.createElement('td');
      tdDate.style.fontSize = 'var(--text-xs)';
      tdDate.style.color = 'var(--text-muted)';
      tdDate.textContent = (c.created_at || '').split(' ')[0];

      // Status dropdown with dynamic badge transition
      const tdStatus = document.createElement('td');
      tdStatus.style.textAlign = 'right';

      const statusBox = document.createElement('div');
      statusBox.style.display = 'inline-flex';
      statusBox.style.flexDirection = 'column';
      statusBox.style.alignItems = 'flex-end';
      statusBox.style.gap = 'var(--space-1)';

      const badge = document.createElement('span');
      badge.id = `staff-complaint-badge-${c.id}`;
      badge.className = `badge badge-${c.status}`;
      badge.textContent = c.status;

      const select = document.createElement('select');
      select.className = 'form-select form-select-sm';
      select.style.padding = '2px 8px';
      select.style.fontSize = 'var(--text-xs)';
      select.style.width = '125px';

      const opts = [
        { val: 'pending', text: 'Pending' },
        { val: 'in-progress', text: 'In-Progress' },
        { val: 'resolved', text: 'Resolved' }
      ];

      opts.forEach((o) => {
        const opt = document.createElement('option');
        opt.value = o.val;
        opt.textContent = o.text;
        if (o.val === c.status) opt.selected = true;
        select.appendChild(opt);
      });

      select.onchange = async () => {
        await updateStaffComplaintStatus(c.id, select.value, badge, select);
      };

      statusBox.appendChild(badge);
      statusBox.appendChild(select);
      tdStatus.appendChild(statusBox);

      tr.appendChild(tdCategory);
      tr.appendChild(tdStudent);
      tr.appendChild(tdDesc);
      tr.appendChild(tdPhoto);
      tr.appendChild(tdDate);
      tr.appendChild(tdStatus);

      tableBody.appendChild(tr);
    });
  } catch (err) {
    console.warn('Failed to load complaints:', err.message);
  }
}

async function updateStaffComplaintStatus(complaintId, newStatus, badgeEl, selectEl) {
  if (selectEl) selectEl.disabled = true;

  try {
    const res = await fetchJSON('../api/complaint_status.php', {
      method: 'POST',
      body: { id: complaintId, status: newStatus },
    });

    toast(res.data?.message || 'Complaint status updated.', 'success');

    if (badgeEl) {
      badgeEl.className = `badge badge-${newStatus}`;
      badgeEl.textContent = newStatus;
    }
  } catch (err) {
    await loadStaffComplaints();
  } finally {
    if (selectEl) selectEl.disabled = false;
  }
}

// ------------------------------------------------------------------------------
// Staff Portal: Mess Analytics & Menu Management
// ------------------------------------------------------------------------------
let currentEditingMealId = 0;

async function initStaffMessSection() {
  const menuForm = document.getElementById('menuEditForm');
  if (menuForm && !menuForm.dataset.bound) {
    menuForm.dataset.bound = 'true';
    menuForm.onsubmit = async (e) => {
      e.preventDefault();
      menuForm.querySelectorAll('.form-error').forEach((el) => { el.textContent = ''; });
      const submitBtn = document.getElementById('btnSaveMenuEdit');
      if (submitBtn) {
        submitBtn.classList.add('is-loading');
        submitBtn.disabled = true;
      }

      try {
        const mealId = parseInt(document.getElementById('menuEditMealId')?.value || '0', 10);
        const items = document.getElementById('menuEditItemsInput')?.value || '';

        const res = await fetchJSON('../api/menu_update.php', {
          method: 'POST',
          body: { meal_id: mealId, items: items },
        });

        toast(res.data?.message || 'Mess menu updated successfully.', 'success');
        closeModal();
        await Promise.all([loadStaffMessAnalytics(), loadStaffMessMenu()]);
      } catch (err) {
        if (err.response && err.response.data && typeof err.response.data === 'object') {
          for (const [field, msg] of Object.entries(err.response.data)) {
            const input = menuForm.querySelector(`[name="${field}"]`);
            if (input) {
              const group = input.closest('.form-group') || input.parentElement;
              if (group) {
                group.classList.add('has-error');
                let errEl = group.querySelector('.form-error');
                if (errEl) errEl.textContent = String(msg);
              }
            }
          }
        }
      } finally {
        if (submitBtn) {
          submitBtn.classList.remove('is-loading');
          submitBtn.disabled = false;
        }
      }
    };
  }

  await Promise.all([loadStaffMessAnalytics(), loadStaffMessMenu()]);
}

async function loadStaffMessAnalytics() {
  const tableBody = document.getElementById('staffMessAnalyticsTableBody');
  const overallRatingEl = document.getElementById('staffMessOverallRating');
  const totalVotesEl = document.getElementById('staffMessTotalVotes');

  if (!tableBody) return;

  try {
    const res = await fetchJSON('../api/mess_analytics.php');
    if (!res.ok || !res.data) return;

    const data = res.data;
    if (overallRatingEl) {
      overallRatingEl.textContent = data.overall_rating > 0 ? data.overall_rating.toFixed(1) : '—';
    }
    if (totalVotesEl) {
      totalVotesEl.textContent = String(data.total_votes || 0);
    }

    tableBody.innerHTML = '';
    const list = data.analytics || [];

    list.forEach((item) => {
      const tr = document.createElement('tr');

      // Day
      const tdDay = document.createElement('td');
      tdDay.style.fontWeight = 'var(--font-weight-medium)';
      tdDay.textContent = item.day;

      // Meal
      const tdMeal = document.createElement('td');
      tdMeal.style.textTransform = 'capitalize';
      tdMeal.textContent = item.meal_type;

      // Scheduled Items
      const tdItems = document.createElement('td');
      tdItems.textContent = item.items;

      // Vote Count
      const tdVotes = document.createElement('td');
      tdVotes.style.textAlign = 'center';
      tdVotes.style.fontFamily = 'var(--font-mono)';
      tdVotes.style.fontSize = 'var(--text-xs)';
      tdVotes.textContent = String(item.vote_count);

      // Average Rating & Inline CSS Bar
      const tdRating = document.createElement('td');

      const barContainer = document.createElement('div');
      barContainer.style.display = 'flex';
      barContainer.style.alignItems = 'center';
      barContainer.style.gap = 'var(--space-3)';

      const barTrack = document.createElement('div');
      barTrack.style.flex = '1';
      barTrack.style.height = '6px';
      barTrack.style.backgroundColor = 'var(--border-hairline)';
      barTrack.style.borderRadius = 'var(--radius-full)';
      barTrack.style.overflow = 'hidden';
      barTrack.style.minWidth = '70px';

      const barFill = document.createElement('div');
      const pct = Math.min(100, Math.max(0, (item.avg_rating / 5) * 100));
      barFill.style.width = `${pct}%`;
      barFill.style.height = '100%';
      barFill.style.borderRadius = 'var(--radius-full)';
      barFill.style.transition = 'width var(--transition-normal)';

      if (item.avg_rating >= 4.0) {
        barFill.style.backgroundColor = 'var(--color-success)';
      } else if (item.avg_rating >= 2.5) {
        barFill.style.backgroundColor = 'var(--color-warning)';
      } else if (item.vote_count > 0) {
        barFill.style.backgroundColor = 'var(--color-danger)';
      } else {
        barFill.style.backgroundColor = 'transparent';
      }

      barTrack.appendChild(barFill);

      const ratingScore = document.createElement('span');
      ratingScore.style.fontFamily = 'var(--font-mono)';
      ratingScore.style.fontWeight = 'var(--font-weight-medium)';
      ratingScore.style.fontSize = 'var(--text-xs)';
      ratingScore.style.minWidth = '24px';
      ratingScore.style.textAlign = 'right';
      ratingScore.textContent = item.vote_count > 0 ? item.avg_rating.toFixed(1) : '—';

      barContainer.appendChild(barTrack);
      barContainer.appendChild(ratingScore);
      tdRating.appendChild(barContainer);

      tr.appendChild(tdDay);
      tr.appendChild(tdMeal);
      tr.appendChild(tdItems);
      tr.appendChild(tdVotes);
      tr.appendChild(tdRating);

      tableBody.appendChild(tr);
    });
  } catch (err) {
    console.warn('Failed to load mess analytics:', err.message);
  }
}

async function loadStaffMessMenu() {
  const tableBody = document.getElementById('staffMessMenuTableBody');
  if (!tableBody) return;

  try {
    const res = await fetchJSON('../api/mess_menu.php');
    if (!res.ok || !res.data || !Array.isArray(res.data.menu)) return;

    tableBody.innerHTML = '';
    const userRole = document.body.dataset.role || '';
    const isWarden = (userRole === 'warden');

    res.data.menu.forEach((meal) => {
      const tr = document.createElement('tr');
      tr.id = `menu-row-${meal.id}`;

      // Day
      const tdDay = document.createElement('td');
      tdDay.style.fontWeight = 'var(--font-weight-medium)';
      tdDay.textContent = meal.day;

      // Meal
      const tdMeal = document.createElement('td');
      tdMeal.style.textTransform = 'capitalize';
      tdMeal.textContent = meal.meal_type;

      // Items
      const tdItems = document.createElement('td');
      tdItems.id = `menu-items-text-${meal.id}`;
      tdItems.textContent = meal.items;

      // Action
      const tdAction = document.createElement('td');
      tdAction.style.textAlign = 'right';

      if (isWarden) {
        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'btn btn-secondary btn-sm';
        editBtn.textContent = 'Edit';
        editBtn.onclick = () => openMenuEditModal(meal);
        tdAction.appendChild(editBtn);
      } else {
        const roSpan = document.createElement('span');
        roSpan.style.fontSize = 'var(--text-xs)';
        roSpan.style.color = 'var(--text-muted)';
        roSpan.textContent = 'Read-Only';
        tdAction.appendChild(roSpan);
      }

      tr.appendChild(tdDay);
      tr.appendChild(tdMeal);
      tr.appendChild(tdItems);
      tr.appendChild(tdAction);

      tableBody.appendChild(tr);
    });
  } catch (err) {
    console.warn('Failed to load mess menu for staff:', err.message);
  }
}

function openMenuEditModal(meal) {
  currentEditingMealId = meal.id;
  const idInput = document.getElementById('menuEditMealId');
  const dayBadge = document.getElementById('menuEditDayBadge');
  const mealType = document.getElementById('menuEditMealType');
  const itemsInput = document.getElementById('menuEditItemsInput');

  if (idInput) idInput.value = String(meal.id);
  if (dayBadge) dayBadge.textContent = meal.day;
  if (mealType) mealType.textContent = meal.meal_type;
  if (itemsInput) {
    const rowText = document.getElementById(`menu-items-text-${meal.id}`)?.textContent;
    itemsInput.value = rowText || meal.items;
  }

  openModal('menuEditModal');
}

// Explicit window bindings for contract compliance
window.fetchJSON = fetchJSON;
window.toast = toast;
window.openModal = openModal;
window.closeModal = closeModal;
window.bindForm = bindForm;
window.loadSection = loadSection;
window.viewComplaintMedia = viewComplaintMedia;
window.initStudentDashboard = initStudentDashboard;
window.initComplaintsSection = initComplaintsSection;
window.initMessSection = initMessSection;
window.initLostFoundSection = initLostFoundSection;
window.filterLostFound = filterLostFound;
window.resolveLostFoundItem = resolveLostFoundItem;
window.initStaffApprovals = initStaffApprovals;
window.initStaffComplaints = initStaffComplaints;
window.initStaffNotices = initStaffNotices;
window.filterStaffComplaints = filterStaffComplaints;
window.initStaffLostFound = initStaffLostFound;
window.applyStaffLFFilters = applyStaffLFFilters;
window.filterStaffLostFound = applyStaffLFFilters;
window.staffResolveLFItem = promptStaffResolveLF;
window.promptStaffResolveLF = promptStaffResolveLF;
window.promptStaffReopenLF = promptStaffReopenLF;
window.stopStaffLFTimer = stopStaffLFTimer;
window.updateStaffComplaintStatus = updateStaffComplaintStatus;
window.deleteStaffNotice = deleteStaffNotice;
window.decideStudent = decideStudent;
window.initStaffMessSection = initStaffMessSection;
window.loadStaffMessAnalytics = loadStaffMessAnalytics;
window.loadStaffMessMenu = loadStaffMessMenu;
window.openMenuEditModal = openMenuEditModal;
window.initPasswordToggles = initPasswordToggles;


