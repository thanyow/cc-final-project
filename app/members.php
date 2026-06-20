<?php
require __DIR__ . '/../app/config.php';

$isAdmin = !empty($_SESSION['admin_logged_in']);

// Process DB writes if admin is authenticated
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['student_name'] ?? '');
        $sid = trim($_POST['student_id'] ?? '');
        $role = trim($_POST['role_name'] ?? '');
        if ($name !== '' && $sid !== '' && $role !== '') {
            $stmt = db()->prepare('INSERT INTO group_members (student_name, student_id, role_name) VALUES (?, ?, ?)');
            $stmt->execute([$name, $sid, $role]);
            header('Location: /members.php?added=1');
            exit;
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('DELETE FROM group_members WHERE id = ?');
            $stmt->execute([$id]);
            header('Location: /members.php?deleted=1');
            exit;
        }
    }
}

try {
    // FIXED: Filter out "Station Guest" so only official team members show up on the roster
    $statement = db()->query("SELECT id, student_name, student_id, role_name FROM group_members WHERE role_name != 'Station Guest' ORDER BY id");
    $cards = '';

    foreach ($statement as $member) {
        $role = $member['role_name'];
        
        // Match specific weather icons for roles
        $roleIcon = 'user';
        if ($role === 'Chief Meteorologist') $roleIcon = 'cloud-lightning';
        elseif ($role === 'Atmospheric Analyst') $roleIcon = 'bar-chart';
        elseif ($role === 'Radar Engineer') $roleIcon = 'radar';
        elseif ($role === 'Field Observer') $roleIcon = 'compass';

        $deleteBtn = '';
        if ($isAdmin) {
            $deleteBtn = '
              <div class="crew-card-actions">
                <form method="post" action="/members.php" onsubmit="return confirm(\'Revoke access and remove this meteorologist?\');" style="margin: 0; display: inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="' . $member['id'] . '">
                  <button type="submit" class="btn-inline-delete" title="Delete Operator">
                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                  </button>
                </form>
              </div>';
        }

        $cards .= '
        <div class="crew-card" data-name="' . htmlspecialchars(strtolower($member['student_name'])) . '" data-id="' . htmlspecialchars(strtolower($member['student_id'])) . '" data-role="' . htmlspecialchars(strtolower($role)) . '">
          <div class="crew-avatar">
            <i data-lucide="' . $roleIcon . '" style="width: 28px; height: 28px;"></i>
          </div>
          <div class="crew-name">' . htmlspecialchars($member['student_name']) . '</div>
          <div class="crew-id">' . htmlspecialchars($member['student_id']) . '</div>
          <div class="crew-role">
            <i data-lucide="' . $roleIcon . '" style="width: 14px; height: 14px;"></i>
            <span>' . htmlspecialchars($role) . '</span>
          </div>
          ' . $deleteBtn . '
        </div>';
    }

    $notices = '';
    if (isset($_GET['added'])) {
        $notices = '
          <div style="margin-bottom: 16px; padding: 12px 16px; background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 12px; font-size: 0.85rem; font-weight: 600; color: var(--success); display: flex; align-items: center; gap: 8px;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
            <span>Station personnel added to database successfully.</span>
          </div>';
    } elseif (isset($_GET['deleted'])) {
        $notices = '
          <div style="margin-bottom: 16px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; font-size: 0.85rem; font-weight: 600; color: var(--error); display: flex; align-items: center; gap: 8px;">
            <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
            <span>Personnel record removed from Nimbus database.</span>
          </div>';
    }

    $adminForm = '';
    if ($isAdmin) {
        $adminForm = '
        <div class="action-box">
          <h3 style="margin-top:0;"><i data-lucide="user-plus"></i> Register Station Personnel</h3>
          <form method="post" action="/members.php" class="form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 12px; align-items: end; margin-top: 8px;">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label>Full Name</label>
              <input name="student_name" placeholder="E.g. Kelly Vance" required>
            </div>
            <div class="form-group">
              <label>Student ID (NIM)</label>
              <input name="student_id" placeholder="E.g. 102022340119" required>
            </div>
            <div class="form-group">
              <label>Station Role</label>
              <select name="role_name" required>
                <option value="Chief Meteorologist">Chief Meteorologist</option>
                <option value="Atmospheric Analyst">Atmospheric Analyst</option>
                <option value="Radar Engineer">Radar Engineer</option>
                <option value="Field Observer">Field Observer</option>
              </select>
            </div>
            <button type="submit" class="btn" style="width: auto; height: 46px;"><i data-lucide="plus"></i> Add Member</button>
          </form>
        </div>';
    }

    $content = $notices . $adminForm . '
    <section class="panel">
      <h2><i data-lucide="users"></i> Meteorology Team Roster</h2>
      <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 24px;">The active personnel assignments below are loaded dynamically from the central AWS RDS database.</p>
      
      <div class="table-toolbar" style="margin-bottom: 24px;">
        <div style="position: relative; max-width: 320px; width: 100%;">
          <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #64748b; z-index: 1;"></i>
          <input type="text" id="member-search" placeholder="Filter personnel by name, ID or role..." style="width: 100%; padding: 12px 16px 12px 40px; background: rgba(255, 255, 255, 0.9); border: 1px solid #cbd5e1; border-radius: 12px; font-family: inherit; font-size: 0.9rem; color: var(--text-primary); outline: none; position: relative;">
        </div>
      </div>

      <div class="crew-grid">
        ' . ($cards !== '' ? $cards : '<p style="color: var(--text-muted); font-size: 0.9rem; grid-column: 1/-1;">No personnel loaded in database.</p>') . '
      </div>
    </section>

    <script>
    document.addEventListener(\'DOMContentLoaded\', () => {
      const searchInput = document.getElementById(\'member-search\');
      
      searchInput.addEventListener(\'input\', (e) => {
        const val = e.target.value.toLowerCase();
        const cards = document.querySelectorAll(\'.crew-card\');
        cards.forEach(card => {
          const name = card.getAttribute(\'data-name\');
          const cid = card.getAttribute(\'data-id\');
          const role = card.getAttribute(\'data-role\');
          if (name.includes(val) || cid.includes(val) || role.includes(val)) {
            card.style.display = \'\';
          } else {
            card.style.display = \'none\';
          }
        });
      });
      
      // Focus effect for search bar
      const searchBox = document.getElementById(\'member-search\');
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

    renderPage('Station Crew', $content);
} catch (Throwable $error) {
    renderPage('Database Error', errorView($error));
}
?>