{{-- resources/views/candidate/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Candidate Assessment Portal</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#f0f3f9;--white:#ffffff;--border:#dde3ed;--border2:#c8d0df;
  --accent:#2563eb;--accent-h:#1d4ed8;--green:#16a34a;--green-h:#15803d;
  --red:#dc2626;--warn:#d97706;
  --txt:#1e293b;--txt2:#475569;--mute:#94a3b8;--light:#f8fafc;
}
html,body{height:100%;overflow:hidden;font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--txt)}

/* ── SHELL ── */
.shell{display:grid;grid-template-columns:300px 1fr;height:100vh}

/* ── LEFT ── */
.left{background:var(--white);border-right:1px solid var(--border);padding:28px 22px;
  display:flex;flex-direction:column;gap:20px;overflow:hidden}

.brand{display:flex;align-items:center;gap:10px;padding-bottom:20px;border-bottom:1px solid var(--border)}
.brand-ico{width:38px;height:38px;background:var(--accent);border-radius:9px;
  display:grid;place-items:center;color:#fff;font-size:17px;font-weight:700;flex-shrink:0;font-family:'DM Serif Display',serif}
.brand-text strong{display:block;font-size:14px;font-weight:700;color:var(--txt)}
.brand-text span{font-size:11px;color:var(--mute)}

.portal-tag{font-size:10px;font-weight:600;color:var(--accent);text-transform:uppercase;
  letter-spacing:2px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;
  padding:3px 8px;display:inline-block;width:fit-content}

.hero-title{font-size:22px;font-weight:400;line-height:1.3;color:var(--txt);font-family:'DM Serif Display',serif}
.hero-title span{color:var(--accent);font-style:italic}
.hero-desc{font-size:11.5px;color:var(--txt2);line-height:1.8}

.stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.stat{background:var(--light);border:1px solid var(--border);border-radius:8px;
  padding:12px 8px;text-align:center}
.stat-num{font-size:20px;font-weight:700;color:var(--accent);font-family:'DM Serif Display',serif}
.stat-lbl{font-size:9px;color:var(--mute);text-transform:uppercase;letter-spacing:1.2px;margin-top:2px}

.divider-label{font-size:9px;font-weight:700;color:var(--mute);text-transform:uppercase;
  letter-spacing:2px;display:flex;align-items:center;gap:8px}
.divider-label::after{content:'';flex:1;height:1px;background:var(--border)}

.infolist{display:flex;flex-direction:column;gap:7px}
.infoitem{display:flex;align-items:flex-start;gap:9px;padding:9px 10px;background:var(--light);
  border:1px solid var(--border);border-radius:8px}
.infoitem-ico{font-size:14px;flex-shrink:0;margin-top:1px}
.infoitem-text{font-size:11px;color:var(--txt2);line-height:1.6}
.infoitem-text strong{color:var(--txt);display:block;font-size:11px;margin-bottom:1px}

.help-note{background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 12px;
  font-size:10px;color:#92400e;line-height:1.7;margin-top:auto}

/* ── RIGHT ── */
.right{display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:36px 48px;overflow:hidden;background:var(--bg);position:relative}

/* Subtle grid background */
.right::before{
  content:'';position:absolute;inset:0;
  background-image:linear-gradient(var(--border) 1px,transparent 1px),
    linear-gradient(90deg,var(--border) 1px,transparent 1px);
  background-size:40px 40px;opacity:0.4;pointer-events:none;
}

/* Welcome card */
.welcome-card{
  background:var(--white);border:1px solid var(--border);border-radius:16px;
  padding:44px 48px;max-width:580px;width:100%;text-align:center;position:relative;
  box-shadow:0 4px 32px rgba(37,99,235,.06);
  animation:rise .4s cubic-bezier(.22,.68,0,1.2) both;
}
@keyframes rise{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

.welcome-badge{
  display:inline-flex;align-items:center;gap:6px;background:#eff6ff;
  border:1px solid #bfdbfe;border-radius:20px;padding:5px 12px;
  font-size:10px;font-weight:700;color:var(--accent);text-transform:uppercase;
  letter-spacing:1.5px;margin-bottom:24px;
}
.badge-dot{width:6px;height:6px;border-radius:50%;background:var(--accent);
  animation:pulse 1.8s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}

.welcome-icon{
  width:72px;height:72px;background:linear-gradient(135deg,#dbeafe,#eff6ff);
  border:2px solid #bfdbfe;border-radius:20px;display:grid;place-items:center;
  font-size:30px;margin:0 auto 20px;
  box-shadow:0 4px 12px rgba(37,99,235,.12);
}

.welcome-title{
  font-family:'DM Serif Display',serif;font-size:30px;font-weight:400;
  color:var(--txt);line-height:1.25;margin-bottom:12px;
}
.welcome-title em{color:var(--accent);font-style:italic}

.welcome-desc{
  font-size:13px;color:var(--txt2);line-height:1.85;max-width:420px;margin:0 auto 28px;
}

/* Checklist */
.check-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:10px;
  margin-bottom:32px;text-align:left;
}
.check-item{
  display:flex;align-items:center;gap:9px;background:var(--light);
  border:1px solid var(--border);border-radius:8px;padding:10px 12px;
}
.check-mark{
  width:20px;height:20px;border-radius:50%;background:#dcfce7;border:1.5px solid var(--green);
  display:grid;place-items:center;flex-shrink:0;color:var(--green);font-size:10px;font-weight:700;
}
.check-text{font-size:11px;color:var(--txt2);font-weight:500}

/* CTA Button */
.btn-start{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  background:var(--accent);color:#fff;border:none;border-radius:9px;
  padding:14px 36px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;
  letter-spacing:.4px;cursor:pointer;transition:all .18s;width:100%;
  text-transform:uppercase;letter-spacing:1.2px;
}
.btn-start:hover{background:var(--accent-h);transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,.3)}
.btn-start:active{transform:translateY(0)}
.btn-arrow{font-size:16px;transition:transform .18s}
.btn-start:hover .btn-arrow{transform:translateX(4px)}

.note-below{
  margin-top:16px;font-size:10px;color:var(--mute);
  display:flex;align-items:center;justify-content:center;gap:5px;
}
.note-below::before{content:'🔒';font-size:11px}

@media(max-width:768px){
  .shell{grid-template-columns:1fr;height:auto}
  html,body{overflow:auto;height:auto}
  .left{border-right:none;border-bottom:1px solid var(--border)}
  .right{padding:24px 20px}
  .welcome-card{padding:28px 24px}
  .check-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="shell">

  {{-- ── LEFT PANEL ── --}}
  <div class="left">

    <div class="brand">
      <div class="brand-ico"></div>
      <div class="brand-text">
        <strong>Acme Portal</strong>
        <span>Candidate Assessment System</span>
      </div>
    </div>

    <div>
      <div class="portal-tag">Assessment Overview</div>
      <h2 class="hero-title" style="margin-top:10px">Your<br><span>Evaluation</span><br>At a Glance</h2>
      <p class="hero-desc" style="margin-top:10px">A structured, timed assessment to evaluate your aptitude, reasoning, and domain knowledge.</p>
    </div>

    <div class="stats">
      <div class="stat"><div class="stat-num">60</div><div class="stat-lbl">Minutes</div></div>
      <div class="stat"><div class="stat-num">45</div><div class="stat-lbl">Questions</div></div>
      <div class="stat"><div class="stat-num">3</div><div class="stat-lbl">Rounds</div></div>
    </div>

    <div class="divider-label">What to expect</div>

    <div class="infolist">
      <div class="infoitem">
        <span class="infoitem-ico">📝</span>
        <div class="infoitem-text">
          <strong>Registration Required</strong>
          Provide your personal details, qualifications, and verify your mobile number via OTP before beginning.
        </div>
      </div>
      <div class="infoitem">
        <span class="infoitem-ico">⏱️</span>
        <div class="infoitem-text">
          <strong>Timed Session</strong>
          The exam is 60 minutes long and auto-submits when time expires — no extensions are granted.
        </div>
      </div>
      <div class="infoitem">
        <span class="infoitem-ico">🖥️</span>
        <div class="infoitem-text">
          <strong>Proctored Environment</strong>
          Tab switching is monitored. Ensure you stay on the exam window at all times.
        </div>
      </div>
    </div>

    <div class="help-note">
      📋 Ensure a stable internet connection before starting. Have your documents ready for the qualification section.
    </div>

  </div>

  {{-- ── RIGHT PANEL — Welcome / Pre-Assessment Screen ── --}}
  <div class="right">

    <div class="welcome-card">

      <div class="welcome-badge">
        <span class="badge-dot"></span>
        Assessment Portal Open
      </div>

      <div class="welcome-icon">📋</div>

      <h1 class="welcome-title">
        Welcome to Your<br><em>Candidate Assessment</em>
      </h1>

      <p class="welcome-desc">
        Before you begin, please take a moment to review the guidelines below.
        This assessment is designed to fairly evaluate your skills and qualifications.
        Ensure you are in a quiet, distraction-free environment.
      </p>

      <div class="check-grid">
        <div class="check-item">
          <div class="check-mark">✓</div>
          <span class="check-text">Stable internet connection</span>
        </div>
        <div class="check-item">
          <div class="check-mark">✓</div>
          <span class="check-text">Quiet, distraction-free space</span>
        </div>
        <div class="check-item">
          <div class="check-mark">✓</div>
          <span class="check-text">Valid mobile number ready</span>
        </div>
        <div class="check-item">
          <div class="check-mark">✓</div>
          <span class="check-text">Academic documents nearby</span>
        </div>
        <div class="check-item">
          <div class="check-mark">✓</div>
          <span class="check-text">No tab switching during exam</span>
        </div>
        <div class="check-item">
          <div class="check-mark">✓</div>
          <span class="check-text">60 minutes of uninterrupted time</span>
        </div>
      </div>

      <a href="/admin">
        <button class="btn-start">
          Begin Registration &amp; Assessment
          <span class="btn-arrow">→</span>
        </button>
      </a>

      <p class="note-below">Your session data is encrypted and kept secure</p>

    </div>

  </div>

</div>

</body>
</html>