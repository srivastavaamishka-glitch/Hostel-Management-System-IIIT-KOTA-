<?php
/**
 * Student Portal Shell
 * IIIT Kota Hostel Management System
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
$contactsData = require __DIR__ . '/../config/contacts.php';

// Guard: Require student role
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: index.php');
    exit;
}

$collegeId   = (string)($_SESSION['college_id'] ?? 'Student');
$hostelType  = (string)($_SESSION['hostel_type'] ?? 'boys');
$branch      = (string)($_SESSION['branch'] ?? '');
$roomNo      = (string)($_SESSION['room_no'] ?? '');

$hostelContacts = $contactsData[$hostelType] ?? [];
$commonContacts = $contactsData['common'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <title>Student Portal | IIIT Kota</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
<body>
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
      <div class="brand-title">Student Portal</div>
      <div style="width: 24px;"></div>
    </header>

    <!-- Fixed Left Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="brand-title">IIIT Kota Hostels</div>
        <div class="user-profile-badge">
          <strong style="color: var(--text-main);"><?= htmlspecialchars($collegeId, ENT_QUOTES, 'UTF-8') ?></strong>
          <span>Room <?= htmlspecialchars($roomNo, ENT_QUOTES, 'UTF-8') ?> &bull; <?= ucfirst($hostelType) ?> Hostel</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <button class="nav-link is-active" data-section="overview" onclick="loadSection('overview')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="7" height="7" x="3" y="3" rx="1"></rect>
            <rect width="7" height="7" x="14" y="3" rx="1"></rect>
            <rect width="7" height="7" x="14" y="14" rx="1"></rect>
            <rect width="7" height="7" x="3" y="14" rx="1"></rect>
          </svg>
          Overview
        </button>

        <button class="nav-link" data-section="complaints" onclick="loadSection('complaints')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          Complaints
        </button>

        <button class="nav-link" data-section="mess" onclick="loadSection('mess')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
          </svg>
          Mess Menu &amp; Feedback
        </button>

        <button class="nav-link" data-section="notices" onclick="loadSection('notices')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
          </svg>
          Notices Board
        </button>

        <button class="nav-link" data-section="lost_found" onclick="loadSection('lost_found')">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Lost &amp; Found
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
          Emergency Contacts
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
        <!-- Default Section: Live Overview Dashboard -->
        <div class="page-header">
          <h1>Student Dashboard</h1>
          <p>Welcome to <?= ucfirst($hostelType) ?> Hostel, Room <?= htmlspecialchars($roomNo, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: var(--space-6);">
          <!-- Official Notice Board -->
          <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-lg); overflow: hidden;">
            <div style="padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--border-hairline); display: flex; justify-content: space-between; align-items: center; background: var(--bg-subtle);">
              <div style="font-size: var(--text-base); font-weight: var(--font-weight-semibold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                  <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                </svg>
                Hostel Notice Board
              </div>
              <span style="font-size: var(--text-xs); color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--color-success);"></span>
                Live (30s)
              </span>
            </div>
            <div id="dashboardNoticesList" style="max-height: 420px; overflow-y: auto;">
              <div style="padding: var(--space-6); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">
                Loading announcements...
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Client-side tab templates -->
  <template id="template-overview">
    <div class="page-header">
      <h1>Student Dashboard</h1>
      <p>Welcome to <?= ucfirst($hostelType) ?> Hostel, Room <?= htmlspecialchars($roomNo, ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>

    <!-- Overview Content -->
    <div style="display: grid; grid-template-columns: 1fr; gap: var(--space-6);">
      <!-- Official Notice Board -->
      <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-lg); overflow: hidden;">
        <div style="padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--border-hairline); display: flex; justify-content: space-between; align-items: center; background: var(--bg-subtle);">
          <div style="font-size: var(--text-base); font-weight: var(--font-weight-semibold); color: var(--text-main); display: flex; align-items: center; gap: var(--space-2);">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
              <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
            </svg>
            Hostel Notice Board
          </div>
          <span style="font-size: var(--text-xs); color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--color-success);"></span>
            Live (30s)
          </span>
        </div>
        <div id="dashboardNoticesList" style="max-height: 420px; overflow-y: auto;">
          <div style="padding: var(--space-6); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">
            Loading latest announcements...
          </div>
        </div>
      </div>
    </div>
  </template>

  <template id="template-complaints">
    <div class="page-header">
      <h1>Complaints &amp; Maintenance</h1>
      <p>Report and track room or wing maintenance issues in <?= ucfirst($hostelType) ?> Hostel.</p>
    </div>

    <!-- Complaint Form -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-lg); padding: var(--space-6); margin-bottom: var(--space-8);">
      <h3 style="margin-bottom: var(--space-4);">Lodge a Maintenance Complaint</h3>
      <form id="complaintForm" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="complaintCategory">Issue Category</label>
            <select class="form-select" id="complaintCategory" name="category" required>
              <option value="">Select Category</option>
              <option value="Electrical">Electrical</option>
              <option value="Plumbing">Plumbing</option>
              <option value="Carpentry">Carpentry</option>
              <option value="Discipline">Discipline</option>
            </select>
            <div class="form-error"></div>
          </div>

          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="complaintImageInput">Attach Photo (Optional, Max 2MB)</label>
            <input class="form-input" id="complaintImageInput" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">Accepted formats: JPG, PNG, WebP (2MB max)</div>
            <div class="form-error"></div>
          </div>
        </div>

        <!-- Live Image Preview Box -->
        <div id="complaintPreviewBox" style="display: none; align-items: center; gap: var(--space-4); margin-top: var(--space-4); padding: var(--space-3); border: 1px dashed var(--border-hairline); border-radius: var(--radius-md); background: var(--bg-subtle);">
          <img id="complaintPreviewImg" src="" alt="Preview" style="max-height: 80px; max-width: 120px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-hairline);">
          <div style="flex: 1; font-size: var(--text-xs); color: var(--text-secondary);">
            Photo selected for upload.
          </div>
          <button type="button" class="btn btn-secondary btn-sm" id="complaintRemoveImgBtn">Remove Photo</button>
        </div>

        <div class="form-group" style="margin-top: var(--space-4); margin-bottom: var(--space-4);">
          <label class="form-label" for="complaintDesc">Detailed Description</label>
          <textarea class="form-textarea" id="complaintDesc" name="description" placeholder="Please describe the defect, location, and room number..." required minlength="10"></textarea>
          <div class="form-error"></div>
        </div>

        <button type="submit" class="btn btn-primary" id="btnSubmitComplaint">
          Submit Maintenance Request
        </button>
      </form>
    </div>

    <!-- Complaints History Table -->
    <div style="margin-top: var(--space-6);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
        <h3>Your Maintenance History</h3>
        <span style="font-size: var(--text-xs); color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
          <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--color-success);"></span>
          Live Sync (20s)
        </span>
      </div>

      <div class="table-container" id="complaintsTableContainer" style="display: none;">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 80px;">Ticket</th>
              <th style="width: 120px;">Category</th>
              <th>Description</th>
              <th style="width: 120px;">Attachment</th>
              <th style="width: 110px;">Date</th>
              <th style="width: 110px;">Status</th>
            </tr>
          </thead>
          <tbody id="complaintsTableBody">
            <!-- Populated dynamically via renderComplaintsTable -->
          </tbody>
        </table>
      </div>

      <div class="empty-state" id="complaintsEmptyState" style="display: none;">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="m9 12 2 2 4-4"></path>
        </svg>
        <div class="empty-state-title">No Complaints Filed</div>
        <div class="empty-state-text">You haven't reported any room or maintenance defects yet.</div>
      </div>
    </div>
  </template>

  <template id="template-mess">
    <div class="page-header">
      <h1>Weekly Mess Menu &amp; Daily Rating</h1>
      <p>Shared campus dining schedule. Submit anonymous 5-star ratings for today's meals.</p>
    </div>

    <!-- Rating info banner -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-lg); padding: var(--space-4) var(--space-5); margin-bottom: var(--space-6); display: flex; align-items: center; justify-content: space-between; gap: var(--space-4);">
      <div style="font-size: var(--text-sm); color: var(--text-secondary);">
        <strong style="color: var(--text-main);">Today's Meal Feedback:</strong> Rate today's breakfast, lunch, or dinner. Ratings are completely anonymous and locked after submission.
      </div>
      <div id="todayDayBadge" class="badge badge-in-progress" style="font-size: var(--text-xs); padding: 0.35rem 0.65rem;">
        Today: Loading...
      </div>
    </div>

    <!-- Weekly Menu Table Container -->
    <div class="table-container" id="messTableContainer">
      <table class="table" id="messMenuTable">
        <thead>
          <tr>
            <th style="width: 90px;">Day</th>
            <th style="width: 110px;">Meal Type</th>
            <th>Menu Items</th>
            <th style="width: 240px; text-align: right;">Today's Rating</th>
          </tr>
        </thead>
        <tbody id="messMenuTableBody">
          <!-- Rendered dynamically -->
        </tbody>
      </table>
    </div>
  </template>

  <template id="template-notices">
    <div class="page-header">
      <h1>Hostel Notices Board</h1>
      <p>Official notices for <?= ucfirst($hostelType) ?> Hostel residents.</p>
    </div>
    <div class="empty-state">
      <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
      </svg>
      <div class="empty-state-title">Notices Board Module</div>
      <div class="empty-state-text">Coming in next phase.</div>
    </div>
  </template>

  <template id="template-lost_found">
    <div class="page-header">
      <h1>Lost &amp; Found Notice Board</h1>
      <p>Report misplaced or discovered belongings within <?= ucfirst($hostelType) ?> Hostel.</p>
    </div>

    <!-- Create Post Form Card -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-hairline); border-radius: var(--radius-lg); padding: var(--space-6); margin-bottom: var(--space-8);">
      <h3 style="margin-bottom: var(--space-4);">Publish Lost / Found Item</h3>
      <form id="lostFoundForm" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 180px 1fr 1fr; gap: var(--space-4);">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="lfType">Listing Type</label>
            <select class="form-select" id="lfType" name="type" required>
              <option value="lost">I Lost An Item</option>
              <option value="found">I Found An Item</option>
            </select>
            <div class="form-error"></div>
          </div>

          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="lfDescription">Item Details &amp; Where Discovered/Lost</label>
            <input class="form-input" id="lfDescription" name="item_description" type="text" placeholder="e.g. Blue Titan wristwatch near 2nd floor washroom" required minlength="5">
            <div class="form-error"></div>
          </div>

          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="lfImageInput">Attach Photo (Optional, Max 2MB)</label>
            <input class="form-input" id="lfImageInput" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">Accepted formats: JPG, PNG, WebP (2MB max)</div>
            <div class="form-error"></div>
          </div>
        </div>

        <!-- Live Image Preview Box -->
        <div id="lfPreviewBox" style="display: none; align-items: center; gap: var(--space-4); margin-top: var(--space-4); padding: var(--space-3); border: 1px dashed var(--border-hairline); border-radius: var(--radius-md); background: var(--bg-subtle);">
          <img id="lfPreviewImg" src="" alt="Preview" style="max-height: 80px; max-width: 120px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-hairline);">
          <div style="flex: 1; font-size: var(--text-xs); color: var(--text-secondary);">
            Photo selected for upload.
          </div>
          <button type="button" class="btn btn-secondary btn-sm" id="lfRemoveImgBtn">Remove Photo</button>
        </div>

        <div style="margin-top: var(--space-4); display: flex; justify-content: space-between; align-items: center;">
          <div style="font-size: var(--text-xs); color: var(--text-muted);">
            Your College ID (<?= htmlspecialchars($collegeId, ENT_QUOTES, 'UTF-8') ?>) will be visible as contact info.
          </div>
          <button type="submit" class="btn btn-primary" id="btnSubmitLostFound">
            Publish Notice
          </button>
        </div>
      </form>
    </div>

    <!-- Lost & Found Directory -->
    <div style="margin-top: var(--space-6);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-2);">
        <h3>Community Listings (<?= ucfirst($hostelType) ?> Hostel)</h3>
        <div style="display: flex; gap: var(--space-2);" id="lfFilterButtonGroup">
          <button type="button" class="btn btn-secondary btn-sm is-active" id="lfFilterAll" onclick="filterLostFound('all')">All</button>
          <button type="button" class="btn btn-secondary btn-sm" id="lfFilterLost" onclick="filterLostFound('lost')">Lost</button>
          <button type="button" class="btn btn-secondary btn-sm" id="lfFilterFound" onclick="filterLostFound('found')">Found</button>
          <button type="button" class="btn btn-secondary btn-sm" id="lfFilterActive" onclick="filterLostFound('active')">Active Only</button>
        </div>
      </div>

      <div class="table-container" id="lostFoundTableContainer" style="display: none;">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 90px;">Type</th>
              <th>Item Description</th>
              <th style="width: 105px;">Photo</th>
              <th style="width: 110px;">Date</th>
              <th style="width: 220px;">Contact</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 120px; text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody id="lostFoundTableBody">
            <!-- Populated dynamically via JS -->
          </tbody>
        </table>
      </div>

      <div class="empty-state" id="lostFoundEmptyState" style="display: none;">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <div class="empty-state-title">No Active Listings</div>
        <div class="empty-state-text">No lost or found items reported for your hostel at this time.</div>
      </div>
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

  <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
  <script>
    // Initialize Dashboard live polling on initial load
    document.addEventListener('DOMContentLoaded', () => {
      initStudentDashboard();
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

