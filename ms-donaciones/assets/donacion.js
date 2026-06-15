/* =============================================================
   MÓDULO SANITARIO — Formulario de Donación
   Archivo único: React 18 + CSS inlineado + Babel standalone
   Subir a: wp-content/plugins/ms-donaciones/assets/donacion.js
   ============================================================= */

(function () {
  const font = document.createElement("link");
  font.rel = "stylesheet";
  font.href = "https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap";
  document.head.appendChild(font);

  const style = document.createElement("style");
  style.textContent = `
:root {
  --primary: #0B6FB8; --primary-dark: #084d80;
  --primary-tint: #E6F4FB; --primary-tint-2: #F4FAFD;
  --accent: #F26B5C; --accent-dark: #d94f3f;
  --ink: #0F1B2D; --ink-2: #4A5A6E; --ink-3: #7A8696;
  --line: #E2EAF1; --line-2: #EDF2F6; --bg: #F6F9FC; --white: #FFFFFF;
  --success: #1F8A5B;
  --shadow-sm: 0 1px 2px rgba(11,27,45,.04), 0 2px 8px rgba(11,27,45,.04);
  --shadow-md: 0 2px 8px rgba(11,27,45,.05), 0 12px 32px rgba(11,27,45,.08);
  --radius: 16px; --radius-lg: 22px;
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); color: var(--ink); font-size: 16px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
img { max-width: 100%; display: block; } button { font: inherit; cursor: pointer; } input, select, textarea { font: inherit; } a { color: var(--primary); text-decoration: none; }
#ms-donacion-root {
  width: min(1120px, calc(100vw - 32px));
  max-width: 100%;
  margin: 0 auto;
}
.topbar { width: 100%; margin: 0 auto; background: var(--white); border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 20; }
.topbar-inner { width: 100%; margin: 0 auto; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.topbar-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--ink-2); font-weight: 500; padding: 8px 12px; border-radius: 999px; transition: background .15s; background: transparent; border: 0; text-decoration: none; }
.topbar-back:hover { background: var(--primary-tint); color: var(--primary); }
.stepper { max-width: 720px; margin: 0 auto; padding: 10px 20px 18px; display: flex; align-items: center; gap: 8px; }
.step { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.step-num { width: 28px; height: 28px; border-radius: 50%; background: var(--line-2); color: var(--ink-3); display: grid; place-items: center; font-size: 13px; font-weight: 700; transition: all .25s; }
.step.active .step-num { background: var(--primary); color: white; box-shadow: 0 0 0 4px var(--primary-tint); }
.step-label { font-size: 13px; font-weight: 600; color: var(--ink-3); transition: color .25s; }
.step.active .step-label { color: var(--ink); }
.step-line { flex: 1; height: 2px; background: var(--line-2); border-radius: 1px; transition: background .25s; min-width: 24px; }
.step-line.active { background: var(--primary); }
@media (max-width: 640px) { .step-label { display: none; } .stepper { padding: 8px 20px 14px; gap: 12px; } .step-line { min-width: 40px; } }
.content { width: 100%; margin: 0 auto; padding: 28px 20px 60px; }
.eyebrow { display: inline-block; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--accent); background: #fdf0ee; padding: 6px 12px; border-radius: 999px; margin-bottom: 14px; }
h1 { font-size: clamp(28px, 4vw, 40px); line-height: 1.1; letter-spacing: -.02em; margin: 0 0 12px; font-weight: 800; color: var(--ink); }
h1 em { font-style: normal; background: linear-gradient(180deg, transparent 60%, #ffd9d4 60%); padding: 0 4px; }
.lede { font-size: 16px; color: var(--ink-2); margin: 0 0 8px; }


.step1-grid{display:grid;grid-template-columns:minmax(320px,.9fr) minmax(360px,1fr);gap:32px;align-items:start;}

.hero{position:sticky;top:120px;display:flex;flex-direction:column;gap:16px;}

.hero-photo{position:relative;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-md);aspect-ratio:4/5;background:var(--line-2);}

.hero-photo img{width:100%;height:100%;object-fit:cover;}

.hero-photo-cap{position:absolute;left:14px;bottom:14px;background:rgba(15,27,45,.72);color:#fff;font-size:11px;padding:8px 14px;border-radius:999px;backdrop-filter:blur(6px);}

.hero-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;}

.stat{background:#fff;border-radius:18px;padding:18px;border:1px solid var(--line);box-shadow:var(--shadow-sm);}

.stat-num{font-size:34px;font-weight:800;line-height:1;color:var(--primary);margin-bottom:8px;}

.stat-label{font-size:13px;line-height:1.35;color:var(--ink-2);}

.hero-quote{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:18px;padding:22px;}

.hero-quote p{font-size:15px;line-height:1.5;margin:0;}

.hero-quote cite{display:block;margin-top:10px;opacity:.82;font-size:12px;}

.form-card{background:#fff;border-radius:24px;padding:34px;min-width:0;overflow:hidden;box-shadow:var(--shadow-md);border:1px solid var(--line);}

.form-head h1{font-size:clamp(32px,4vw,46px);line-height:1.05;letter-spacing:-.03em;overflow-wrap:break-word;}

.lede{font-size:17px;max-width:520px;}

.topbar{overflow-x:hidden;}

@media(max-width:1040px){

.step1-grid{grid-template-columns:1fr;}

.hero{position:static;}

}

@media(max-width:900px){

.step1-grid{display:flex;flex-direction:column;}

.form-card{order:1;}

.hero{order:2;}

.hero-photo{aspect-ratio:16/9;max-height:300px;}

.hero-stats{grid-template-columns:1fr 1fr;}

.form-head h1{font-size:clamp(30px,7vw,42px);}

}

@media(max-width:640px){

.content{padding:20px 14px 44px;}

.form-card{padding:24px 20px;}

.form-head h1{font-size:36px;}

.hero-photo{max-height:260px;}

.stat{padding:16px;}

.stat-num{font-size:28px;}

}

@media(max-width:480px){

.hero-stats{grid-template-columns:1fr;}

.form-head h1{font-size:32px;}

}


.impact-strip { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #fdf3f2; border: 1px solid #f9d4cf; border-radius: var(--radius); margin-bottom: 22px; }
.impact-icon { width: 32px; height: 32px; flex-shrink: 0; background: var(--accent); color: white; border-radius: 50%; display: grid; place-items: center; }
.impact-strip p { margin: 0; font-size: 14px; color: var(--ink); line-height: 1.4; }
.impact-strip strong { color: var(--accent-dark); font-weight: 700; }
.saved-banner { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: #edf7f2; border: 1px solid #c3e6d4; border-radius: var(--radius); margin-bottom: 18px; color: var(--ink); }
.saved-banner svg { color: var(--success); flex-shrink: 0; }
.saved-banner p { margin: 0; font-size: 13px; line-height: 1.4; }
.fields { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
.field-row { display: grid; gap: 12px; }
.field-row.two { grid-template-columns: 1fr 1fr; }
@media (max-width: 480px) { .field-row.two { grid-template-columns: 1fr; } }
.field { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 13px; font-weight: 600; color: var(--ink-2); }
.field-label .optional { font-style: normal; color: var(--ink-3); font-weight: 500; font-size: 12px; }
.field input { border: 1.5px solid var(--line); border-radius: 12px; padding: 12px 14px; background: white; outline: 0; transition: border-color .15s, box-shadow .15s; font-size: 15px; color: var(--ink); width: 100%; }
.field input:hover { border-color: var(--ink-3); }
.field input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11,111,184,.14); }
.field-hint { font-size: 12px; color: var(--ink-3); }
.field-error { font-size: 12px; color: var(--accent-dark); font-weight: 600; }
.field.has-error input { border-color: var(--accent-dark); }
.err { font-size: 12px; color: var(--accent-dark); font-weight: 600; margin: 6px 0 0; }
.cta { width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 16px 24px; background: var(--accent); color: white; border: 0; border-radius: 14px; font-size: 16px; font-weight: 700; letter-spacing: .01em; box-shadow: 0 6px 20px rgba(242,107,92,.35); transition: transform .15s, box-shadow .15s, background .15s; text-decoration: none; }
.cta:hover { background: var(--accent-dark); transform: translateY(-1px); color: white; }
.cta:active { transform: translateY(0); }
.reassure { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 12px; color: var(--ink-3); margin-top: 14px; }
.reassure svg { color: var(--success); }
.step2-wrap, .step3-wrap { max-width: 720px; margin: 0 auto; }
.back-link { background: transparent; border: 0; padding: 0; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: var(--ink-2); margin-bottom: 18px; cursor: pointer; }
.back-link:hover { color: var(--primary); }
.step2-head { margin-bottom: 18px; }
.freq { border: 0; padding: 0; margin: 0 0 20px; }
.freq legend, .amount legend { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-3); padding: 0; margin-bottom: 10px; display: block; }
.freq-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: var(--bg); padding: 4px; border-radius: 14px; }
.freq-opt { position: relative; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; cursor: pointer; transition: all .15s; font-size: 14px; font-weight: 600; color: var(--ink-2); }
.freq-opt input { position: absolute; opacity: 0; pointer-events: none; }
.freq-opt.selected { background: white; color: var(--ink); box-shadow: var(--shadow-sm); }
.freq-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--line); flex-shrink: 0; transition: all .15s; }
.freq-opt.selected .freq-dot { border-color: var(--primary); background: radial-gradient(circle, var(--primary) 40%, transparent 50%); }
.freq-badge { margin-left: auto; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 999px; background: #fdf3f2; color: var(--accent-dark); letter-spacing: .04em; text-transform: uppercase; }
.amount { border: 0; padding: 0; margin: 0 0 22px; }
.amount-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, 1fr); }
.amount-chip, .amount-custom { position: relative; background: white; border: 1.5px solid var(--line); border-radius: 14px; padding: 14px 16px; display: flex; align-items: baseline; gap: 4px; font-weight: 700; color: var(--ink); transition: all .15s; text-align: left; }
.amount-chip { font-size: 18px; cursor: pointer; }
.amount-chip:hover { border-color: var(--primary); }
.amount-chip.selected, .amount-custom.selected { border-color: var(--primary); background: var(--primary-tint); box-shadow: 0 0 0 3px rgba(11,111,184,.12); }
.amount-grid .amount-custom { grid-column: 1 / -1; }
.amount-currency { color: var(--ink-3); font-size: 14px; font-weight: 600; }
.amount-value { font-size: 18px; }
.amount-iso { font-size: 11px; font-weight: 700; color: var(--ink-3); letter-spacing: .04em; margin-left: 4px; }
.amount-custom { padding: 10px 14px; }
.amount-custom input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font-size: 16px; font-weight: 700; padding: 4px 0; color: var(--ink); }
.amount-custom input::placeholder { color: var(--ink-3); font-weight: 500; }
.monthly-notice { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #e8f5ef; border: 1px solid #c3e6d4; border-radius: var(--radius); margin-bottom: 4px; font-size: 13px; color: var(--ink-2); }
.monthly-notice svg { color: var(--success); flex-shrink: 0; }
.methods-title { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-3); margin: 24px 0 12px; }
.methods { list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 12px; }
.method-card { width: 100%; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 16px; background: white; border: 1.5px solid var(--line); border-radius: var(--radius); padding: 18px; text-align: left; transition: all .18s; box-shadow: var(--shadow-sm); cursor: pointer; }
.method-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.method-card.disabled, .method-card:disabled { background: #f4f6f8; color: var(--ink-3); cursor: not-allowed; opacity: .68; transform: none; box-shadow: none; }
.method-card.disabled:hover, .method-card:disabled:hover { border-color: var(--line); transform: none; box-shadow: none; }
.method-card.disabled .method-icon, .method-card:disabled .method-icon { filter: grayscale(1); opacity: .62; }
.method-icon { width: 64px; height: 64px; border-radius: 14px; display: grid; place-items: center; flex-shrink: 0; }
.method-body { min-width: 0; }
.method-title-row { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 4px; }
.method-body h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--ink); }
.method-body p { margin: 0; font-size: 13px; color: var(--ink-2); line-height: 1.4; }
.method-tags { display: inline-flex; gap: 4px; flex-wrap: wrap; }
.tag { font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; background: var(--line-2); color: var(--ink-2); }
.tag-rec { background: #e8f5ef; color: var(--success); }
.method-arrow { color: var(--ink-3); transition: transform .18s, color .18s; flex-shrink: 0; }
.method-card:hover .method-arrow { transform: translateX(4px); color: var(--primary); }
@media (max-width: 480px) { .method-icon { width: 52px; height: 52px; } .method-card { padding: 14px; gap: 12px; } }
.trust-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 18px; background: white; border: 1px solid var(--line); border-radius: var(--radius); }
@media (max-width: 600px) { .trust-row { grid-template-columns: 1fr; } }
.trust-item { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--ink-2); }
.trust-item svg { color: var(--success); flex-shrink: 0; }
.trust-item div { display: flex; flex-direction: column; gap: 2px; }
.trust-item strong { color: var(--ink); font-size: 13px; }
.step3-card { background: white; border-radius: var(--radius-lg); padding: 36px 28px; box-shadow: var(--shadow-md); border: 1px solid var(--line); text-align: center; margin-bottom: 18px; }
.step3-badge { width: 72px; height: 72px; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 18px; }
.step3-card h2 { font-size: 22px; font-weight: 800; margin: 0 0 8px; letter-spacing: -.01em; }
.step3-card .lede { margin-bottom: 14px; }
.step3-loader { display: inline-flex; gap: 6px; margin: 8px 0 22px; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); opacity: .3; animation: pulse 1.2s infinite ease-in-out; }
.dot:nth-child(2) { animation-delay: .15s; } .dot:nth-child(3) { animation-delay: .3s; }
@keyframes pulse { 0%,100%{opacity:.3;transform:scale(.8);}50%{opacity:1;transform:scale(1.1);} }
.bank-block { margin: 8px 0 22px; padding: 18px; background: var(--primary-tint-2); border: 1px solid var(--primary-tint); border-radius: var(--radius); text-align: left; }
.bank-block h4 { margin: 0 0 12px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--primary); }
.bank-block dl { margin: 0; display: grid; gap: 8px; }
.bank-block dl > div { display: grid; grid-template-columns: 96px 1fr; gap: 8px; padding: 6px 0; border-bottom: 1px dashed var(--line); font-size: 13px; }
.bank-block dl > div:last-of-type { border-bottom: 0; }
.bank-block dt { color: var(--ink-3); font-weight: 600; }
.bank-block dd { margin: 0; color: var(--ink); font-weight: 600; font-family: ui-monospace, 'SF Mono', Menlo, monospace; }
.bank-note { font-size: 12px; color: var(--ink-2); margin: 12px 0 0; }
.step3-actions { display: flex; flex-direction: column; gap: 8px; align-items: center; }
.ghost { font-size: 13px; color: var(--ink-2); font-weight: 600; padding: 8px 12px; background: transparent; border: 0; cursor: pointer; text-decoration: none; }
.site-footer { width: 100%; margin: 40px auto 0; background: white; border-top: 1px solid var(--line); }
.foot-inner { width: 100%; margin: 0 auto; padding: 24px 20px; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 18px; }
.foot-left { display: flex; flex-direction: column; gap: 6px; }
.foot-left p { margin: 0; font-size: 12px; color: var(--ink-3); }
.foot-seals { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
.seal { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; font-size: 11px; font-weight: 600; background: var(--bg); border: 1px solid var(--line); border-radius: 999px; color: var(--ink-2); }
.seal svg { color: var(--success); }
.foot-links { display: flex; gap: 14px; justify-content: flex-end; font-size: 13px; }
.foot-links a { color: var(--ink-2); }
@media (max-width: 700px) { .foot-inner { grid-template-columns: 1fr; text-align: center; } .foot-links { justify-content: center; } .foot-left { align-items: center; } }
.modal-backdrop { position: fixed; inset: 0; background: rgba(15,27,45,.55); backdrop-filter: blur(4px); display: grid; place-items: center; padding: 20px; z-index: 100; animation: fade-in .2s ease-out; }
@keyframes fade-in { from{opacity:0;}to{opacity:1;} }
.modal { background: white; border-radius: var(--radius-lg); max-width: 460px; width: 100%; padding: 32px 28px 24px; box-shadow: 0 24px 64px rgba(11,27,45,.25); position: relative; text-align: center; animation: pop-in .25s cubic-bezier(.16,1,.3,1); }
@keyframes pop-in { from{opacity:0;transform:translateY(12px) scale(.97);}to{opacity:1;transform:none;} }
.modal-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border-radius: 50%; background: var(--bg); border: 0; font-size: 22px; line-height: 1; color: var(--ink-3); display: grid; place-items: center; transition: background .15s, color .15s; cursor: pointer; }
.modal-icon { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; background: #e8f5ef; color: var(--success); display: grid; place-items: center; }
.modal h2 { font-size: 22px; font-weight: 800; margin: 0 0 8px; letter-spacing: -.01em; }
.modal-lede { font-size: 14px; color: var(--ink-2); margin: 0 0 18px; }
.modal-lede strong { color: var(--ink); }
.modal-card { display: flex; align-items: flex-start; gap: 10px; text-align: left; background: #fdf3f2; border: 1px solid #f9d4cf; padding: 14px; border-radius: var(--radius); margin-bottom: 18px; }
.modal-card p { margin: 0; font-size: 13px; color: var(--ink); line-height: 1.45; }
.modal-actions { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
.ghost-btn { background: transparent; border: 1.5px solid var(--line); color: var(--ink-2); padding: 12px 18px; border-radius: 14px; font-size: 14px; font-weight: 600; transition: all .15s; cursor: pointer; }
.modal-foot { margin: 0; font-size: 11px; color: var(--ink-3); display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
.modal-foot svg { color: var(--success); }
`;
  document.head.appendChild(style);
})();

const _app = document.createElement("script");
_app.type = "text/babel";
_app.setAttribute("data-presets", "react");
_app.textContent = `
const { useState, useEffect } = React;

const CONFIG = window.MS_DONACIONES?.labels || {};
const cfg = (key, fallback = "") => CONFIG[key] || fallback;
const cfgNum = (key, fallback = 0) => {
  const value = parseInt(CONFIG[key], 10);
  return Number.isFinite(value) ? value : fallback;
};
const cfgList = (key, fallback = []) => {
  const raw = CONFIG[key];
  if (!raw) return fallback;
  return String(raw).split(",").map(item => item.trim()).filter(Boolean);
};

const FIELD_LABELS = window.MS_DONACIONES?.labels || {
  nombre: "Nombre",
  apellido: "Apellido",
  email: "Email",
  dni: "DNI",
  telefono: "Teléfono",
};

const FOTO_URL =
  CONFIG.foto_url ||
  "https://modulosanitario.org/wp-content/uploads/2025/08/banos-portadad-_0003_IMG-20250209-WA0023-1-768x768.jpg";

const HERO_CAPTION =
  CONFIG.hero_caption ||
  "Familia Pereyra · Florencio Varela · 2025";

const Ic = {
  Lock:   (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...p}><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 1 1 8 0v4"/></svg>,
  Shield: (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...p}><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="m9 12 2 2 4-4"/></svg>,
  Check:  (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...p}><path d="m5 12 5 5L20 7"/></svg>,
  Arrow:  (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...p}><path d="M5 12h14"/><path d="m13 5 7 7-7 7"/></svg>,
  Back:   (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...p}><path d="M19 12H5"/><path d="m11 19-7-7 7-7"/></svg>,
  Heart:  (p) => <svg viewBox="0 0 24 24" fill="currentColor" {...p}><path d="M12 21s-7.5-4.7-9.5-9.3C1 7.8 3.6 4 7.4 4c2 0 3.5 1.1 4.6 2.7C13.1 5.1 14.6 4 16.6 4 20.4 4 23 7.8 21.5 11.7 19.5 16.3 12 21 12 21z"/></svg>,
  Spark:  (p) => <svg viewBox="0 0 24 24" fill="currentColor" {...p}><path d="M12 2 13.8 9 21 11l-7.2 2L12 20l-1.8-7L3 11l7.2-2L12 2z"/></svg>,
  MP:     (p) => <svg viewBox="0 0 48 48" fill="none" {...p}><rect x="4" y="12" width="40" height="24" rx="6" fill="#00B1EA"/><path d="M14 24c4-6 12-6 16 0" stroke="#fff" strokeWidth="2.5" strokeLinecap="round"/><circle cx="22" cy="24" r="2.5" fill="#fff"/></svg>,
  CardL:  (p) => <svg viewBox="0 0 48 48" fill="none" {...p}><rect x="4" y="10" width="40" height="28" rx="5" fill="#0B6FB8"/><rect x="4" y="16" width="40" height="5" fill="#08527d"/><rect x="9" y="27" width="10" height="3" rx="1" fill="#fff" opacity=".9"/><circle cx="36" cy="31" r="3.5" fill="#F26B5C"/><circle cx="40" cy="31" r="3.5" fill="#FFC857" opacity=".8"/></svg>,
  CardI:  (p) => <svg viewBox="0 0 48 48" fill="none" {...p}><circle cx="24" cy="24" r="18" fill="#1F3A5F"/><ellipse cx="24" cy="24" rx="8" ry="18" stroke="#fff" strokeWidth="1.5" fill="none"/><path d="M6 24h36M9 16h30M9 32h30" stroke="#fff" strokeWidth="1.5"/></svg>,
  Bank:   (p) => <svg viewBox="0 0 48 48" fill="none" {...p}><path d="M24 6 6 16h36L24 6z" fill="#0B6FB8"/><rect x="9" y="18" width="4" height="16" fill="#0B6FB8"/><rect x="17" y="18" width="4" height="16" fill="#0B6FB8"/><rect x="27" y="18" width="4" height="16" fill="#0B6FB8"/><rect x="35" y="18" width="4" height="16" fill="#0B6FB8"/><rect x="6" y="36" width="36" height="4" rx="1" fill="#0B6FB8"/></svg>,
};

function Logo({ size = 40 }) {
  return (
    <div style={{ display:"flex", alignItems:"center", gap:10 }}>
      <svg width={size} height={size} viewBox="0 0 40 40" fill="none">
        <rect x="2" y="2" width="16" height="16" rx="3" fill="var(--primary)"/>
        <rect x="22" y="2" width="16" height="16" rx="3" fill="var(--primary)" opacity=".55"/>
        <rect x="2" y="22" width="16" height="16" rx="3" fill="var(--primary)" opacity=".75"/>
        <rect x="22" y="22" width="16" height="16" rx="3" fill="var(--accent)"/>
      </svg>
      <div style={{ lineHeight:1, display:"flex", flexDirection:"column", gap:2 }}>
        <span style={{ fontWeight:800, fontSize:14, color:"var(--primary)", letterSpacing:".02em" }}>MÓDULO</span>
        <span style={{ fontWeight:600, fontSize:12, color:"var(--ink-2)", letterSpacing:".18em" }}>SANITARIO</span>
      </div>
    </div>
  );
}

function TopBar({ step }) {
  return (
    <header className="topbar">
      <div className="topbar-inner">
        <Logo />
        <a href={cfg("site_back_url", "/inicio")} className="topbar-back">
          <Ic.Back width="16" height="16" /> {cfg("site_back_label", "Volver al sitio")}
        </a>
      </div>
      <div className="stepper">
        {[[cfg("stepper_1_label", "Tus datos"),1],[cfg("stepper_2_label", "Método de pago"),2],[cfg("stepper_3_label", "Confirmar"),3]].map(([lbl,n],i) => (
          <React.Fragment key={n}>
            {i > 0 && <div className={"step-line "+(step>=n?"active":"")} />}
            <div className={"step "+(step>=n?"active":"")}>
              <span className="step-num">{n}</span>
              <span className="step-label">{lbl}</span>
            </div>
          </React.Fragment>
        ))}
      </div>
    </header>
  );
}

function ImpactStrip({ amount }) {
  const presets = cfgList("amount_presets", ["1500", "5000", "15000", "50000"])
    .map(n => parseInt(n, 10))
    .filter(Boolean)
    .sort((a, b) => a - b);
  const fallbackMessages = [
    "una familia accede a productos de higiene por un mes",
    "ayudás a financiar materiales para construir un baño digno",
    "cubrís el inodoro y la ducha de un módulo sanitario",
    "construís un módulo sanitario completo para una familia",
  ];
  const tiers = presets.map((preset, index) => ({
    amount: preset,
    msg: cfg("impact_tier_" + (index + 1), fallbackMessages[index] || fallbackMessages[fallbackMessages.length - 1]),
  }));
  const tier =
    [...tiers].reverse().find(t => amount >= t.amount) ||
    tiers[0] ||
    { msg: fallbackMessages[0] };

  return (
    <div className="impact-strip">
      <div className="impact-icon"><Ic.Spark width="16" height="16" /></div>
      <p>Con <strong>\${amount.toLocaleString("es-AR")} ARS</strong>, {tier.msg}.</p>
    </div>
  );
}

function Field({ label, error, hint, optional, ...props }) {
  return (
    <label className={"field "+(error?"has-error":"")}>
      <span className="field-label">{label} {optional && <em className="optional">(opcional)</em>}</span>
      <input {...props} />
      {hint && !error && <span className="field-hint">{hint}</span>}
      {error && <span className="field-error">{error}</span>}
    </label>
  );
}

function TrustRow() {
  return (
    <div className="trust-row">
      <div className="trust-item"><Ic.Lock width="16" height="16"/><div><strong>{cfg("trust_1_title", "Pago seguro")}</strong><span>{cfg("trust_1_text", "SSL 256-bit")}</span></div></div>
      <div className="trust-item"><Ic.Shield width="16" height="16"/><div><strong>{cfg("trust_2_title", "Sitio verificado")}</strong><span>{cfg("trust_2_text", "PCI-DSS · MP")}</span></div></div>
      <div className="trust-item"><Ic.Check width="16" height="16"/><div><strong>{cfg("trust_3_title", "ONG inscripta")}</strong><span>{cfg("trust_3_text", "IGJ · Ley 27.260")}</span></div></div>
    </div>
  );
}

/* ── STEP 1 ── */
function Step1({ data, setData, onNext, savedForLater }) {
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState(null);

  const validate = () => {
    const e = {};

    // Nombre
    if (!data.nombre?.trim()) {
      e.nombre = "Ingresá tu nombre";
    } else if (data.nombre.trim().length < 2) {
      e.nombre = "El nombre debe tener al menos 2 caracteres";
    } else if (!/^[a-záéíóúñüA-ZÁÉÍÓÚÑÜ\s'\-]+$/.test(data.nombre.trim())) {
      e.nombre = "El nombre solo puede contener letras y espacios";
    }

    // Apellido
    if (!data.apellido?.trim()) {
      e.apellido = "Ingresá tu apellido";
    } else if (data.apellido.trim().length < 2) {
      e.apellido = "El apellido debe tener al menos 2 caracteres";
    } else if (!/^[a-záéíóúñüA-ZÁÉÍÓÚÑÜ\s'\-]+$/.test(data.apellido.trim())) {
      e.apellido = "El apellido solo puede contener letras y espacios";
    }

    // Email
    if (!data.email?.trim()) {
      e.email = "Ingresá tu email";
    } else {
      const em = data.email.trim();
      const atPos = em.indexOf("@");
      const dotPos = em.lastIndexOf(".");
      const emailValido = atPos > 0 && dotPos > atPos + 1 && dotPos < em.length - 1;
      if (!emailValido) {
        e.email = "El email no tiene un formato válido (ej: nombre@ejemplo.com)";
      }
    }

    // DNI
    if (!data.dni?.trim()) {
      e.dni = "Ingresá tu DNI";
    } else {
      const dniTiene = data.dni.split("").some(function(c){ return c < "0" || c > "9"; });
      if (dniTiene) {
        e.dni = "El DNI solo puede contener números";
      } else if (data.dni.trim().length < 7 || data.dni.trim().length > 8) {
        e.dni = "El DNI debe tener 7 u 8 dígitos";
      }
    }
    // Teléfono (opcional)
    if (data.telefono?.trim()) {
      const telTiene = data.telefono.split("").some(function(c){ return c < "0" || c > "9"; });
      if (telTiene) {
        e.telefono = "El teléfono solo puede contener números";
      } else if (data.telefono.trim().length < 10) {
        e.telefono = "El teléfono debe tener al menos 10 dígitos";
      }
    }

    return e;
  };

  const ch = (k) => (ev) => setData({ ...data, [k]: ev.target.value });
  const bl = (k) => () => setTouched({ ...touched, [k]: true });

  const submit = async (ev) => {
    ev.preventDefault();
    const e = validate();
    setErrors(e);
    setTouched({ nombre:true, apellido:true, email:true, dni:true, telefono:true });
    if (Object.keys(e).length === 0) {
      setSubmitting(true);
      setSubmitError(null);

      try {
        await onNext();
      } catch (err) {
        setSubmitError(err?.message || "No pudimos guardar tus datos. Intentá de nuevo.");
      } finally {
        setSubmitting(false);
      }
    }
  };

  useEffect(() => { if (Object.keys(touched).length) setErrors(validate()); }, [data]);
  const showErr = (k) => touched[k] && errors[k];

  return (
    <div className="step1-grid">
      <aside className="hero">
        <div className="hero-photo">
          <img src={FOTO_URL} alt={cfg("hero_image_alt", "Familia con su nuevo baño digno")} />
          <div className="hero-photo-cap">{HERO_CAPTION}</div>
        </div>
        <div className="hero-stats">
          <div className="stat"><div className="stat-num">{cfg("hero_stat_1_number", "6M")}</div><div className="stat-label">{cfg("hero_stat_1_label", "de personas en Argentina viven sin baño")}</div></div>
          <div className="stat"><div className="stat-num">{cfg("hero_stat_2_number", "+1.200")}</div><div className="stat-label">{cfg("hero_stat_2_label", "módulos sanitarios construidos desde 2014")}</div></div>
        </div>
        <blockquote className="hero-quote">
          <p>{cfg("hero_quote_text", "\\\"Antes mis hijos hacían sus necesidades en una letrina afuera. Ahora tienen un baño que les da dignidad.\\\"")}</p>
          <cite>{cfg("hero_quote_author", "— Carolina, beneficiaria, Quilmes")}</cite>
        </blockquote>
      </aside>
      <main className="form-card">
        <div className="form-head">
          <span className="eyebrow">{cfg("step1_eyebrow", "Doná en 2 pasos")}</span>
          <h1>{cfg("step1_title_before", "Construyamos juntos un")} <em>{cfg("step1_title_highlight", "baño digno")}</em>{cfg("step1_title_after", ".")}</h1>
          <p className="lede">{cfg("step1_lede", "Cada donación es una familia que deja de defecar al aire libre.")}</p>
        </div>
        {savedForLater && (
          <div className="saved-banner"><Ic.Check width="16" height="16"/><p>{cfg("saved_banner_text", "Tus datos están guardados. Cuando quieras, completá tu donación.")}</p></div>
        )}
        <div className="impact-strip">
          <div className="impact-icon"><Ic.Spark width="16" height="16"/></div>
          <p>{cfg("step1_impact_text", "Con tu donación, una familia accede a un baño digno por primera vez.")}</p>
        </div>
        <form onSubmit={submit} noValidate>
          {submitError && <p className="field-error" style={{marginTop:0}}>{submitError}</p>}
          <div className="fields">
            <div className="field-row two">
              <Field label={FIELD_LABELS.nombre} value={data.nombre} onChange={ch("nombre")} onBlur={bl("nombre")} error={showErr("nombre")&&errors.nombre} autoComplete="given-name" required />
              <Field label={FIELD_LABELS.apellido} value={data.apellido} onChange={ch("apellido")} onBlur={bl("apellido")} error={showErr("apellido")&&errors.apellido} autoComplete="family-name" required />
            </div>
            <Field label={FIELD_LABELS.email} type="email" value={data.email} onChange={ch("email")} onBlur={bl("email")} error={showErr("email")&&errors.email} autoComplete="email" required hint={cfg("email_hint", "Te enviaremos el comprobante.")} />
            <Field label={FIELD_LABELS.dni} type="text" inputMode="numeric" value={data.dni} onChange={ch("dni")} onBlur={bl("dni")} error={showErr("dni")&&errors.dni} required hint={cfg("dni_hint", "Requerido por Mercado Pago para identificar el pago.")} />
            <Field label={FIELD_LABELS.telefono} type="tel" value={data.telefono} onChange={ch("telefono")} onBlur={bl("telefono")} error={showErr("telefono")&&errors.telefono} autoComplete="tel" optional hint={cfg("telefono_hint", "Solo si querés que te contactemos.")} />
          </div>
          <button type="submit" className="cta" disabled={submitting}><span>{submitting ? "Guardando..." : cfg("step1_button", "Continuar")}</span><Ic.Arrow width="20" height="20"/></button>
          <div className="reassure"><Ic.Lock width="14" height="14"/> {cfg("step1_reassure", "Tus datos están protegidos. No los compartimos con terceros.")}</div>
        </form>
      </main>
    </div>
  );
}

/* ── STEP 2 ── */
function Step2({ data, amount, setAmount, frequency, setFrequency, onBack, onSelect }) {
  const [customAmount, setCustomAmount] = useState(false);
  const [amountError, setAmountError] = useState(null);
  const presets = cfgList("amount_presets", ["1500", "5000", "15000", "50000"]).map(n => parseInt(n, 10)).filter(Boolean);
  const minAmount = cfgNum("min_amount", 100);

  const handleSelect = (id) => {
    if (!amount || amount < minAmount) { setAmountError(cfg("amount_error", "Elegí un monto válido") + " (mínimo $" + minAmount + ")"); return; }
    setAmountError(null);
    onSelect(id);
  };

  const allMethods = [
    { id:"mp",    name:cfg("method_mp_name", "Mercado Pago"), desc:cfg("method_mp_desc", "Tarjeta, dinero en cuenta o efectivo en Pago Fácil/Rapipago."), tags:cfgList("method_mp_tags", ["Recomendado","Sin comisión extra"]), icon:<Ic.MP width="48" height="48"/>, color:"#00B1EA" },
    { id:"local", name:cfg("method_local_name", "Tarjeta local (Argentina)"), desc:cfg("method_local_desc", "Crédito o débito emitida en Argentina. Hasta 3 cuotas sin interés."), tags:cfgList("method_local_tags", ["Crédito y débito"]), icon:<Ic.CardL width="48" height="48"/>, color:"#0B6FB8" },
    { id:"intl",  name:cfg("method_intl_name", "Tarjeta internacional"), desc:cfg("method_intl_desc", "Para donantes desde el exterior. Procesado en USD."), tags:cfgList("method_intl_tags", ["USD","Visa · Master · Amex"]), icon:<Ic.CardI width="48" height="48"/>, color:"#1F3A5F" },
    { id:"bank",  name:cfg("method_bank_name", "Transferencia bancaria"), desc:cfg("method_bank_desc", "Te mostramos los datos de la cuenta para hacer la transferencia."), tags:cfgList("method_bank_tags", ["CBU/Alias"]), icon:<Ic.Bank width="48" height="48"/>, color:"#0B6FB8" },
  ];
  const methods = frequency === "mensual" ? allMethods.filter(m => m.id === "mp") : allMethods;

  return (
    <div className="step2-wrap">
      <button className="back-link" onClick={onBack} type="button"><Ic.Back width="16" height="16"/> {cfg("step2_back_label", "Volver")}</button>
      <div className="step2-head">
        <span className="eyebrow">{cfg("step2_eyebrow", "Paso 2 de 2")}</span>
        <h1>{cfg("step2_title", "Elegí tu monto y cómo donar")}</h1>
        <p className="lede">{cfg("step2_lede_before_name", "¡Gracias")} <strong>{data.nombre||cfg("anonymous_name", "donante")}</strong>{cfg("step2_lede_after_name", "! Definí cuánto querés aportar y elegí el método de pago.")}</p>
      </div>
      <fieldset className="freq">
        <legend>{cfg("frequency_legend", "Frecuencia")}</legend>
        <div className="freq-row">
          {[{id:"unico",label:cfg("frequency_once_label", "Donación única")},{id:"mensual",label:cfg("frequency_monthly_label", "Mensual"),badge:cfg("frequency_monthly_badge", "+ impacto")}].map(f => (
            <label key={f.id} className={"freq-opt "+(frequency===f.id?"selected":"")}>
              <input type="radio" name="freq" value={f.id} checked={frequency===f.id} onChange={()=>setFrequency(f.id)}/>
              <span className="freq-dot"/>
              <span>{f.label}</span>
              {f.badge && <span className="freq-badge">{f.badge}</span>}
            </label>
          ))}
        </div>
      </fieldset>
      <fieldset className="amount">
        <legend>{cfg("amount_legend", "Elegí un monto")} {frequency==="mensual"&&<small>{cfg("amount_monthly_suffix", "por mes")}</small>}</legend>
        <div className="amount-grid">
          {presets.map(p => (
            <button key={p} type="button" className={"amount-chip "+(!customAmount&&amount===p?"selected":"")} onClick={()=>{setAmount(p);setCustomAmount(false);setAmountError(null);}}>
              <span className="amount-currency">\$</span><span className="amount-value">{p.toLocaleString("es-AR")}</span><span className="amount-iso">ARS</span>
            </button>
          ))}
          <div className={"amount-custom "+(customAmount?"selected":"")}>
            <span className="amount-currency">\$</span>
            <input type="number" inputMode="numeric" placeholder={cfg("custom_amount_placeholder", "Otro monto")} onFocus={()=>setCustomAmount(true)} onChange={e=>{setAmount(parseInt(e.target.value||"0",10));setAmountError(null);}} aria-label={cfg("custom_amount_placeholder", "Otro monto")}/>
          </div>
        </div>
        {amountError && <p className="err">{amountError}</p>}
      </fieldset>
      <ImpactStrip amount={amount||0}/>
      {frequency === "mensual" && (
        <div className="monthly-notice">
          <Ic.Heart width="14" height="14"/>
          <span>{cfg("monthly_notice", "Las donaciones mensuales se procesan exclusivamente a través de Mercado Pago. Podés cancelar cuando quieras.")}</span>
        </div>
      )}
      <h2 className="methods-title">{cfg("methods_title", "Método de pago")}</h2>
      <ul className="methods">
        {methods.map(m => {
          const isDisabled = m.id === "mp" && cfg("mp_connection_status", "unknown") !== "valid";
          const tags = isDisabled ? ["No disponible"] : m.tags;
          return (
          <li key={m.id}>
            <button type="button" className={"method-card " + (isDisabled ? "disabled" : "")} disabled={isDisabled} onClick={()=>!isDisabled && handleSelect(m.id)}>
              <div className="method-icon" style={{background:m.color+"12"}}>{m.icon}</div>
              <div className="method-body">
                <div className="method-title-row">
                  <h3>{m.name}</h3>
                  <div className="method-tags">{tags.map((t,i)=><span key={i} className={"tag "+(t==="Recomendado"?"tag-rec":"")}>{t}</span>)}</div>
                </div>
                <p>{isDisabled ? "No disponible por el momento" : m.desc}</p>
              </div>
              <div className="method-arrow"><Ic.Arrow width="20" height="20"/></div>
            </button>
          </li>
          );
        })}
      </ul>
      <TrustRow/>
    </div>
  );
}

/* ── STEP 3 ── */
function Step3({ data, amount, frequency, method, onBack, onRestart, guardarEnFormidable }) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const map = {
    mp:    { name:cfg("method_mp_name", "Mercado Pago"), color:"#00B1EA", icon:<Ic.MP    width="40" height="40"/> },
    local: { name:cfg("method_local_name", "Tarjeta local"), color:"#0B6FB8", icon:<Ic.CardL width="40" height="40"/> },
    intl:  { name:cfg("method_intl_name", "Tarjeta internacional"), color:"#1F3A5F", icon:<Ic.CardI width="40" height="40"/> },
    bank:  { name:cfg("method_bank_name", "Transferencia bancaria"), color:"#0B6FB8", icon:<Ic.Bank  width="40" height="40"/> },
  };
  const m = map[method] || map.mp;

  useEffect(() => {
    if (method === 'bank') { setLoading(false); return; }
    const endpoint = frequency === "mensual" ? "/crear-suscripcion" : "/crear-preferencia";
    fetch((window.MS_DONACIONES?.restUrl || "/wp-json/donacion/v1") + endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        monto:    amount,
        nombre:   data.nombre,
        apellido: data.apellido,
        email:    data.email,
        dni:      data.dni,
      })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.init_point) {
        guardarEnFormidable(data, {
          monto:              amount,
          metodo:             method,
          frecuencia:         frequency,
          preference_id:      res.id,
          external_reference: res.external_reference,
        });
        window.location.href = res.init_point;
      } else {
        console.error("MS Donaciones MP error:", res);
        setError(cfg("step3_error_text", "No pudimos conectar con Mercado Pago. Intentá de nuevo."));
        setLoading(false);
      }
    })
    .catch(() => {
      setError(cfg("step3_connection_error_text", "Error de conexión. Intentá de nuevo."));
      setLoading(false);
    });
  }, []);

  return (
    <div className="step3-wrap">
      <button className="back-link" onClick={onBack} type="button">
        <Ic.Back width="16" height="16"/> {cfg("step3_back_label", "Cambiar método de pago")}
      </button>
      <div className="step3-card">
        <div className="step3-badge" style={{background:m.color+"18", color:m.color}}>{m.icon}</div>

        {loading && method !== 'bank' && (
          <>
            <h2>{cfg("step3_loading_title", "Preparando tu donación...")}</h2>
            <p className="lede">{cfg("step3_loading_text_prefix", "Conectando con")} {m.name}. {cfg("step3_loading_text_suffix", "Un segundo.")}</p>
            <div className="step3-loader">
              <div className="dot"/><div className="dot"/><div className="dot"/>
            </div>
          </>
        )}

        {error && (
          <>
            <h2>{cfg("step3_error_title", "Algo salió mal")}</h2>
            <p className="lede" style={{color:"var(--accent-dark)"}}>{error}</p>
            <div className="step3-actions" style={{marginTop:20}}>
              <button type="button" className="cta" onClick={onBack}>{cfg("step3_retry_label", "Volver a intentar")}</button>
            </div>
          </>
        )}

        {method === 'bank' && (
          <>
            <h2>{cfg("bank_title", "Datos para transferencia")}</h2>
            <p className="lede">{cfg("bank_lede_prefix", "Donación de")} <strong>\${amount.toLocaleString("es-AR")} {cfg("bank_lede_middle", "ARS a nombre de")}</strong> <strong>{data.nombre} {data.apellido}</strong>.</p>
            <div className="bank-block">
              <h4>{cfg("bank_block_title", "Datos de la cuenta")}</h4>
              <dl>
                <div><dt>Titular</dt><dd>{cfg("bank_holder", "Asoc. Civil Módulo Sanitario")}</dd></div>
                <div><dt>CUIT</dt><dd>{cfg("bank_cuit", "30-71234567-8")}</dd></div>
                <div><dt>Banco</dt><dd>{cfg("bank_name", "Banco Galicia")}</dd></div>
                <div><dt>CBU</dt><dd>{cfg("bank_cbu", "0070123456789012345678")}</dd></div>
                <div><dt>Alias</dt><dd>{cfg("bank_alias", "MODULO.SANITARIO.AR")}</dd></div>
              </dl>
              <p className="bank-note">{cfg("bank_note", "Enviá el comprobante a")} <a href={"mailto:" + cfg("bank_email", "donaciones@modulosanitario.org")}>{cfg("bank_email", "donaciones@modulosanitario.org")}</a></p>
            </div>
            <div className="step3-actions">
              <button type="button" className="cta" onClick={onRestart}>{cfg("restart_button", "Hacer otra donación")}</button>
              <a href={cfg("site_back_url", "/inicio")} className="ghost">{cfg("site_back_label", "Volver al sitio")}</a>
            </div>
          </>
        )}
      </div>
      <TrustRow/>
    </div>
  );
}

/* ── MODAL ── */
function SavedDataModal({ data, onDonateNow, onDonateLater, onClose }) {
  useEffect(() => {
    const onKey = e => { if (e.key==="Escape") onClose(); };
    document.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    return () => { document.removeEventListener("keydown", onKey); document.body.style.overflow = ""; };
  }, [onClose]);
  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={e=>e.stopPropagation()}>
        <button type="button" className="modal-close" onClick={onClose}>×</button>
        <div className="modal-icon"><Ic.Check width="28" height="28"/></div>
        <h2>{cfg("modal_title_prefix", "¡Listo,")} {data.nombre||cfg("anonymous_name", "donante")}{cfg("modal_title_suffix", "! Guardamos tus datos.")}</h2>
        <p className="modal-lede">{cfg("modal_lede_prefix", "Te enviamos un correo a")} <strong>{data.email}</strong> {cfg("modal_lede_suffix", "para que puedas retomar tu donación cuando quieras.")}</p>
        <div className="modal-card"><Ic.Heart width="18" height="18" style={{color:"var(--accent)",flexShrink:0}}/><p><strong>{cfg("modal_card_title", "¿Querés donar ahora?")}</strong> {cfg("modal_card_text", "Te lleva 1 minuto y tu aporte se convierte hoy mismo en materiales para construir un baño digno.")}</p></div>
        <div className="modal-actions">
          <button type="button" className="cta" onClick={onDonateNow}><span>{cfg("modal_donate_now", "Sí, donar ahora")}</span><Ic.Arrow width="20" height="20"/></button>
          <button type="button" className="ghost-btn" onClick={onDonateLater}>{cfg("modal_donate_later", "Donar más tarde")}</button>
        </div>
        <p className="modal-foot"><Ic.Lock width="12" height="12"/> {cfg("modal_footer", "Tus datos están protegidos.")}</p>
      </div>
    </div>
  );
}

/* ── FOOTER ── */
function Footer() {
  return (
    <footer className="site-footer">
      <div className="foot-inner">
        <div className="foot-left"><Logo size={32}/><p>{cfg("footer_text", "Asoc. Civil sin fines de lucro · Buenos Aires, Argentina")}</p></div>
        <div className="foot-seals">
          <span className="seal"><Ic.Lock width="12" height="12"/> {cfg("footer_seal_1", "SSL Seguro")}</span>
          <span className="seal"><Ic.Shield width="12" height="12"/> {cfg("footer_seal_2", "PCI-DSS")}</span>
          <span className="seal"><Ic.Check width="12" height="12"/> {cfg("footer_seal_3", "ONG Verificada")}</span>
        </div>
        <div className="foot-links"><a href={cfg("footer_link_1_url", "#")}>{cfg("footer_link_1_label", "Términos")}</a><a href={cfg("footer_link_2_url", "#")}>{cfg("footer_link_2_label", "Privacidad")}</a><a href={cfg("footer_link_3_url", "#")}>{cfg("footer_link_3_label", "Contacto")}</a></div>
      </div>
    </footer>
  );
}

/* ── APP ── */
function App() {
  const [step, setStep]           = useState(1);
  const [data, setData]           = useState({ nombre:"", apellido:"", email:"", dni:"", telefono:"" });
  const [amount, setAmount]       = useState(cfgNum("default_amount", 5000));
  const [frequency, setFrequency] = useState("unico");
  const [method, setMethod]       = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [savedForLater, setSavedForLater] = useState(false);

  const goStep = (s) => { setStep(s); window.scrollTo({ top:0, behavior:"smooth" }); };

  const guardarEnFormidable = async (datos, extra = {}) => {
  try {

    const endpoint =
      (window.MS_DONACIONES?.restUrl || "/wp-json/donacion/v1")
      + "/guardar";

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        ...datos,
        ...extra
      })
    });

    const result = await response.json().catch(() => null);

    if (!response.ok || result?.crm_result?.success === false) {
      console.error('MS Donaciones CRM response:', result);
    }

    return result;

  } catch(e) {
    console.error('MS Donaciones save error:', e);
    throw e;
    }
  };

  return (
    <div>
      <TopBar step={step}/>
      <div className="content">
        {step===1 && <Step1 data={data} setData={setData} onNext={async()=>{
          const result = await guardarEnFormidable(data, { crm_event: "step_1_completed" });
          if (result?.crm_result?.success === false) {
            throw new Error(result.crm_result.airtable_error || result.crm_result.message || "No pudimos guardar tus datos.");
          }
          setShowModal(true);
        }} savedForLater={savedForLater}/>}
        {step===2 && <Step2 data={data} amount={amount} setAmount={setAmount} frequency={frequency} setFrequency={setFrequency} onBack={()=>goStep(1)} onSelect={id=>{setMethod(id);goStep(3);}}/>}
        {step===3 && <Step3 data={data} amount={amount} frequency={frequency} method={method} onBack={()=>goStep(2)} onRestart={()=>{setStep(1);setMethod(null);}} guardarEnFormidable={guardarEnFormidable}/>}
      </div>
      <Footer/>
      {showModal && (
        <SavedDataModal data={data}
          onDonateNow={()=>{ setShowModal(false); goStep(2); }}
          onDonateLater={()=>{ setShowModal(false); setSavedForLater(true); }}
          onClose={()=>setShowModal(false)}
        />
      )}
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("ms-donacion-root")).render(<App/>);
`;
const _currentScript = document.currentScript;

if (window.Babel && typeof window.Babel.transform === "function") {
  const _compiled = document.createElement("script");
  _compiled.text = window.Babel.transform(_app.textContent, {
    presets: ["react"],
  }).code;
  _currentScript.after(_compiled);
} else {
  _currentScript.after(_app);
}