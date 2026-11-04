<?php
/**
 * Staff Portal Shell (Warden & Caretaker)
 * IIIT Kota Hostel Management System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
$contactsData = require __DIR__ . '/../config/contacts.php';

// Guard: Require staff role (warden or caretaker)
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || !in_array($_SESSION['role'], ['warden', 'caretaker'], true)) {
    header('Location: index.php');
    exit;
}

$staffName   = (string)($_SESSION['name'] ?? 'Staff Officer');
$staffUser   = (string)($_SESSION['staff_username'] ?? 'staff');
$role        = (string)($_SESSION['role'] ?? 'caretaker');
$hostelType  = (string)($_SESSION['hostel_type'] ?? 'boys');

$hostelContacts = $contactsData[$hostelType] ?? [];
$commonContacts = $contactsData['common'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <title>Staff Portal (<?= ucfirst($role) ?>) | IIIT Kota</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .user-profile-badge {
      display: flex;
      flex-direction: column;
      gap: 2px;
      margin-top: var(--space-2);
      font-size: var(--text-xs);
      color: var(--text-secondary);
    }
    .contacts-panel {
      margin-top: auto;
      padding: var(--space-4) var(--space-4);
      border-top: 1px solid var(--border-hairline);
      background: var(--bg-subtle);
      font-size: var(--text-xs);
    }
    .contacts-title {
      font-weight: var(--font-weight-semibold);
      color: var(--text-main);
      margin-bottom: var(--space-2);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      gap: var(--space-1);
    }
    .contact-item {
      margin-bottom: var(--space-2);
      line-height: 1.35;
    }
    .contact-role {
      font-weight: var(--font-weight-medium);
      color: var(--text-main);
    }
    .contact-phone {
      color: var(--accent);
      font-family: var(--font-mono);
      font-size: 11px;
    }
  </style>
</head>
<body data-role="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" data-hostel="<?= htmlspecialchars($hostelType, ENT_QUOTES, 'UTF-8') ?>">
  <div class="app-shell">
    <!-- Mobile Navigation Bar -->
    <header class="mobile-bar">
      <button class="mobile-toggle" aria-label="Toggle navigation">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <div class="brand-title">Staff Administration</div>
      <div style="width: 24px;"></div>
    </header>

    <!-- Fixed Left Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="brand-title">IIIT Kota Hostels</div>
        <div class="user-profile-badge">
          <strong style="color: var(--text-main);"><?= htmlspecialchars($staffName, ENT_QUOTES, 'UTF-8') ?></strong>
          <span><?= ucfirst($role) ?> &bull; <?= ucfirst($hostelType) ?> Hostel</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <button class="nav-link is-active" data-section="approvals" onclick="loadSection('approvals')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <polyline points="16 11 18 13 22 9"></polyline>
          </svg>
          Student Approvals
        </button>

        <button class="nav-link" data-section="complaints" onclick="loadSection('complaints')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          Complaints Desk
        </button>

        <button class="nav-link" data-section="notices" onclick="loadSection('notices')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
          </svg>
          Notices Management
        </button>

        <button class="nav-link" data-section="mess" onclick="loadSection('mess')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
          Mess Menu &amp; Ratings
        </button>

        <button class="nav-link" data-section="lost_found" onclick="loadSection('lost_found')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Lost &amp; Found Desk
        </button>

        <div style="margin-top: var(--space-4); padding-top: var(--space-2); border-top: 1px solid var(--border-hairline);">
          <button type="button" class="nav-link" style="color: var(--color-danger);" onclick="logoutUser()">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Sign Out
          </button>
        </div>
      </nav>

      <!-- Persistent Emergency Contacts Section (From contacts.php) -->
      <div class="contacts-panel">
        <div class="contacts-title">
          <svg class="icon" style="width: 12px; height: 12px; color: var(--color-danger);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
          </svg>
          Hostel Contacts
        </div>

        <?php if (!empty($hostelContacts['warden'])): ?>
        <div class="contact-item">
          <div class="contact-role"><?= htmlspecialchars($hostelContacts['warden']['designation'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="contact-phone"><?= htmlspecialchars($hostelContacts['warden']['phone'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($hostelContacts['caretaker'])): ?>
        <div class="contact-item">
          <div class="contact-role"><?= htmlspecialchars($hostelContacts['caretaker']['designation'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="contact-phone"><?= htmlspecialchars($hostelContacts['caretaker']['phone'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($commonContacts['dispensary'])): ?>
        <div class="contact-item">
          <div class="contact-role"><?= htmlspecialchars($commonContacts['dispensary']['designation'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="contact-phone"><?= htmlspecialchars($commonContacts['dispensary']['phone'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>
      </div>
    </aside>

    <div class="sidebar-backdrop"></div>

    <!-- Main Content Shell -->
    <main class="main-content">
      <div class="content-container" id="content">
        <!-- Default Section: Approvals -->
        <div class="page-header">
          <h1>Student Registration Approvals</h1>
          <p>Pending accounts waiting for admission approval into <?= ucfirst($hostelType) ?> Hostel.</p>
        </div>

        <div class="table-container" id="staffPendingTableContainer" style="display: none;">
          <table class="table">
            <thead>
              <tr>
                <th>College ID</th>
                <th style="width: 100px;">Gender</th>
                <th style="width: 120px;">Branch</th>
                <th style="width: 100px;">Room No</th>
                <th style="width: 150px;">Registered</th>
                <th style="width: 180px; text-align: right;">Action</th>
              </tr>
            </thead>
            <tbody id="staffPendingTableBody">
              <!-- Populated dynamically via JS -->
            </tbody>
          </table>
        </div>

        <div class="empty-state" id="staffPendingEmptyState" style="display: none;">
          <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <polyline points="16 11 18 13 22 9"></polyline>
          </svg>
          <div class="empty-state-title">No Pending Registrations</div>
          <div class="empty-state-text">All student registration requests for <?= ucfirst($hostelType) ?> Hostel have been reviewed.</div>
        </div>
      </div>
    </main>
  </div>

  <!-- Client-side tab templates -->
  <template id="template-approvals">
    <div class="page-header">
      <h1>Student Registration Approvals</h1>
      <p>Pending accounts waiting for admission approval into <?= ucfirst($hostelType) ?> Hostel.</p>
    </div>

    <div class="table-container" id="staffPendingTableContainer" style="display: none;">
      <table class="table">
        <thead>
          <tr>
            <th>College ID</th>
            <th style="width: 100px;">Gender</th>
            <th style="width: 120px;">Branch</th>
            <th style="width: 100px;">Room No</th>
            <th style="width: 150px;">Registered</th>
            <th style="width: 180px; text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody id="staffPendingTableBody">
          <!-- Populated dynamically via JS -->
        </tbody>
      </table>
    </div>

    <div class="empty-state" id="staffPendingEmptyState" style="display: none;">
      <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <polyline points="16 11 18 13 22 9"></polyline>
      </svg>
      <div class="empty-state-title">No Pending Registrations</div>
      <div class="empty-state-text">All student registration requests for <?= ucfirst($hostelType) ?> Hostel have been reviewed.</div>
    </div>
  </template>

  <template id="template-complaints">
    <div class="page-header">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
        <div>
          <h1>Hostel Complaints Desk</h1>
          <p>Active issues and work orders in <?= ucfirst($hostelType) ?> Hostel.</p>
        </div>
        <div style="display: flex; gap: var(--space-2); align-items: center;">
          <label for="staffComplaintFilterCategory" style="font-size: var(--text-xs); color: var(--text-secondary); font-weight: var(--font-weight-medium);">Filter:</label>
          <select class="form-select form-select-sm" id="staffComplaintFilterCategory" onchange="filterStaffComplaints(this.value)" style="width: auto;">
            <option value="">All Categories</option>
            <option value="Electrical">Electrical</option>
            <option value="Plumbing">Plumbing</option>
            <option value="Carpentry">Carpentry</option>
            <option value="Discipline">Discipline</option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-container" id="staffComplaintTableContainer" style="display: none;">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 120px;">Category</th>
            <th style="width: 220px;">Student</th>
            <th>Description</th>
            <th style="width: 105px;">Photo</th>
            <th style="width: 130px;">Logged</th>
            <th style="width: 150px; text-align: right;">Status</th>
          </tr>
        </thead>
        <tbody id="staffComplaintTableBody">
          <!-- Populated dynamically via JS -->
        </tbody>
      </table>
    </div>

    <div class="empty-state" id="staffComplaintEmptyState" style="display: none;">
      <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <div class="empty-state-title">No Complaints Found</div>
      <div class="empty-state-text">There are currently no complaints matching the selected filter in <?= ucfirst($hostelType) ?> Hostel.</div>
    </div>
  </template>

  <template id="template-notices">
    <div class="page-header">
      <h1>Official Notice Broadcasts</h1>
      <p>Publish administrative circulars to <?= ucfirst($hostelType) ?> Hostel residents.</p>
    </div>

    <!-- Publish Notice Card -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-lg); padding: var(--space-6); margin-bottom: var(--space-8);">
      <h3 style="margin-bottom: var(--space-4);">Broadcast New Notice</h3>
      <form id="staffNoticeForm">
        <div class="form-group" style="margin-bottom: var(--space-4);">
          <label class="form-label" for="staffNoticeMessage">Notice Content</label>
          <textarea class="form-textarea" id="staffNoticeMessage" name="message" rows="3" placeholder="Enter administrative announcement, maintenance alert, or notice..." required minlength="5"></textarea>
          <div class="form-error"></div>
        </div>
        <div style="display: flex; justify-content: flex-end;">
          <button type="submit" class="btn btn-primary" id="btnPublishNotice">
            Broadcast Notice
          </button>
        </div>
      </form>
    </div>

    <!-- Recent Notices Directory -->
    <div style="margin-top: var(--space-6);">
      <h3 style="margin-bottom: var(--space-4);">Recent Broadcasts (<?= ucfirst($hostelType) ?> Hostel)</h3>
      <div id="staffNoticesList" style="display: flex; flex-direction: column; gap: var(--space-3);">
        <!-- Populated dynamically via JS -->
      </div>
      <div class="empty-state" id="staffNoticeEmptyState" style="display: none;">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
          <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
        </svg>
        <div class="empty-state-title">No Active Notices</div>
        <div class="empty-state-text">No official notices have been published yet for <?= ucfirst($hostelType) ?> Hostel.</div>
      </div>
    </div>
  </template>

  <template id="template-mess">
    <div class="page-header">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
        <div>
          <h1>Mess Menu &amp; Ratings Analytics</h1>
          <p>Shared campus weekly menu and 7-day student satisfaction trends for <?= ucfirst($hostelType) ?> Hostel.</p>
        </div>
        <div>
          <span class="badge badge-in-progress" style="font-size: var(--text-xs);">
            <?= ucfirst($role) ?> Mode (<?= $role === 'warden' ? 'Editable Menu' : 'Read-Only Menu' ?>)
          </span>
        </div>
      </div>
    </div>

    <!-- Summary KPI cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-6);">
      <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-md); padding: var(--space-4);">
        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-1);">7-DAY AVERAGE RATING</div>
        <div style="display: flex; align-items: baseline; gap: var(--space-2);">
          <span id="staffMessOverallRating" style="font-size: var(--text-2xl); font-weight: var(--font-weight-semibold); color: var(--text-main); font-family: var(--font-mono);">—</span>
          <span style="font-size: var(--text-xs); color: var(--text-muted);">/ 5.0</span>
        </div>
      </div>
      <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-md); padding: var(--space-4);">
        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-1);">TOTAL STUDENT RATINGS (7D)</div>
        <div id="staffMessTotalVotes" style="font-size: var(--text-2xl); font-weight: var(--font-weight-semibold); color: var(--text-main); font-family: var(--font-mono);">—</div>
      </div>
      <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-md); padding: var(--space-4);">
        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-1);">MONITORED HOSTEL</div>
        <div style="font-size: var(--text-lg); font-weight: var(--font-weight-semibold); color: var(--text-main); text-transform: capitalize;"><?= ucfirst($hostelType) ?> Hostel</div>
      </div>
    </div>

    <!-- Section 1: Mess Analytics Table (Last 7 Days) -->
    <div style="margin-bottom: var(--space-8);">
      <h3 style="margin-bottom: var(--space-3);">Student Feedback Analytics (Last 7 Days)</h3>
      <div class="table-container" id="staffMessAnalyticsContainer">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 80px;">Day</th>
              <th style="width: 110px;">Meal</th>
              <th>Menu Items</th>
              <th style="width: 90px; text-align: center;">Votes</th>
              <th style="width: 180px;">Rating (Last 7 Days)</th>
            </tr>
          </thead>
          <tbody id="staffMessAnalyticsTableBody">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Section 2: Shared Weekly Menu Editor -->
    <div>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3); flex-wrap: wrap; gap: var(--space-2);">
        <div>
          <h3 style="margin-bottom: 2px;">Shared Campus Weekly Menu</h3>
          <div style="font-size: var(--text-xs); color: var(--text-muted);">
            <?= $role === 'warden' ? 'Click "Edit" to update items for any meal. Changes reflect immediately across all student and staff portals.' : 'Caretaker mode: Shared menu is read-only (wardens only can edit).' ?>
          </div>
        </div>
      </div>

      <div class="table-container" id="staffMessMenuContainer">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 80px;">Day</th>
              <th style="width: 110px;">Meal</th>
              <th>Current Scheduled Items</th>
              <th style="width: 100px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody id="staffMessMenuTableBody">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>
    </div>
  </template>

  <template id="template-lost_found">
    <div class="page-header">
      <h1>Lost &amp; Found Directory</h1>
      <p>View and resolve active lost &amp; found listings for <?= ucfirst($hostelType) ?> Hostel. Staff may resolve any active listing.</p>
    </div>

    <!-- Filter Bar -->
    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4); flex-wrap: wrap;">
      <label for="staffLFFilter" style="font-size: var(--text-xs); color: var(--text-secondary); font-weight: var(--font-weight-medium);">Filter:</label>
      <select class="form-select form-select-sm" id="staffLFFilter" onchange="filterStaffLostFound(this.value)" style="width: auto;">
        <option value="all">All</option>
        <option value="active">Active</option>
        <option value="resolved">Resolved</option>
        <option value="lost">Lost</option>
        <option value="found">Found</option>
      </select>
    </div>

    <div class="table-container" id="staffLFTableContainer" style="display: none;">
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Description</th>
            <th>Photo</th>
            <th>Date</th>
            <th>Reported By</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody id="staffLFTableBody"></tbody>
      </table>
    </div>
    <div class="empty-state" id="staffLFEmptyState" style="display: none;">
      <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <div class="empty-state-title">No listings found</div>
      <div class="empty-state-text">No lost &amp; found items match the current filter.</div>
    </div>
  </template>

  <!-- Attachment View Modal -->
  <div class="modal-backdrop" id="complaintImageModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalImageTitle" style="max-width: 600px;">
      <div class="modal-header">
        <h3 class="modal-title" id="modalImageTitle">Complaint Photo Attachment</h3>
        <button type="button" class="modal-close" onclick="closeModal()" aria-label="Close modal">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body" style="text-align: center; padding: var(--space-4); background: #00000005;">
        <img id="modalComplaintImg" src="" alt="Attachment Preview" style="max-width: 100%; max-height: 70vh; border-radius: var(--radius-md); object-fit: contain;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- Menu Edit Modal (Wardens Only) -->
  <div class="modal-backdrop" id="menuEditModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalMenuEditTitle" style="max-width: 520px;">
      <div class="modal-header">
        <h3 class="modal-title" id="modalMenuEditTitle">Update Menu Items</h3>
        <button type="button" class="modal-close" onclick="closeModal()" aria-label="Close modal">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <form id="menuEditForm">
        <input type="hidden" id="menuEditMealId" name="meal_id" value="">
        <div class="modal-body" style="padding: var(--space-5);">
          <div style="margin-bottom: var(--space-4); display: flex; gap: var(--space-2); align-items: center;">
            <span class="badge badge-in-progress" id="menuEditDayBadge">Mon</span>
            <strong id="menuEditMealType" style="text-transform: capitalize; color: var(--text-main);">Breakfast</strong>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="menuEditItemsInput">Scheduled Menu Items</label>
            <textarea class="form-textarea" id="menuEditItemsInput" name="items" rows="4" required minlength="3" maxlength="500" placeholder="e.g. Poha, Jalebi, Sprouts, Boiled Eggs / Milk, Tea"></textarea>
            <div class="form-hint">Changes apply to both boys and girls mess schedules immediately.</div>
            <div class="form-error"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnSaveMenuEdit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    // Initialize default section (Approvals) on page load
    document.addEventListener('DOMContentLoaded', () => {
      initStaffApprovals();
    });

    async function logoutUser() {
      try {
        const res = await fetchJSON('../api/auth_logout.php', { method: 'POST' });
        toast('Logged out successfully', 'success');
        setTimeout(() => {
          window.location.href = res.data?.redirect || 'index.php';
        }, 300);
      } catch (err) {
        window.location.href = 'index.php';
      }
    }
  </script>
</body>
</html>

