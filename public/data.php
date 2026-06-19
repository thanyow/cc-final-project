<?php
require __DIR__ . '/config.php';

$isAdmin = !empty($_SESSION['admin_logged_in']);

// Process DB writes if admin is authenticated
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($title !== '' && $body !== '') {
            $stmt = db()->prepare('INSERT INTO announcements (title, body) VALUES (?, ?)');
            $stmt->execute([$title, $body]);
            header('Location: /data.php?added=1');
            exit;
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('DELETE FROM announcements WHERE id = ?');
            $stmt->execute([$id]);
            header('Location: /data.php?deleted=1');
            exit;
        }
    }
}

try {
    $statement = db()->query('SELECT id, title, body, created_at FROM announcements ORDER BY created_at DESC');
    $cards = '';

    foreach ($statement as $item) {
        $deleteBtn = '';
        if ($isAdmin) {
            $deleteBtn = '
              <div class="data-card-controls">
                <form method="post" action="/data.php" onsubmit="return confirm(\'Delete this weather log?\');" style="margin: 0; display: inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="' . $item['id'] . '">
                  <button type="submit" class="btn-inline-delete" title="Delete Log">
                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                  </button>
                </form>
              </div>';
        }

        $cards .= '
        <article class="data-card">
          <div class="data-card-body">
            <h3 style="display: flex; align-items: center; gap: 8px;">
              <i data-lucide="cloud-rain" style="color:var(--accent-cyan); width:20px; height: 20px;"></i> 
              ' . htmlspecialchars($item['title']) . '
            </h3>
            <p>' . htmlspecialchars($item['body']) . '</p>
          </div>
          <div class="data-card-footer" style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 14px; margin-top: 14px;">
            <small style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
              <i data-lucide="calendar" style="width: 14px; height: 14px;"></i> 
              ' . htmlspecialchars($item['created_at']) . '
            </small>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="background: #f0f9ff; border: 1px solid #bae6fd; color: var(--accent-cyan); font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 99px;">
                Weather Log
              </span>
              ' . $deleteBtn . '
            </div>
          </div>
        </article>';
    }

    $notices = '';
    if (isset($_GET['added'])) {
        $notices = '
          <div style="margin-bottom: 20px; padding: 12px 16px; background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 12px; font-size: 0.85rem; font-weight: 600; color: var(--success); display: flex; align-items: center; gap: 8px;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
            <span>Atmospheric log entry successfully synced to database.</span>
          </div>';
    } elseif (isset($_GET['deleted'])) {
        $notices = '
          <div style="margin-bottom: 20px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; font-size: 0.85rem; font-weight: 600; color: var(--error); display: flex; align-items: center; gap: 8px;">
            <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
            <span>Log entry permanently removed from the database.</span>
          </div>';
    }

    $adminForm = '';
    if ($isAdmin) {
        $adminForm = '
        <div class="action-box" style="background: rgba(255, 255, 255, 0.6); border: 2px dashed var(--accent-cyan); border-radius: 20px; padding: 24px; margin-bottom: 24px;">
          <h3 style="margin-top:0; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="megaphone" style="color: var(--accent-cyan);"></i> Publish Weather Update
          </h3>
          <form method="post" action="/data.php" class="form" style="display: grid; gap: 16px;">
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="display: grid; gap: 6px;">
              <label style="font-size: 0.85rem; font-weight: bold; color: var(--text-primary);">Event Title</label>
              <input name="title" placeholder="E.g. Pressure Drop Detected in Sector 4" required style="width: 100%; padding: 12px 16px; background: rgba(255, 255, 255, 0.8); border: 1px solid #cbd5e1; border-radius: 12px; font-family: inherit; font-size: 0.95rem;">
            </div>
            <div class="form-group" style="display: grid; gap: 6px;">
              <label style="font-size: 0.85rem; font-weight: bold; color: var(--text-primary);">Log Details</label>
              <textarea name="body" rows="3" placeholder="Describe the atmospheric event..." required style="width: 100%; padding: 12px 16px; background: rgba(255, 255, 255, 0.8); border: 1px solid #cbd5e1; border-radius: 12px; font-family: inherit; font-size: 0.95rem;"></textarea>
            </div>
            <button type="submit" class="btn" style="width: auto; margin-top: 4px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: var(--accent-cyan); color: white; padding: 12px 24px; border-radius: 12px; font-weight: bold; border: none; cursor: pointer;">
              <i data-lucide="send" style="width: 18px; height: 18px;"></i> Publish Log
            </button>
          </form>
        </div>';
    }

    $content = $notices . $adminForm . '
    <section class="panel">
      <h2><i data-lucide="thermometer"></i> Atmospheric Data Logs</h2>
      <p style="margin-bottom: 24px; font-size: 0.92rem; color: var(--text-secondary);">Historical weather events and system logs retrieved from the shared RDS database instance.</p>
      
      <div class="table-toolbar" style="margin-bottom: 24px;">
        <div style="position: relative; max-width: 320px; width: 100%;">
          <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #64748b; z-index: 1;"></i>
          <input type="text" id="announcement-search" placeholder="Search atmospheric logs..." style="width: 100%; padding: 12px 16px 12px 40px; background: rgba(255, 255, 255, 0.9); border: 1px solid #cbd5e1; border-radius: 12px; font-family: inherit; font-size: 0.9rem; color: var(--text-primary); outline: none; position: relative;">
        </div>
      </div>

      <div class="cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        ' . ($cards !== '' ? $cards : '<p style="color: var(--text-muted); font-size: 0.9rem; grid-column: 1/-1;">No atmospheric log entries found in the database.</p>') . '
      </div>
    </section>

    <script>
    document.addEventListener(\'DOMContentLoaded\', () => {
      const searchInput = document.getElementById(\'announcement-search\');
      
      searchInput.addEventListener(\'input\', (e) => {
        const val = e.target.value.toLowerCase();
        const cards = document.querySelectorAll(\'.data-card\');
        cards.forEach(card => {
          const title = card.querySelector(\'h3\').innerText.toLowerCase();
          const body = card.querySelector(\'p\').innerText.toLowerCase();
          if (title.includes(val) || body.includes(val)) {
            card.style.display = \'\';
          } else {
            card.style.display = \'none\';
          }
        });
      });
      
      // Focus effect for search bar
      const searchBox = document.getElementById(\'announcement-search\');
      searchBox.addEventListener(\'focus\', () => {
        searchBox.style.borderColor = \'var(--accent-cyan)\';
        searchBox.style.boxShadow = \'0 0 0 3px rgba(2, 132, 199, 0.1)\';
      });
      searchBox.addEventListener(\'blur\', () => {
        searchBox.style.borderColor = \'#cbd5e1\';
        searchBox.style.boxShadow = \'none\';
      });
    });
    </script>';

    renderPage('Weather Logs', $content);
} catch (Throwable $error) {
    renderPage('Database Error', errorView($error));
}
?>