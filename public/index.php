<?php
require __DIR__ . '/config.php';

$isAdmin = !empty($_SESSION['admin_logged_in']);

// Handle the Weather Alert Toggle
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_alert') {
    $_SESSION['weather_alert'] = !empty($_POST['alert_status']);
    header('Location: /index.php');
    exit;
}

// Generate the Admin Panel dynamically if logged in
$adminPanel = '';
if ($isAdmin) {
    $alertActive = !empty($_SESSION['weather_alert']);
    $adminPanel = '
    <section class="panel" style="margin-bottom: 24px; border: 2px dashed #0284c7; background: rgba(255, 255, 255, 0.8);">
      <h3 style="margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; font-family: var(--font-heading); color: var(--text-primary); font-size: 1.15rem;">
        <i data-lucide="sliders" style="color: #0284c7;"></i> Weather Control Center
      </h3>
      <p style="margin-bottom: 16px; color: var(--text-secondary); font-size: 0.9rem;">Simulate a global weather alert to test frontend synchronization across all active nodes.</p>
      <form method="post" action="/index.php" style="display:flex; gap:10px; align-items:center;">
        <input type="hidden" name="action" value="toggle_alert">
        <input type="hidden" name="alert_status" value="' . ($alertActive ? '0' : '1') . '">
        <button type="submit" class="btn" style="width: auto; background: ' . ($alertActive ? '#64748b' : '#ef4444') . ';">
          <i data-lucide="' . ($alertActive ? 'bell-off' : 'bell-ring') . '"></i> 
          ' . ($alertActive ? 'Cancel Alert' : 'Broadcast Severe Weather Alert') . '
        </button>
      </form>
    </section>';
}

// The rest of the Cloud Playground HTML
$htmlContent = <<<'HTML'
  <style>
    .gradient-text {
      background: linear-gradient(135deg, var(--accent-cyan), #a78bfa, var(--accent-violet));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 800;
    }
    
    .feature-list { display: flex; flex-direction: column; gap: 12px; }
    .feature-item {
      display: flex; align-items: center; gap: 16px;
      background: rgba(255, 255, 255, 0.6);
      padding: 12px 16px; border-radius: 16px;
      border: 1px solid var(--border-color);
      transition: transform 0.3s ease;
    }
    .feature-item:hover { transform: translateX(8px); background: white; }
    .feature-icon {
      width: 42px; height: 42px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .cyan-glow { background: #e0f2fe; color: var(--accent-cyan); }
    .violet-glow { background: #f0f9ff; color: var(--accent-violet); }
    .green-glow { background: #d1fae5; color: var(--success); }
    .feature-text strong { display: block; color: var(--text-primary); font-size: 0.9rem; margin-bottom: 2px; }
    .feature-text span { font-size: 0.78rem; color: var(--text-secondary); line-height: 1.3; }

    /* =========================================
       TELEMETRY GRID (LEFT PANEL)
       ========================================= */
    .telemetry-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;
    }
    .telemetry-box {
      background: #0f172a; border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 16px;
      padding: 14px 10px; text-align: center; color: white; position: relative; overflow: hidden;
    }
    .telemetry-box::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--accent-cyan), transparent);
    }
    .telemetry-box i { color: var(--accent-cyan); width: 20px; height: 20px; margin-bottom: 4px; }
    .telemetry-box span { display: block; font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 2px; }
    .telemetry-box strong { font-family: var(--font-mono); font-size: 1.1rem; color: #e0f2fe; text-shadow: 0 0 8px rgba(56, 189, 248, 0.4); }

    /* =========================================
       RADAR SCREEN (RIGHT PANEL)
       ========================================= */
    .radar-screen {
      background: #0f172a !important; border: 4px solid #e2e8f0 !important;
      border-radius: 24px !important; overflow: hidden;
      box-shadow: inset 0 0 60px rgba(0,0,0,0.9) !important; position: relative;
    }
    
    .radar-grid {
      position: absolute; top: 0; left: 0; right: 0; bottom: 0;
      background-image: linear-gradient(rgba(56, 189, 248, 0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(56, 189, 248, 0.1) 1px, transparent 1px);
      background-size: 20px 20px; z-index: 0; pointer-events: none; opacity: 0.5;
    }
    .radar-crosshair-v { position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; background: rgba(56, 189, 248, 0.3); z-index: 1; }
    .radar-crosshair-h { position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: rgba(56, 189, 248, 0.3); z-index: 1; }

    .radar-sweep-container { position: absolute; top: -50%; left: -50%; right: -50%; bottom: -50%; animation: radar-spin 4s linear infinite; pointer-events: none; z-index: 1; }
    .radar-sweep { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: conic-gradient(from 0deg, transparent 75%, rgba(56, 189, 248, 0.15) 99%, rgba(56, 189, 248, 0.6) 100%); border-radius: 50%; }
    .radar-rings { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 260px; height: 260px; border-radius: 50%; border: 1px solid rgba(56, 189, 248, 0.2); pointer-events: none; z-index: 1; }
    .radar-rings::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 130px; height: 130px; border-radius: 50%; border: 1px solid rgba(56, 189, 248, 0.2); }
    @keyframes radar-spin { 100% { transform: rotate(360deg); } }

    .radar-screen .topo-node { background: rgba(15, 23, 42, 0.85) !important; border: 1px solid rgba(56, 189, 248, 0.5) !important; box-shadow: 0 0 15px rgba(2, 132, 199, 0.4) !important; backdrop-filter: blur(4px); z-index: 3; }
    .radar-screen .topo-node span { color: white !important; text-shadow: 0 0 8px rgba(255,255,255,0.5); }
    .radar-screen .topo-node small { color: #94a3b8 !important; }
    .radar-screen .topo-node i { color: #38bdf8 !important; }
    
    .radar-screen .topo-line { stroke: rgba(56, 189, 248, 0.2) !important; stroke-dasharray: 4, 4; z-index: 2; }
    .radar-screen .topo-line.active { stroke: #38bdf8 !important; filter: drop-shadow(0 0 4px #38bdf8); stroke-dasharray: none; }
    .radar-screen .topo-line.active-db { stroke: #34d399 !important; filter: drop-shadow(0 0 4px #34d399); stroke-dasharray: none; }

    .sonar-ripple { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border-radius: 50%; z-index: 2; pointer-events: none; animation: ripple-out 1s cubic-bezier(0.1, 0.8, 0.3, 1) forwards; }
    .ripple-cyan { border: 2px solid #38bdf8; }
    .ripple-violet { border: 2px solid #a78bfa; }
    @keyframes ripple-out { 0% { width: 20px; height: 20px; opacity: 1; border-width: 4px; } 100% { width: 140px; height: 140px; opacity: 0; border-width: 1px; } }

    @keyframes float-slow { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
    @keyframes float-fast { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
    .floating-slow { animation: float-slow 4s ease-in-out infinite; }
    .floating-fast { animation: float-fast 2.5s ease-in-out infinite 1s; }

    .activity-feed {
      background: rgba(255,255,255,0.4); border: 1px solid var(--border-color);
      height: 160px; overflow-y: auto; border-radius: 16px;
      display: flex; flex-direction: column; gap: 8px; padding: 12px; margin-top: 10px;
      font-family: var(--font-mono); 
    }
    .feed-bubble {
      background: white; padding: 10px 14px; border-radius: 8px;
      font-size: 0.8rem; color: var(--text-secondary); width: 100%;
      animation: popIn 0.2s ease-out forwards; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border-left: 3px solid transparent;
    }
    .system-bubble { border-left-color: var(--text-muted); color: var(--text-secondary); }
    .alpha-bubble { border-left-color: var(--accent-cyan); }
    .beta-bubble { border-left-color: var(--accent-violet); }
    .err-bubble { border-left-color: var(--error); background: #fef2f2; color: var(--error); }
    .ping-time { opacity: 0.6; font-size: 0.7rem; float: right; }
    @keyframes popIn { from { opacity: 0; transform: translateX(-5px); } to { opacity: 1; transform: translateX(0); } }
  </style>

  <section class="panel" style="margin-bottom: 24px; padding: 24px 30px;">
    <div style="display: flex; align-items: flex-start; gap: 20px;">
      <div style="background: #e0f2fe; color: var(--accent-cyan); width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bae6fd; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);">
        <i data-lucide="info" style="width: 32px; height: 32px;"></i>
      </div>
      <div>
        <h2 style="margin-top: 0; margin-bottom: 8px; font-size: 1.4rem; color: var(--text-primary);"><span class="gradient-text">Project Overview: High-Availability Architecture</span></h2>
        <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 0; font-size: 0.95rem;">
          Welcome to <strong>Project Nimbus</strong>. This dashboard is a functional proof-of-concept for a high-availability cloud infrastructure built on AWS. 
          While themed as a meteorological command center, it fulfills all core requirements of our Cloud Computing Final Project: 
          <strong>Two EC2 Web Servers</strong> for redundancy, an <strong>Application Load Balancer (ALB)</strong> for traffic distribution, 
          and a <strong>Dedicated Database EC2 Instance</strong> for synchronized state management. Explore the active routing nodes below!
        </p>
      </div>
    </div>
  </section>

  <div class="hero">
    <section class="panel welcome-panel" style="display: flex; flex-direction: column;">
      <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 99px; background: #e0f2fe; color: var(--accent-cyan); font-size: 0.75rem; font-weight: 700; margin-bottom: 16px; width: fit-content;">
        <i data-lucide="satellite" style="width:14px;height:14px;"></i> Live Facility Uplink
      </div>
      
      <div class="nimbus-intro">
        <h2 style="font-size: 1.6rem; margin-bottom: 8px;">Nimbus Central Command</h2>
        <p style="font-size: 0.9rem;">Monitoring regional atmospheric disturbances. All inbound traffic is actively routed through the load balancer to prevent system overload.</p>
      </div>

      <div class="telemetry-grid">
        <div class="telemetry-box">
          <i data-lucide="wind"></i><span>Wind Spd</span><strong id="tel-wind">14 km/h</strong>
        </div>
        <div class="telemetry-box">
          <i data-lucide="gauge"></i><span>Pressure</span><strong id="tel-pres">1012 hPa</strong>
        </div>
        <div class="telemetry-box">
          <i data-lucide="droplets"></i><span>Humidity</span><strong id="tel-hum">78 %</strong>
        </div>
      </div>
      
      <div style="margin-bottom: 24px;">
        <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; font-weight: 700;">
          <i data-lucide="cpu" style="width: 14px; height: 14px;"></i> Compute Node Status
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div style="background: rgba(255,255,255,0.7); border: 1px solid var(--border-color); padding: 14px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
              <span>Alpha Core</span> <span id="cpu-alpha" style="color: var(--accent-cyan); font-family: var(--font-mono);">12%</span>
            </div>
            <div style="height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden;">
              <div id="bar-alpha" style="height: 100%; width: 12%; background: var(--accent-cyan); transition: width 0.3s ease-out;"></div>
            </div>
          </div>
          <div style="background: rgba(255,255,255,0.7); border: 1px solid var(--border-color); padding: 14px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
              <span>Beta Core</span> <span id="cpu-beta" style="color: var(--accent-violet); font-family: var(--font-mono);">14%</span>
            </div>
            <div style="height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden;">
              <div id="bar-beta" style="height: 100%; width: 14%; background: var(--accent-violet); transition: width 0.3s ease-out;"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="feature-list" style="margin-top: auto;">
        <div class="feature-item">
          <div class="feature-icon cyan-glow"><i data-lucide="cloud-lightning"></i></div>
          <div class="feature-text">
            <strong>Active Twin Stations</strong>
            <span>Nodes Alpha and Beta processing signals.</span>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-icon violet-glow"><i data-lucide="git-fork"></i></div>
          <div class="feature-text">
            <strong>AWS Load Balancer</strong>
            <span>Equitably distributing traffic bursts.</span>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-icon green-glow"><i data-lucide="database"></i></div>
          <div class="feature-text">
            <strong>Dedicated Database Node</strong>
            <span>Synchronizing historical weather logs via MySQL.</span>
          </div>
        </div>
      </div>
    </section>

    <section class="panel alb-simulator-panel">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="margin: 0;"><i data-lucide="radar"></i> Live Traffic Radar</h2>
      </div>
      <p style="font-size: 0.85rem; margin-bottom: 16px;">Transmit a signal pulse to observe the Load Balancer routing protocols in real-time.</p>
      
      <div class="topology-box radar-screen">
        <div class="radar-grid"></div>
        <div class="radar-crosshair-v"></div>
        <div class="radar-crosshair-h"></div>
        <div class="radar-rings"></div>
        <div class="radar-sweep-container"><div class="radar-sweep"></div></div>
        
        <svg class="topo-svg">
          <line class="topo-line" id="line-client-alb" x1="0" y1="0" x2="0" y2="0"></line>
          <line class="topo-line" id="line-alb-web1" x1="0" y1="0" x2="0" y2="0"></line>
          <line class="topo-line" id="line-alb-web2" x1="0" y1="0" x2="0" y2="0"></line>
          <line class="topo-line" id="line-web1-db" x1="0" y1="0" x2="0" y2="0"></line>
          <line class="topo-line" id="line-web2-db" x1="0" y1="0" x2="0" y2="0"></line>
        </svg>

        <div class="topo-node topo-client floating-slow" style="left: 20px; top: 105px;"><i data-lucide="radio-tower"></i><span>Uplink</span></div>
        <div class="topo-node topo-alb" style="left: 150px; top: 105px;"><i data-lucide="git-fork"></i><span>Director</span></div>
        <div class="topo-node topo-server-1 floating-fast" id="topo-web1" style="left: 300px; top: 30px;"><i data-lucide="radio-receiver"></i><span>Alpha</span></div>
        <div class="topo-node topo-server-2 floating-slow" id="topo-web2" style="left: 300px; top: 180px;"><i data-lucide="radio-receiver"></i><span>Beta</span></div>
        <div class="topo-node topo-database" id="topo-db" style="right: 20px; top: 105px;"><i data-lucide="database"></i><span>Vault</span></div>
      </div>

      <div class="alb-actions">
        <button id="ping-btn" class="btn" style="flex: 1; border-radius: 99px;"><i data-lucide="zap"></i> Transmit Pulse</button>
        <button id="reset-btn" class="btn secondary-btn" style="width: 48px; height: 48px; border-radius: 50%; padding: 0;" title="Reset Radar"><i data-lucide="rotate-ccw"></i></button>
        <label class="toggle-switch-container" style="background: white; padding: 6px 12px; border-radius: 99px; border: 1px solid var(--border-color);">
          <input type="checkbox" id="auto-ping">
          <span class="toggle-slider"></span>
          <span>Auto</span>
        </label>
      </div>

      <div class="stats-container" style="background: white; border-radius: 16px; padding: 16px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
        <div class="server-stat-bar">
          <div class="stat-label-row">
            <span style="color: var(--accent-cyan);">Station Alpha</span>
            <span id="inst1-count" style="background: #e0f2fe; padding: 2px 8px; border-radius: 8px;">0 pulses</span>
          </div>
          <div class="stat-bar-outer"><div id="inst1-bar" class="stat-bar-inner inner-inst1"></div></div>
        </div>
        <div class="server-stat-bar">
          <div class="stat-label-row">
            <span style="color: var(--accent-violet);">Station Beta</span>
            <span id="inst2-count" style="background: #f0f9ff; padding: 2px 8px; border-radius: 8px;">0 pulses</span>
          </div>
          <div class="stat-bar-outer"><div id="inst2-bar" class="stat-bar-inner inner-inst2"></div></div>
        </div>
      </div>

      <div id="console-log" class="activity-feed">
        <div class="feed-bubble system-bubble">[SYS] Radar calibrated. Awaiting transmission command.</div>
      </div>
    </section>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // CPU & Telemetry Simulator
    // ----------------------------------------------------
    const telWind = document.getElementById('tel-wind');
    const telPres = document.getElementById('tel-pres');
    const telHum = document.getElementById('tel-hum');
    
    const cpuAlphaTxt = document.getElementById('cpu-alpha');
    const cpuAlphaBar = document.getElementById('bar-alpha');
    const cpuBetaTxt = document.getElementById('cpu-beta');
    const cpuBetaBar = document.getElementById('bar-beta');

    setInterval(() => {
        // Telemetry Update
        const w = (12 + Math.random() * 5).toFixed(1);
        const p = Math.floor(1010 + Math.random() * 5);
        const h = Math.floor(75 + Math.random() * 8);
        telWind.innerText = `${w} km/h`;
        telPres.innerText = `${p} hPa`;
        telHum.innerText = `${h} %`;

        // Ambient CPU Update (only fluctuate if not currently spiking from a pulse)
        if (parseInt(cpuAlphaTxt.innerText) < 30) {
            const a = 4 + Math.floor(Math.random() * 12);
            cpuAlphaTxt.innerText = `${a}%`;
            cpuAlphaBar.style.width = `${a}%`;
        } else {
            // Decay spike quickly
            const currentA = parseInt(cpuAlphaTxt.innerText);
            cpuAlphaTxt.innerText = `${Math.floor(currentA * 0.5)}%`;
            cpuAlphaBar.style.width = `${Math.floor(currentA * 0.5)}%`;
        }

        if (parseInt(cpuBetaTxt.innerText) < 30) {
            const b = 4 + Math.floor(Math.random() * 12);
            cpuBetaTxt.innerText = `${b}%`;
            cpuBetaBar.style.width = `${b}%`;
        } else {
            const currentB = parseInt(cpuBetaTxt.innerText);
            cpuBetaTxt.innerText = `${Math.floor(currentB * 0.5)}%`;
            cpuBetaBar.style.width = `${Math.floor(currentB * 0.5)}%`;
        }
    }, 1500);

    // ----------------------------------------------------
    // Radar Logic
    // ----------------------------------------------------
    const pingBtn = document.getElementById('ping-btn');
    const resetBtn = document.getElementById('reset-btn');
    const autoPingCheckbox = document.getElementById('auto-ping');
    const consoleLog = document.getElementById('console-log');
    const inst1CountLabel = document.getElementById('inst1-count');
    const inst2CountLabel = document.getElementById('inst2-count');
    const inst1Bar = document.getElementById('inst1-bar');
    const inst2Bar = document.getElementById('inst2-bar');
    
    const svg = document.querySelector('.topo-svg');
    const lineClientAlb = document.getElementById('line-client-alb');
    const lineAlbWeb1 = document.getElementById('line-alb-web1');
    const lineAlbWeb2 = document.getElementById('line-alb-web2');
    const lineWeb1Db = document.getElementById('line-web1-db');
    const lineWeb2Db = document.getElementById('line-web2-db');

    const clientNode = document.querySelector('.topo-client');
    const albNode = document.querySelector('.topo-alb');
    const web1Node = document.getElementById('topo-web1');
    const web2Node = document.getElementById('topo-web2');
    const dbNode = document.getElementById('topo-db');
    const topoBox = document.querySelector('.topology-box');

    let inst1Count = 0; let inst2Count = 0; let requestNumber = 0; let autoPingInterval = null;

    function updateLines() {
      const boxRect = topoBox.getBoundingClientRect();
      const getCenter = (el) => { const r = el.getBoundingClientRect(); return { x: r.left - boxRect.left + r.width / 2, y: r.top - boxRect.top + r.height / 2 }; };
      const c = getCenter(clientNode); const a = getCenter(albNode); const w1 = getCenter(web1Node); const w2 = getCenter(web2Node); const d = getCenter(dbNode);

      lineClientAlb.setAttribute('x1', c.x); lineClientAlb.setAttribute('y1', c.y); lineClientAlb.setAttribute('x2', a.x); lineClientAlb.setAttribute('y2', a.y);
      lineAlbWeb1.setAttribute('x1', a.x); lineAlbWeb1.setAttribute('y1', a.y); lineAlbWeb1.setAttribute('x2', w1.x); lineAlbWeb1.setAttribute('y2', w1.y);
      lineAlbWeb2.setAttribute('x1', a.x); lineAlbWeb2.setAttribute('y1', a.y); lineAlbWeb2.setAttribute('x2', w2.x); lineAlbWeb2.setAttribute('y2', w2.y);
      lineWeb1Db.setAttribute('x1', w1.x); lineWeb1Db.setAttribute('y1', w1.y); lineWeb1Db.setAttribute('x2', d.x); lineWeb1Db.setAttribute('y2', d.y);
      lineWeb2Db.setAttribute('x1', w2.x); lineWeb2Db.setAttribute('y1', w2.y); lineWeb2Db.setAttribute('x2', d.x); lineWeb2Db.setAttribute('y2', d.y);
    }

    setTimeout(updateLines, 100);
    window.addEventListener('resize', updateLines);

    function appendLog(message, type = 'system') {
      const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
      const row = document.createElement('div');
      let bubbleClass = type === 'alpha' ? 'alpha-bubble' : (type === 'beta' ? 'beta-bubble' : (type === 'error' ? 'err-bubble' : 'system-bubble'));
      row.className = `feed-bubble ${bubbleClass}`;
      row.innerHTML = `<span style="opacity:0.5; margin-right:8px;">[${time}]</span> ${message}`;
      consoleLog.appendChild(row); consoleLog.scrollTop = consoleLog.scrollHeight;
      while (consoleLog.children.length > 30) consoleLog.removeChild(consoleLog.firstChild);
    }

    function updateStats() {
      const total = inst1Count + inst2Count; if (total === 0) return;
      const p1 = Math.round((inst1Count / total) * 100); const p2 = Math.round((inst2Count / total) * 100);
      inst1CountLabel.innerText = `${inst1Count} pulses (${p1}%)`; inst2CountLabel.innerText = `${inst2Count} pulses (${p2}%)`;
      inst1Bar.style.width = `${p1}%`; inst2Bar.style.width = `${p2}%`;
    }

    function createRipple(targetNode, isInst1) {
        const ripple = document.createElement('div');
        ripple.className = `sonar-ripple ${isInst1 ? 'ripple-cyan' : 'ripple-violet'}`;
        targetNode.appendChild(ripple);
        setTimeout(() => ripple.remove(), 1000);
    }

    function animatePacket(targetNode, callback) {
      const packet = document.createElement('div'); packet.className = 'packet';
      const isInst1 = targetNode === web1Node;
      packet.style.background = isInst1 ? '#38bdf8' : '#818cf8';
      packet.style.boxShadow = `0 0 16px 6px ${packet.style.background}`;
      topoBox.appendChild(packet);
      
      const boxRect = topoBox.getBoundingClientRect();
      const getCenter = (el) => { const r = el.getBoundingClientRect(); return { x: r.left - boxRect.left + r.width / 2, y: r.top - boxRect.top + r.height / 2 }; };
      const c = getCenter(clientNode); const a = getCenter(albNode); const t = getCenter(targetNode); const d = getCenter(dbNode);

      packet.style.left = `${c.x - 4}px`; packet.style.top = `${c.y - 4}px`; packet.style.opacity = '1';
      const anim1 = packet.animate([{ left: `${c.x - 4}px`, top: `${c.y - 4}px` }, { left: `${a.x - 4}px`, top: `${a.y - 4}px` }], { duration: 300, easing: 'ease-out' });
      lineClientAlb.classList.add('active');

      anim1.onfinish = () => {
        lineClientAlb.classList.remove('active'); albNode.style.borderColor = 'var(--accent-cyan)'; setTimeout(() => albNode.style.borderColor = 'rgba(56, 189, 248, 0.5)', 150);
        const anim2 = packet.animate([{ left: `${a.x - 4}px`, top: `${a.y - 4}px` }, { left: `${t.x - 4}px`, top: `${t.y - 4}px` }], { duration: 300, easing: 'ease-out' });
        const activeLine = isInst1 ? lineAlbWeb1 : lineAlbWeb2; activeLine.classList.add('active');

        anim2.onfinish = () => {
          activeLine.classList.remove('active'); targetNode.classList.add('active-node'); 
          
          createRipple(targetNode, isInst1);
          
          setTimeout(() => targetNode.classList.remove('active-node'), 200);
          const anim3 = packet.animate([{ left: `${t.x - 4}px`, top: `${t.y - 4}px` }, { left: `${d.x - 4}px`, top: `${d.y - 4}px` }], { duration: 300, easing: 'ease-out' });
          const dbLine = isInst1 ? lineWeb1Db : lineWeb2Db; dbLine.classList.add('active-db');

          anim3.onfinish = () => {
            dbLine.classList.remove('active-db'); packet.remove(); dbNode.classList.add('active-node'); setTimeout(() => dbNode.classList.remove('active-node'), 300);
            if (callback) callback();
          };
        };
      };
    }

    async function triggerPing() {
      requestNumber++; const startTime = performance.now();
      try {
        const response = await fetch('/health.php?t=' + Date.now());
        const duration = Math.round(performance.now() - startTime);
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const data = await response.json();
        const instance = data.instance || 'Unknown Server';
        const isInst1 = instance.includes('1') || instance.toLowerCase().includes('one') || instance.toLowerCase().includes('alpha') || instance === 'Local Instance';
        
        // Spike CPU visual based on which server got the ping
        const cpuSpike = 60 + Math.floor(Math.random() * 25);
        if (isInst1) {
            cpuAlphaTxt.innerText = `${cpuSpike}%`;
            cpuAlphaBar.style.width = `${cpuSpike}%`;
        } else {
            cpuBetaTxt.innerText = `${cpuSpike}%`;
            cpuBetaBar.style.width = `${cpuSpike}%`;
        }

        animatePacket(isInst1 ? web1Node : web2Node, () => {
          if (isInst1) { inst1Count++; appendLog(`> Signal #${requestNumber} routed to Alpha <span class="ping-time">${duration}ms</span>`, 'alpha'); } 
          else { inst2Count++; appendLog(`> Signal #${requestNumber} routed to Beta <span class="ping-time">${duration}ms</span>`, 'beta'); }
          updateStats();
        });
      } catch (err) { appendLog(`> System Error on Req #${requestNumber}: Routing failed`, 'error'); }
    }

    pingBtn.addEventListener('click', triggerPing);
    resetBtn.addEventListener('click', () => {
      inst1Count = 0; inst2Count = 0; requestNumber = 0;
      inst1CountLabel.innerText = '0 pulses (0%)'; inst2CountLabel.innerText = '0 pulses (0%)'; inst1Bar.style.width = '0%'; inst2Bar.style.width = '0%';
      consoleLog.innerHTML = '<div class="feed-bubble system-bubble">[SYS] Radar metrics reset to zero.</div>';
    });

    autoPingCheckbox.addEventListener('change', () => {
      if (autoPingCheckbox.checked) { appendLog('> Auto-pilot engaged. Commencing continuous sweep.', 'system'); triggerPing(); autoPingInterval = setInterval(triggerPing, 1800); } 
      else { appendLog('> Auto-pilot disengaged. Holding for manual input.', 'system'); clearInterval(autoPingInterval); }
    });
  });
  </script>
HTML;

$content = $adminPanel . $htmlContent;

renderPage('Dashboard', $content);
?>