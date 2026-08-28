<?php
namespace ScratchByPHP\Ui;

use ScratchByPHP\Scratch;
use ScratchByPHP\Session;
use ScratchByPHP\Watch\ProjectWatch;

final class ScratchApiWizard
{
    private array $options;

    public function __construct(private Scratch $scratch, array $options = [])
    {
        $this->options = array_merge([
            'session_key' => 'scratchbyphp_wizard',
            'allow_auth' => true,
            'allow_writes' => true,
            'cloud_request_handlers' => [],
            'clouddb_profiles' => [],
        ], $options);

        $this->ensurePhpSession();
        $bag =& $this->bag();
        $bag['csrf'] ??= bin2hex(random_bytes(24));
    }

    public function handle(): void
    {
        if (($_GET['scratchbyphp_wizard_api'] ?? '') !== '1') {
            return;
        }

        $this->ensurePhpSession();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new \RuntimeException('Wizard API yalnızca POST kabul eder.');
            }

            $payload = json_decode((string)file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = $_POST;
            }

            $csrf = (string)($payload['csrf'] ?? '');
            if (!hash_equals((string)($this->bag()['csrf'] ?? ''), $csrf)) {
                http_response_code(403);
                throw new \RuntimeException('Wizard CSRF doğrulaması başarısız.');
            }

            $action = trim((string)($payload['action'] ?? ''));
            $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];

            if ($action === 'wizard.meta') {
                $this->respond([
                    'ok' => true,
                    'version' => Scratch::version(),
                    'auth' => $this->authStatus(),
                    'actions' => $this->clientActions(),
                ]);
            }

            if ($action === 'auth.login') {
                if (!$this->options['allow_auth']) throw new \RuntimeException('Wizard login bu sitede kapalı.');
                $username = trim((string)($params['username'] ?? ''));
                $password = (string)($params['password'] ?? '');
                if ($username === '' || $password === '') throw new \InvalidArgumentException('Kullanıcı adı ve parola gerekli.');

                $session = $this->scratch->login($username, $password);
                $bag =& $this->bag();
                $bag['scratch_session_id'] = $session->sessionId();
                $bag['scratch_username'] = $session->username() ?: $username;
                unset($password, $params['password']);

                $this->respond([
                    'ok' => true,
                    'message' => 'Scratch oturumu açıldı.',
                    'auth' => $this->authStatus(),
                ]);
            }

            if ($action === 'auth.logout') {
                $bag =& $this->bag();
                unset($bag['scratch_session_id'], $bag['scratch_username']);
                $this->respond([
                    'ok' => true,
                    'message' => 'Scratch oturumu kapatıldı.',
                    'auth' => $this->authStatus(),
                ]);
            }

            $result = $this->execute($action, $params);

            $this->respond([
                'ok' => true,
                'action' => $action,
                'data' => $this->redact($this->normalize($result)),
                'code' => $this->snippet($action, $params),
                'auth' => $this->authStatus(),
            ]);
        } catch (\Throwable $e) {
            if (http_response_code() < 400) http_response_code(400);
            $this->respond([
                'ok' => false,
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'auth' => $this->authStatus(),
            ], false);
        }
    }

    public function render(array $options = []): string
    {
        $opts = array_merge([
            'tailwind_cdn' => true,
            'title' => 'ScratchByPHP Control Center',
            'button' => 'ScratchByPHP',
            'endpoint' => '?scratchbyphp_wizard_api=1',
            'start_open' => false,
            'width' => 980,
            'height' => 680,
        ], $options);

        $title = htmlspecialchars((string)$opts['title'], ENT_QUOTES, 'UTF-8');
        $button = htmlspecialchars((string)$opts['button'], ENT_QUOTES, 'UTF-8');
        $endpoint = json_encode((string)$opts['endpoint'], JSON_UNESCAPED_SLASHES);
        $csrf = json_encode((string)$this->bag()['csrf']);
        $startOpen = !empty($opts['start_open']) ? 'true' : 'false';
        $width = max(680, min(1400, (int)$opts['width']));
        $height = max(500, min(950, (int)$opts['height']));
        $tw = $opts['tailwind_cdn'] ? '<script src="https://cdn.tailwindcss.com"></script>' : '';

        $brandLogoPath = dirname(__DIR__, 2) . '/docs/assets/brand/scratchbyphp-icon-light.png';
        $brandLogoSrc = is_file($brandLogoPath)
            ? 'data:image/png;base64,' . base64_encode((string)file_get_contents($brandLogoPath))
            : '';
        $logo = '<div class="sbpw-logo" aria-hidden="true"><img src="' . htmlspecialchars($brandLogoSrc, ENT_QUOTES, 'UTF-8') . '" alt=""></div>';

        return $tw . <<<HTML
<style>
#sbpw-root,#sbpw-root *{box-sizing:border-box}
#sbpw-modal.sbpw-hidden{display:none!important}
#sbpw-card{resize:both;overflow:hidden;min-width:680px;min-height:500px;max-width:calc(100vw - 16px);max-height:calc(100vh - 16px)}
#sbpw-card.sbpw-max{left:8px!important;top:8px!important;width:calc(100vw - 16px)!important;height:calc(100vh - 16px)!important;transform:none!important;resize:none}
.sbpw-logo{width:38px;height:38px;border-radius:12px;background:#fff;display:grid;place-items:center;box-shadow:0 6px 20px rgba(133,92,214,.28);overflow:hidden;padding:2px}
.sbpw-logo img{display:block;width:100%;height:100%;object-fit:contain;border-radius:10px}
.sbpw-brand-gradient{background:linear-gradient(135deg,#855CD6 0%,#7448CC 57%,#ff9f1c 145%)}
.sbpw-scroll{scrollbar-width:thin;scrollbar-color:#c4b5fd transparent}
.sbpw-action.active{background:#f3efff;border-color:#855CD6;color:#5f35b5}
.sbpw-danger{border-color:#fecaca!important;background:#fff7f7!important}
@media(max-width:760px){#sbpw-card{min-width:0!important;min-height:0!important;width:calc(100vw - 12px)!important;height:calc(100vh - 12px)!important;left:6px!important;top:6px!important;transform:none!important;resize:none}.sbpw-sidebar{width:150px!important}}
</style>

<div id="sbpw-root" class="fixed z-[2147483000] bottom-5 right-5 font-sans text-slate-800">
  <button id="sbpw-open" type="button" class="sbpw-brand-gradient flex items-center gap-2 rounded-2xl text-white shadow-2xl px-4 py-3 font-bold border border-white/20">
    {$logo}<span>{$button}</span>
  </button>

  <div id="sbpw-modal" class="sbpw-hidden fixed inset-0 bg-slate-950/35 backdrop-blur-[2px]">
    <section id="sbpw-card" style="width:{$width}px;height:{$height}px" class="absolute top-12 left-1/2 -translate-x-1/2 bg-white rounded-[22px] shadow-2xl border border-violet-200 flex flex-col">
      <header id="sbpw-drag" class="sbpw-brand-gradient cursor-move select-none text-white px-4 py-3 flex items-center justify-between gap-3 shrink-0 rounded-t-[21px]">
        <div class="flex items-center gap-3 min-w-0">
          {$logo}
          <div class="min-w-0">
            <div class="font-extrabold text-[15px] truncate">{$title}</div>
            <div class="text-[11px] text-white/75 flex items-center gap-2">
              <span>v0.8.5 Wizard Pro</span><span>•</span><span id="sbpw-auth-label">Oturum kontrol ediliyor…</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-1">
          <button id="sbpw-max" type="button" title="Büyüt / geri al" class="w-9 h-9 rounded-xl hover:bg-white/15 text-lg">□</button>
          <button id="sbpw-close" type="button" title="Kapat" class="w-9 h-9 rounded-xl hover:bg-white/15 text-2xl leading-none">×</button>
        </div>
      </header>

      <div class="flex flex-1 min-h-0">
        <aside class="sbpw-sidebar w-[220px] shrink-0 bg-[#fbfaff] border-r border-violet-100 p-3 flex flex-col min-h-0">
          <div class="relative mb-3">
            <input id="sbpw-search" class="w-full rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-violet-300" placeholder="Özellik ara…">
          </div>
          <div id="sbpw-actions" class="sbpw-scroll overflow-auto pr-1 space-y-3"></div>

          <div class="mt-auto pt-3">
            <button id="sbpw-login-toggle" type="button" class="w-full rounded-xl border border-violet-200 bg-white px-3 py-2 text-xs font-bold text-[#6f42c1] hover:bg-violet-50">Scratch ile giriş</button>
          </div>
        </aside>

        <main class="flex-1 min-w-0 flex flex-col bg-white">
          <div id="sbpw-login-panel" class="hidden border-b border-violet-100 bg-violet-50/70 px-5 py-4">
            <div id="sbpw-login-form">
              <div class="flex items-center justify-between mb-3">
                <div><div class="font-bold text-sm">Scratch hesabı</div><div class="text-xs text-slate-500">Parola saklanmaz; oturum sunucu tarafındaki PHP session'da tutulur.</div></div>
              </div>
              <div class="grid md:grid-cols-[1fr_1fr_auto] gap-2">
                <input id="sbpw-user" autocomplete="username" class="rounded-xl border px-3 py-2 text-sm" placeholder="Kullanıcı adı">
                <input id="sbpw-pass" autocomplete="current-password" type="password" class="rounded-xl border px-3 py-2 text-sm" placeholder="Parola">
                <button id="sbpw-login" type="button" class="rounded-xl bg-[#855CD6] text-white px-4 py-2 text-sm font-bold">Giriş</button>
              </div>
            </div>
            <div id="sbpw-logged" class="hidden flex items-center justify-between gap-3">
              <div><div class="font-bold text-sm">Giriş yapıldı: <span id="sbpw-logged-user"></span></div><div class="text-xs text-slate-500">Authenticated Project/User/Studio ve Cloud özellikleri açık.</div></div>
              <button id="sbpw-logout" type="button" class="rounded-xl border border-red-200 text-red-600 bg-white px-3 py-2 text-xs font-bold">Çıkış</button>
            </div>
          </div>

          <div class="px-5 py-4 border-b border-slate-100 shrink-0">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div id="sbpw-action-title" class="font-extrabold text-lg">ScratchByPHP Wizard</div>
                <div id="sbpw-action-desc" class="text-xs text-slate-500 mt-1">Soldan bir özellik seç.</div>
              </div>
              <span id="sbpw-action-badge" class="rounded-full bg-violet-100 text-violet-700 text-[10px] font-extrabold px-2.5 py-1">READY</span>
            </div>
            <div id="sbpw-fields" class="grid md:grid-cols-2 xl:grid-cols-3 gap-2 mt-4"></div>
            <div class="flex items-center gap-2 mt-3">
              <button id="sbpw-run" type="button" class="rounded-xl bg-[#ff9f1c] hover:bg-[#ee8e0a] text-white px-5 py-2.5 text-sm font-extrabold shadow-sm">Çalıştır</button>
              <button id="sbpw-clear" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600">Temizle</button>
              <span id="sbpw-status" class="text-xs text-slate-400"></span>
            </div>
          </div>

          <div class="flex-1 min-h-0 grid lg:grid-cols-2">
            <section class="min-h-0 flex flex-col border-r border-slate-100">
              <div class="h-11 px-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <span class="text-xs font-extrabold text-slate-600">SONUÇ / JSON</span>
                <button id="sbpw-copy-result" class="text-[11px] font-bold text-[#855CD6]">Kopyala</button>
              </div>
              <pre id="sbpw-out" class="sbpw-scroll flex-1 min-h-0 overflow-auto bg-[#15121b] text-[#f8f5ff] p-4 text-xs whitespace-pre-wrap">Hazır.</pre>
            </section>
            <section class="min-h-0 flex flex-col">
              <div class="h-11 px-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <span class="text-xs font-extrabold text-slate-600">SCRATCHBYPHP KODU</span>
                <button id="sbpw-copy-code" class="text-[11px] font-bold text-[#ff9f1c]">Kopyala</button>
              </div>
              <pre id="sbpw-code" class="sbpw-scroll flex-1 min-h-0 overflow-auto bg-[#fffaf3] text-slate-800 p-4 text-xs whitespace-pre-wrap">// Bir özellik seç.</pre>
            </section>
          </div>

          <footer class="px-4 py-2 border-t border-slate-100 bg-slate-50 text-[10px] text-slate-500 flex items-center justify-between shrink-0">
            <span>ScratchByPHP • PHP ↔ Scratch</span>
            <span>Sürükle • Köşeden boyutlandır • □ ile tam ekran</span>
          </footer>
        </main>
      </div>
    </section>
  </div>
</div>

<script>
(()=>{
const endpoint={$endpoint},csrf={$csrf},startOpen={$startOpen};
const el=id=>document.getElementById(id);
const modal=el("sbpw-modal"),card=el("sbpw-card"),drag=el("sbpw-drag"),open=el("sbpw-open"),close=el("sbpw-close"),max=el("sbpw-max");
const actionsBox=el("sbpw-actions"),fields=el("sbpw-fields"),out=el("sbpw-out"),code=el("sbpw-code"),title=el("sbpw-action-title"),desc=el("sbpw-action-desc"),badge=el("sbpw-action-badge"),status=el("sbpw-status");
let meta=null,current=null,auth={authenticated:false,username:null};

const esc=s=>String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const api=async(action,params={})=>{
  const r=await fetch(endpoint,{method:"POST",headers:{"Content-Type":"application/json","Accept":"application/json"},credentials:"same-origin",body:JSON.stringify({csrf,action,params})});
  const j=await r.json().catch(()=>({ok:false,error:"Geçersiz JSON cevabı"}));
  if(!j.ok) throw new Error(j.error||("HTTP "+r.status));
  if(j.auth) setAuth(j.auth);
  return j;
};
function setAuth(a){auth=a||{};el("sbpw-auth-label").textContent=auth.authenticated?("@" + auth.username):"Public mod";el("sbpw-login-toggle").textContent=auth.authenticated?("@" + auth.username):"Scratch ile giriş";el("sbpw-login-form").classList.toggle("hidden",!!auth.authenticated);el("sbpw-logged").classList.toggle("hidden",!auth.authenticated);el("sbpw-logged-user").textContent=auth.username||""}
function show(){modal.classList.remove("sbpw-hidden"); if(!meta) loadMeta()}
function hide(){modal.classList.add("sbpw-hidden")}
open.onclick=show;close.onclick=hide;if(startOpen)show();
max.onclick=()=>card.classList.toggle("sbpw-max");
modal.addEventListener("click",e=>{if(e.target===modal)hide()});
document.addEventListener("keydown",e=>{if(e.key==="Escape"&&!modal.classList.contains("sbpw-hidden"))hide()});

let moving=false,dx=0,dy=0;
drag.addEventListener("pointerdown",e=>{if(e.target.closest("button")||card.classList.contains("sbpw-max"))return;moving=true;const r=card.getBoundingClientRect();card.style.transform="none";card.style.left=r.left+"px";card.style.top=r.top+"px";dx=e.clientX-r.left;dy=e.clientY-r.top;drag.setPointerCapture(e.pointerId)});
drag.addEventListener("pointermove",e=>{if(!moving)return;const x=Math.max(8,Math.min(innerWidth-card.offsetWidth-8,e.clientX-dx));const y=Math.max(8,Math.min(innerHeight-card.offsetHeight-8,e.clientY-dy));card.style.left=x+"px";card.style.top=y+"px"});
drag.addEventListener("pointerup",()=>moving=false);

async function loadMeta(){try{status.textContent="Özellikler yükleniyor…";const j=await api("wizard.meta");meta=j;setAuth(j.auth);renderActions();status.textContent="Hazır";const first=j.actions?.[0];if(first)selectAction(first.id)}catch(e){out.textContent="Wizard başlatılamadı: "+e.message}}
function renderActions(filter=""){
  if(!meta)return;const groups={};
  meta.actions.filter(a=>(a.title+" "+a.id+" "+a.category).toLowerCase().includes(filter.toLowerCase())).forEach(a=>(groups[a.category]??=[]).push(a));
  actionsBox.innerHTML=Object.entries(groups).map(([g,arr])=>`<div><div class="px-2 mb-1 text-[10px] uppercase tracking-wider font-extrabold text-slate-400">\${esc(g)}</div><div class="space-y-1">\${arr.map(a=>`<button type="button" data-action="\${esc(a.id)}" class="sbpw-action w-full text-left rounded-xl border border-transparent px-2.5 py-2 text-xs font-semibold hover:bg-white hover:border-violet-100">\${esc(a.title)}\${a.auth?'<span class="float-right text-[9px] text-violet-500">AUTH</span>':''}</button>`).join("")}</div></div>`).join("");
  actionsBox.querySelectorAll("[data-action]").forEach(b=>b.onclick=()=>selectAction(b.dataset.action));
}
el("sbpw-search").oninput=e=>renderActions(e.target.value);

function selectAction(id){
  current=meta.actions.find(a=>a.id===id);if(!current)return;
  actionsBox.querySelectorAll("[data-action]").forEach(b=>b.classList.toggle("active",b.dataset.action===id));
  title.textContent=current.title;desc.textContent=current.description||"";
  badge.textContent=current.auth?"AUTH":"PUBLIC";badge.className="rounded-full text-[10px] font-extrabold px-2.5 py-1 "+(current.auth?"bg-violet-100 text-violet-700":"bg-orange-100 text-orange-700");
  fields.innerHTML=(current.fields||[]).map(f=>{
    const base=`data-field="\${esc(f.name)}"`;
    if(f.type==="select")return `<label class="block"><span class="text-[10px] font-bold text-slate-500">\${esc(f.label)}</span><select \${base} class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white">\${(f.options||[]).map(o=>`<option value="\${esc(o)}">\${esc(o)}</option>`).join("")}</select></label>`;
    if(f.type==="textarea")return `<label class="block md:col-span-2"><span class="text-[10px] font-bold text-slate-500">\${esc(f.label)}</span><textarea \${base} rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs" placeholder="\${esc(f.placeholder||"")}">\${esc(f.default||"")}</textarea></label>`;
    return `<label class="block"><span class="text-[10px] font-bold text-slate-500">\${esc(f.label)}</span><input \${base} type="\${f.type==="password"?"password":"text"}" value="\${esc(f.default||"")}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs" placeholder="\${esc(f.placeholder||"")}"></label>`;
  }).join("");
  code.textContent=current.example||"// Çalıştırıldığında kod örneği burada güncellenecek.";
}
function params(){const p={};fields.querySelectorAll("[data-field]").forEach(x=>p[x.dataset.field]=x.value);return p}
el("sbpw-run").onclick=async()=>{
  if(!current)return;if(current.auth&&!auth.authenticated){el("sbpw-login-panel").classList.remove("hidden");out.textContent="Bu özellik için önce Scratch hesabına giriş yap.";return}
  if(current.danger&&!confirm(current.title+" işlemini gerçekten çalıştırmak istiyor musun?"))return;
  status.textContent="Çalışıyor…";out.textContent="İstek gönderiliyor…";
  try{const j=await api(current.id,params());out.textContent=JSON.stringify(j.data,null,2);if(j.code)code.textContent=j.code;status.textContent="Tamamlandı"}
  catch(e){out.textContent="Hata: "+e.message;status.textContent="Hata"}
};
el("sbpw-clear").onclick=()=>{out.textContent="Hazır.";status.textContent=""};
el("sbpw-copy-result").onclick=()=>navigator.clipboard.writeText(out.textContent);
el("sbpw-copy-code").onclick=()=>navigator.clipboard.writeText(code.textContent);

el("sbpw-login-toggle").onclick=()=>el("sbpw-login-panel").classList.toggle("hidden");
el("sbpw-login").onclick=async()=>{const u=el("sbpw-user").value.trim(),p=el("sbpw-pass").value;try{status.textContent="Scratch'e giriş yapılıyor…";const j=await api("auth.login",{username:u,password:p});el("sbpw-pass").value="";setAuth(j.auth);status.textContent="Giriş başarılı"}catch(e){out.textContent="Giriş hatası: "+e.message;status.textContent="Giriş başarısız"}};
el("sbpw-logout").onclick=async()=>{try{const j=await api("auth.logout");setAuth(j.auth);status.textContent="Çıkış yapıldı"}catch(e){out.textContent=e.message}};
})();
</script>
HTML;
    }

    private function execute(string $action, array $p): mixed
    {
        $session = str_starts_with($action, 'auth.') ? null : $this->wizardSession();
        $authRequired = $this->actionById($action)['auth'] ?? false;
        if ($authRequired && !$session) throw new \RuntimeException('Bu özellik için Scratch hesabına giriş yapmalısın.');
        if (($this->actionById($action)['write'] ?? false) && !$this->options['allow_writes']) throw new \RuntimeException('Wizard yazma işlemleri bu sitede kapalı.');

        $id = fn(string $k) => trim((string)($p[$k] ?? ''));
        $lim = fn() => max(1, min(100, (int)($p['limit'] ?? 20)));
        $off = fn() => max(0, (int)($p['offset'] ?? 0));

        return match ($action) {
            'project.get' => $this->scratch->project($id('project_id'))->get(),
            'project.refresh' => $this->scratch->project($id('project_id'))->refresh(),
            'project.comments' => $this->scratch->project($id('project_id'))->comments($lim(), $off()),
            'project.remixes' => $this->scratch->project($id('project_id'))->remixes($lim(), $off()),
            'project.analyze' => $this->scratch->project($id('project_id'))->analyze()->summary(),
            'project.analyzer_warnings' => $this->scratch->project($id('project_id'))->analyze()->warnings(),
            'project.opcodes' => $this->scratch->project($id('project_id'))->analyze()->opcodeCounts(),
            'project.compare' => $this->scratch->compareProjects($id('project_a'), $id('project_b'))->toArray(),

            'project.love' => $session->project($id('project_id'))->love(),
            'project.unlove' => $session->project($id('project_id'))->unlove(),
            'project.favorite' => $session->project($id('project_id'))->favorite(),
            'project.unfavorite' => $session->project($id('project_id'))->unfavorite(),
            'project.comment' => $session->project($id('project_id'))->postComment($id('content'), $id('parent_id') ?: null),
            'project.share' => $session->project($id('project_id'))->share(),
            'project.unshare' => $session->project($id('project_id'))->unshare(),

            'user.get' => $this->scratch->user($id('username'))->get(),
            'user.projects' => $this->scratch->user($id('username'))->projects($lim(), $off()),
            'user.followers' => $this->scratch->user($id('username'))->followers($lim(), $off()),
            'user.following' => $this->scratch->user($id('username'))->following($lim(), $off()),
            'user.favorites' => $this->scratch->user($id('username'))->favorites($lim(), $off()),
            'user.studios' => $this->scratch->user($id('username'))->studios($lim(), $off()),
            'user.follow' => $session->user($id('username'))->follow(),
            'user.unfollow' => $session->user($id('username'))->unfollow(),
            'user.comment' => $session->user($id('username'))->postComment($id('content'), $id('parent_id') ?: null),
            'user.set_bio' => $session->user((string)$session->username())->setBio($id('text')),
            'user.set_status' => $session->user((string)$session->username())->setStatus($id('text')),

            'studio.get' => $this->scratch->studio($id('studio_id'))->get(),
            'studio.projects' => $this->scratch->studio($id('studio_id'))->projects($lim(), $off()),
            'studio.curators' => $this->scratch->studio($id('studio_id'))->curators($lim(), $off()),
            'studio.managers' => $this->scratch->studio($id('studio_id'))->managers($lim(), $off()),
            'studio.comments' => $this->scratch->studio($id('studio_id'))->comments($lim(), $off()),
            'studio.add_project' => $session->studio($id('studio_id'))->addProject($id('project_id')),
            'studio.remove_project' => $session->studio($id('studio_id'))->removeProject($id('project_id')),
            'studio.invite' => $session->studio($id('studio_id'))->inviteCurator($id('username')),
            'studio.promote' => $session->studio($id('studio_id'))->promoteCurator($id('username')),
            'studio.remove_curator' => $session->studio($id('studio_id'))->removeCurator($id('username')),
            'studio.follow' => $session->studio($id('studio_id'))->follow(),
            'studio.unfollow' => $session->studio($id('studio_id'))->unfollow(),
            'studio.set_title' => $session->studio($id('studio_id'))->setTitle($id('text')),
            'studio.set_description' => $session->studio($id('studio_id'))->setDescription($id('text')),
            'studio.comment' => $session->studio($id('studio_id'))->postComment($id('content'), $id('parent_id') ?: null),

            'search.projects' => $this->scratch->searchProjects($id('query'), $id('mode') ?: 'trending', $id('language') ?: 'en', $lim(), $off()),
            'explore.projects' => $this->scratch->exploreProjects($id('query') ?: '*', $id('mode') ?: 'trending', $id('language') ?: 'en', $lim(), $off()),
            'search.turkish_trending' => $this->scratch->turkishTrending(
                max(1,min(100,(int)($p['limit']??20))),
                max(20,min(400,(int)($p['scan']??120)))
            ),

            'cloud.variables' => $this->withCloud($session, $id('project_id'), fn($c) => $c->variables(true)),
            'cloud.get' => $this->withCloud($session, $id('project_id'), fn($c) => ['variable'=>$id('variable'),'value'=>$c->getRemote($id('variable'), 4.0)]),
            'cloud.set_verified' => $this->withCloud($session, $id('project_id'), fn($c) => $c->setVerified($id('variable'), $id('value'), 6.0)),
            'cloud.history' => $this->withCloud($session, $id('project_id'), fn($c) => $c->history($id('variable') ?: null, $lim(), $off())),
            'cloud.db_get' => $this->withCloud($session, $id('project_id'), fn($c) => ['value'=>$c->database($id('db_variable') ?: 'db')->get($id('key'))]),
            'cloud.db_set' => $this->withCloud($session, $id('project_id'), fn($c) => $c->database($id('db_variable') ?: 'db')->set($id('key'), $id('value'))),
            'cloud.db_delete' => $this->withCloud($session, $id('project_id'), fn($c) => $c->database($id('db_variable') ?: 'db')->delete($id('key'))),
            'cloud.db_to_mysql' => $this->cloudDbToMysql($session,$p),
            'cloud.requests_once' => $this->cloudRequestsOnce($session, $p),

            'watcher.baseline' => $this->watcherBaseline($id('project_id')),
            'watcher.tick' => $this->watcherTick($id('project_id')),

            'dev.health' => $this->scratch->healthCheck(true),
            'dev.metrics' => $this->scratch->metrics()->summary(),
            'dev.circuit' => $this->scratch->circuitBreaker()->state(),

            default => throw new \InvalidArgumentException('Desteklenmeyen Wizard action: ' . $action),
        };
    }

    private function withCloud(?Session $session, string $projectId, callable $callback): mixed
    {
        if (!$session) throw new \RuntimeException('Cloud için authenticated Session gerekir.');
        if ($projectId === '') throw new \InvalidArgumentException('Project ID gerekli.');
        $cloud = $session->cloud($projectId);
        $cloud->connect();
        try {
            return $callback($cloud);
        } finally {
            $cloud->disconnect();
        }
    }

    private function cloudDbToMysql(?Session $session,array $p): mixed
    {
        if (!$session) throw new \RuntimeException('CloudDB Pro için Scratch login gerekli.');

        $profileName=trim((string)($p['profile']??''));
        $profiles=$this->options['clouddb_profiles']??[];

        if ($profileName==='' || !array_key_exists($profileName,$profiles)) {
            throw new \RuntimeException('Geçerli bir server-side CloudDB MySQL profili seç.');
        }

        $profile=$profiles[$profileName];
        $projectId=trim((string)($p['project_id']??''));
        $variable=trim((string)($p['db_variable']??'db')) ?: 'db';

        return $this->withCloud($session,$projectId,function($cloud) use ($profile,$variable) {
            return $cloud->database($variable)->getToDB($profile);
        });
    }

    private function cloudRequestsOnce(?Session $session, array $p): mixed
    {
        $handlers = $this->options['cloud_request_handlers'];
        if (!$handlers) {
            $handlers = [
                'ping' => fn(array $params) => $params[0] ?? 'pong',
                'sum' => fn(array $params) => array_sum(array_map('floatval', $params)),
                'time' => fn(array $params) => time(),
            ];
        }

        return $this->withCloud($session, trim((string)($p['project_id'] ?? '')), function($cloud) use ($p, $handlers) {
            $rpc = $cloud->requests(
                trim((string)($p['request_var'] ?? 'request')) ?: 'request',
                trim((string)($p['response_var'] ?? 'response')) ?: 'response'
            );
            foreach ($handlers as $name => $handler) {
                if (is_callable($handler)) $rpc->route((string)$name, $handler);
            }
            return $rpc->handleOnce(max(1.0, min(15.0, (float)($p['timeout'] ?? 5))));
        });
    }

    private function watcherBaseline(string $projectId): array
    {
        $watch = $this->scratch->watch()->project($projectId);
        $state = $watch->snapshot();
        $bag =& $this->bag();
        $bag['watchers'][$projectId] = $state;
        return ['baseline' => $state];
    }

    private function watcherTick(string $projectId): array
    {
        $bag =& $this->bag();
        $old = $bag['watchers'][$projectId] ?? null;
        if (!is_array($old)) throw new \RuntimeException('Önce Watcher Baseline çalıştır.');

        $watch = $this->scratch->watch()->project($projectId);
        $now = $watch->snapshot();
        $changes = ProjectWatch::diffStates($old, $now);
        $bag['watchers'][$projectId] = $now;

        return ['changes' => $changes, 'state' => $now];
    }

    private function clientActions(): array
    {
        $f = fn(string $name,string $label,string $type='text',array $extra=[]) => array_merge(['name'=>$name,'label'=>$label,'type'=>$type],$extra);
        $id = fn(string $name='project_id') => $f($name, ucwords(str_replace('_',' ',$name)), 'text', ['placeholder'=>'ID']);
        $page = [$f('limit','Limit','text',['default'=>'20']),$f('offset','Offset','text',['default'=>'0'])];

        return [
            ['id'=>'project.get','category'=>'Project','title'=>'Proje bilgisi','description'=>'Public proje verisini getir.','fields'=>[$id()],'example'=>'$project = $scratch->project(104);'."\n".'$data = $project->get();'],
            ['id'=>'project.refresh','category'=>'Project','title'=>'Projeyi fresh getir','description'=>'Cache bypass ederek güncel proje verisini al.','fields'=>[$id()]],
            ['id'=>'project.comments','category'=>'Project','title'=>'Proje yorumları','description'=>'Proje yorumlarını listele.','fields'=>array_merge([$id()],$page)],
            ['id'=>'project.remixes','category'=>'Project','title'=>'Remixler','description'=>'Projenin remixlerini getir.','fields'=>array_merge([$id()],$page)],
            ['id'=>'project.analyze','category'=>'Analyzer','title'=>'Analyzer özeti','description'=>'SB3/project JSON yapısını analiz eder.','fields'=>[$id()]],
            ['id'=>'project.analyzer_warnings','category'=>'Analyzer','title'=>'Analyzer uyarıları','description'=>'Unused variable ve duplicate script gibi uyarılar.','fields'=>[$id()]],
            ['id'=>'project.opcodes','category'=>'Analyzer','title'=>'Opcode sayımları','description'=>'Blok opcode kullanım sayılarını getir.','fields'=>[$id()]],
            ['id'=>'project.compare','category'=>'Analyzer','title'=>'İki projeyi karşılaştır','description'=>'ProjectDiff ile iki Scratch projesini kıyasla.','fields'=>[$id('project_a'),$id('project_b')]],

            ['id'=>'project.love','category'=>'Project • Auth','title'=>'Projeyi beğen','description'=>'Giriş yapılan tek Scratch hesabıyla love ekler.','auth'=>true,'write'=>true,'fields'=>[$id()]],
            ['id'=>'project.unlove','category'=>'Project • Auth','title'=>'Beğeniyi kaldır','description'=>'Love kaldırır.','auth'=>true,'write'=>true,'fields'=>[$id()]],
            ['id'=>'project.favorite','category'=>'Project • Auth','title'=>'Favoriye ekle','description'=>'Favoriye ekler.','auth'=>true,'write'=>true,'fields'=>[$id()]],
            ['id'=>'project.unfavorite','category'=>'Project • Auth','title'=>'Favoriden çıkar','description'=>'Favoriden çıkarır.','auth'=>true,'write'=>true,'fields'=>[$id()]],
            ['id'=>'project.comment','category'=>'Project • Auth','title'=>'Projeye yorum yap','description'=>'Authenticated project comment.','auth'=>true,'write'=>true,'fields'=>[$id(),$f('content','Yorum','textarea'),$f('parent_id','Parent ID (opsiyonel)')]],
            ['id'=>'project.share','category'=>'Project • Auth','title'=>'Projeyi paylaş','description'=>'Yetkin olan projeyi share eder.','auth'=>true,'write'=>true,'fields'=>[$id()]],
            ['id'=>'project.unshare','category'=>'Project • Auth','title'=>'Paylaşımdan kaldır','description'=>'Yetkin olan projeyi unshare eder.','auth'=>true,'write'=>true,'danger'=>true,'fields'=>[$id()]],

            ['id'=>'user.get','category'=>'User','title'=>'Kullanıcı bilgisi','description'=>'Public kullanıcı profilini getir.','fields'=>[$f('username','Username')]],
            ['id'=>'user.projects','category'=>'User','title'=>'Kullanıcı projeleri','description'=>'Kullanıcının projeleri.','fields'=>array_merge([$f('username','Username')],$page)],
            ['id'=>'user.followers','category'=>'User','title'=>'Takipçiler','description'=>'Takipçi listesini getir.','fields'=>array_merge([$f('username','Username')],$page)],
            ['id'=>'user.following','category'=>'User','title'=>'Takip edilenler','description'=>'Following listesini getir.','fields'=>array_merge([$f('username','Username')],$page)],
            ['id'=>'user.favorites','category'=>'User','title'=>'Favoriler','description'=>'Kullanıcının favori projeleri.','fields'=>array_merge([$f('username','Username')],$page)],
            ['id'=>'user.studios','category'=>'User','title'=>'Stüdyolar','description'=>'Kullanıcının curate ettiği stüdyolar.','fields'=>array_merge([$f('username','Username')],$page)],
            ['id'=>'user.follow','category'=>'User • Auth','title'=>'Kullanıcıyı takip et','description'=>'Giriş yapılan tek hesapla takip eder.','auth'=>true,'write'=>true,'fields'=>[$f('username','Username')]],
            ['id'=>'user.unfollow','category'=>'User • Auth','title'=>'Takibi bırak','description'=>'Takibi kaldırır.','auth'=>true,'write'=>true,'fields'=>[$f('username','Username')]],
            ['id'=>'user.comment','category'=>'User • Auth','title'=>'Profile yorum yap','description'=>'Scratch profil yorumu gönderir.','auth'=>true,'write'=>true,'fields'=>[$f('username','Username'),$f('content','Yorum','textarea'),$f('parent_id','Parent ID')]],
            ['id'=>'user.set_bio','category'=>'User • Auth','title'=>'Kendi bio değiştir','description'=>'Giriş yapılan hesabın bio alanını günceller.','auth'=>true,'write'=>true,'fields'=>[$f('text','Yeni bio','textarea')]],
            ['id'=>'user.set_status','category'=>'User • Auth','title'=>'Kendi status değiştir','description'=>'Giriş yapılan hesabın status alanını günceller.','auth'=>true,'write'=>true,'fields'=>[$f('text','Yeni status','textarea')]],

            ['id'=>'studio.get','category'=>'Studio','title'=>'Studio bilgisi','description'=>'Public studio verisi.','fields'=>[$id('studio_id')]],
            ['id'=>'studio.projects','category'=>'Studio','title'=>'Studio projeleri','description'=>'Studio projelerini getir.','fields'=>array_merge([$id('studio_id')],$page)],
            ['id'=>'studio.curators','category'=>'Studio','title'=>'Curatorlar','description'=>'Studio curator listesini getir.','fields'=>array_merge([$id('studio_id')],$page)],
            ['id'=>'studio.managers','category'=>'Studio','title'=>'Managerlar','description'=>'Studio manager listesini getir.','fields'=>array_merge([$id('studio_id')],$page)],
            ['id'=>'studio.comments','category'=>'Studio','title'=>'Studio yorumları','description'=>'Studio yorumlarını getir.','fields'=>array_merge([$id('studio_id')],$page)],
            ['id'=>'studio.add_project','category'=>'Studio • Auth','title'=>'Studioya proje ekle','description'=>'Yetkin varsa projeyi studioya ekler.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id'),$id('project_id')]],
            ['id'=>'studio.remove_project','category'=>'Studio • Auth','title'=>'Studiodan proje çıkar','description'=>'Yetkin varsa projeyi kaldırır.','auth'=>true,'write'=>true,'danger'=>true,'fields'=>[$id('studio_id'),$id('project_id')]],
            ['id'=>'studio.invite','category'=>'Studio • Auth','title'=>'Curator davet et','description'=>'Curator daveti gönderir.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id'),$f('username','Username')]],
            ['id'=>'studio.promote','category'=>'Studio • Auth','title'=>'Curator promote','description'=>'Curatorı manager yapar.','auth'=>true,'write'=>true,'danger'=>true,'fields'=>[$id('studio_id'),$f('username','Username')]],
            ['id'=>'studio.remove_curator','category'=>'Studio • Auth','title'=>'Curator çıkar','description'=>'Curatorı studiodan çıkarır.','auth'=>true,'write'=>true,'danger'=>true,'fields'=>[$id('studio_id'),$f('username','Username')]],
            ['id'=>'studio.follow','category'=>'Studio • Auth','title'=>'Studio takip et','description'=>'Studioyu takip eder.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id')]],
            ['id'=>'studio.unfollow','category'=>'Studio • Auth','title'=>'Studio takibi bırak','description'=>'Studio takibini kaldırır.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id')]],
            ['id'=>'studio.set_title','category'=>'Studio • Auth','title'=>'Studio başlığı','description'=>'Studio başlığını günceller.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id'),$f('text','Yeni başlık')]],
            ['id'=>'studio.set_description','category'=>'Studio • Auth','title'=>'Studio açıklaması','description'=>'Studio açıklamasını günceller.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id'),$f('text','Açıklama','textarea')]],
            ['id'=>'studio.comment','category'=>'Studio • Auth','title'=>'Studio yorum yap','description'=>'Studioya yorum gönderir.','auth'=>true,'write'=>true,'fields'=>[$id('studio_id'),$f('content','Yorum','textarea'),$f('parent_id','Parent ID')]],

            ['id'=>'search.projects','category'=>'Search','title'=>'Proje ara','description'=>'Scratch project search.','fields'=>array_merge([$f('query','Arama'),$f('mode','Mode','select',['options'=>['trending','popular']]),$f('language','Dil','text',['default'=>'en'])],$page)],
            ['id'=>'explore.projects','category'=>'Search','title'=>'Explore projeleri','description'=>'Scratch explore project verisi.','fields'=>array_merge([$f('query','Query','text',['default'=>'*']),$f('mode','Mode','select',['options'=>['trending','popular']]),$f('language','Dil','text',['default'=>'en'])],$page)],
            ['id'=>'search.turkish_trending','category'=>'Search','title'=>'Türkçe Trend','description'=>'Adında Türk geçen Scratch stüdyolarını bulur, bu stüdyolardaki projeleri toplayıp trend algoritmasıyla sıralar.','fields'=>[$f('limit','Sonuç','text',['default'=>'20']),$f('scan','Taranacak stüdyo sonucu','text',['default'=>'120'])]],

            ['id'=>'cloud.variables','category'=>'Cloud • Auth','title'=>'Cloud Variables','description'=>'Projede bilinen cloud değerlerini getir.','auth'=>true,'fields'=>[$id()]],
            ['id'=>'cloud.get','category'=>'Cloud • Auth','title'=>'Cloud değer oku','description'=>'Cloud log/WebSocket üzerinden remote değer al.','auth'=>true,'fields'=>[$id(),$f('variable','Variable')]],
            ['id'=>'cloud.set_verified','category'=>'Cloud • Auth','title'=>'Cloud setVerified','description'=>'Cloud değerini yazıp Scratch tarafında doğrular.','auth'=>true,'write'=>true,'fields'=>[$id(),$f('variable','Variable'),$f('value','Sayısal değer')]],
            ['id'=>'cloud.history','category'=>'Cloud • Auth','title'=>'Cloud history','description'=>'Cloud log geçmişini getir.','auth'=>true,'fields'=>array_merge([$id(),$f('variable','Variable (opsiyonel)')],$page)],
            ['id'=>'cloud.db_get','category'=>'Cloud Database','title'=>'CloudDB get','description'=>'CloudDatabase key/value oku.','auth'=>true,'fields'=>[$id(),$f('db_variable','DB variable','text',['default'=>'db']),$f('key','Key')]],
            ['id'=>'cloud.db_set','category'=>'Cloud Database','title'=>'CloudDB set','description'=>'CloudDatabase key/value yaz.','auth'=>true,'write'=>true,'fields'=>[$id(),$f('db_variable','DB variable','text',['default'=>'db']),$f('key','Key'),$f('value','Value')]],
            ['id'=>'cloud.db_delete','category'=>'Cloud Database','title'=>'CloudDB delete','description'=>'CloudDatabase key sil.','auth'=>true,'write'=>true,'danger'=>true,'fields'=>[$id(),$f('db_variable','DB variable','text',['default'=>'db']),$f('key','Key')]],
            ['id'=>'cloud.db_to_mysql','category'=>'Cloud Database','title'=>'CloudDB Pro → MySQL','description'=>'CloudDatabase key/value verisini server-side JSON/MySQL profile üzerinden prepared statement ile MySQL tablosuna aktarır.','auth'=>true,'write'=>true,'danger'=>true,'fields'=>[$id(),$f('db_variable','DB variable','text',['default'=>'db']),$f('profile','MySQL Profile','select',['options'=>array_keys($this->options['clouddb_profiles']??[])])]],
            ['id'=>'cloud.requests_once','category'=>'CloudRequests','title'=>'CloudRequests: handle once','description'=>'Bir CloudRequests RPC isteğini bekleyip route eder. Site sahibi custom handler tanımlayabilir.','auth'=>true,'write'=>true,'fields'=>[$id(),$f('request_var','Request variable','text',['default'=>'request']),$f('response_var','Response variable','text',['default'=>'response']),$f('timeout','Timeout','text',['default'=>'5'])]],

            ['id'=>'watcher.baseline','category'=>'Watcher','title'=>'Watcher baseline','description'=>'Projenin anlık stateini PHP sessionda baseline olarak kaydeder.','fields'=>[$id()]],
            ['id'=>'watcher.tick','category'=>'Watcher','title'=>'Watcher tick','description'=>'Fresh state alıp baseline ile farkları gösterir.','fields'=>[$id()]],

            ['id'=>'dev.health','category'=>'Developer','title'=>'Doctor / Health','description'=>'PHP, cURL, TLS, Scratch API latency ve ortam kontrolleri.','fields'=>[]],
            ['id'=>'dev.metrics','category'=>'Developer','title'=>'Request Metrics','description'=>'Wizardın mevcut Scratch nesnesindeki metrics özeti.','fields'=>[]],
            ['id'=>'dev.circuit','category'=>'Developer','title'=>'Circuit Breaker','description'=>'HTTP circuit breaker durumunu göster.','fields'=>[]],
        ];
    }

    private function actionById(string $id): ?array
    {
        foreach ($this->clientActions() as $action) if ($action['id'] === $id) return $action;
        return null;
    }

    private function snippet(string $action, array $p): string
    {
        $v = fn(string $key, mixed $default='') => var_export($p[$key] ?? $default, true);
        $sessionPrefix = "// \$session Wizard'daki giriş yapılan Scratch hesabını temsil eder.\n";

        return match ($action) {
            'project.get' => '$project = $scratch->project('.$v('project_id').");\n\$data = \$project->get();",
            'project.refresh' => '$data = $scratch->project('.$v('project_id').')->refresh();',
            'project.comments' => '$comments = $scratch->project('.$v('project_id').')->comments('.(int)($p['limit']??20).', '.(int)($p['offset']??0).');',
            'project.analyze' => '$summary = $scratch->project('.$v('project_id').')->analyze()->summary();',
            'project.compare' => '$diff = $scratch->compareProjects('.$v('project_a').', '.$v('project_b').');',
            'user.get' => '$user = $scratch->user('.$v('username').");\n\$data = \$user->get();",
            'studio.get' => '$studio = $scratch->studio('.$v('studio_id').");\n\$data = \$studio->get();",
            'search.projects' => '$results = $scratch->searchProjects('.$v('query').', '.$v('mode','trending').', '.$v('language','en').');',
            'search.turkish_trending' => '$projects = $scratch->turkishTrending('.(int)($p['limit']??20).', '.(int)($p['scan']??120).');',
            'project.love' => $sessionPrefix.'$result = $session->project('.$v('project_id').')->love();',
            'project.favorite' => $sessionPrefix.'$result = $session->project('.$v('project_id').')->favorite();',
            'project.comment' => $sessionPrefix.'$result = $session->project('.$v('project_id').')->postComment('.$v('content').');',
            'user.follow' => $sessionPrefix.'$result = $session->user('.$v('username').')->follow();',
            'studio.add_project' => $sessionPrefix.'$result = $session->studio('.$v('studio_id').')->addProject('.$v('project_id').');',
            'cloud.variables' => $sessionPrefix.'$cloud = $session->cloud('.$v('project_id').");\n\$cloud->connect();\n\$values = \$cloud->variables();\n\$cloud->disconnect();",
            'cloud.get' => $sessionPrefix.'$cloud = $session->cloud('.$v('project_id').");\n\$cloud->connect();\n\$value = \$cloud->getRemote(".$v('variable').");\n\$cloud->disconnect();",
            'cloud.set_verified' => $sessionPrefix.'$cloud = $session->cloud('.$v('project_id').");\n\$cloud->connect();\n\$result = \$cloud->setVerified(".$v('variable').', '.$v('value').");\n\$cloud->disconnect();",
            'cloud.requests_once' => $sessionPrefix.'$cloud = $session->cloud('.$v('project_id').");\n\$cloud->connect();\n\$rpc = \$cloud->requests(".$v('request_var','request').', '.$v('response_var','response').");\n\$rpc->route('ping', fn(\$params) => \$params[0] ?? 'pong');\n\$result = \$rpc->handleOnce(".(float)($p['timeout']??5).");\n\$cloud->disconnect();",
            'watcher.baseline' => '$watch = $scratch->watch()->project('.$v('project_id').");\n\$baseline = \$watch->baseline();",
            'watcher.tick' => '$changes = $watch->tick();',
            'dev.health' => '$health = $scratch->healthCheck(true);',
            'dev.metrics' => '$metrics = $scratch->metrics()->summary();',
            default => "// ScratchByPHP action: {$action}\n// Modal bu işlemi sunucu tarafında güvenli dispatcher üzerinden çalıştırdı.",
        };
    }

    private function wizardSession(): ?Session
    {
        $bag = $this->bag();
        $sid = (string)($bag['scratch_session_id'] ?? '');
        if ($sid === '') return null;
        return $this->scratch->loginWithSessionId($sid, $bag['scratch_username'] ?? null);
    }

    private function authStatus(): array
    {
        $bag = $this->bag();
        return [
            'authenticated' => !empty($bag['scratch_session_id']),
            'username' => $bag['scratch_username'] ?? null,
            'session_id_exposed' => false,
        ];
    }

    private function &bag(): array
    {
        $key = (string)$this->options['session_key'];
        $_SESSION[$key] ??= [];
        return $_SESSION[$key];
    }

    private function ensurePhpSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        if (headers_sent($file, $line)) {
            throw new \RuntimeException("ScratchByPHP Wizard PHP session başlatamadı; çıktı daha önce gönderilmiş: {$file}:{$line}. \$scratch->wizard() çağrısını HTML çıktısından önce yap.");
        }
        session_start();
    }

    private function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            if (method_exists($value, 'toArray')) return $this->normalize($value->toArray());
            return $this->normalize(get_object_vars($value));
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) $value[$k] = $this->normalize($v);
        }
        return $value;
    }

    private function redact(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) $value[$k] = $this->redact($v, (string)$k);
            return $value;
        }
        if ($key && preg_match('/token|session|password|cookie|csrf|authorization|project_token/i', $key)) {
            return '[REDACTED]';
        }
        return $value;
    }

    private function respond(array $payload, bool $exit = true): never
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
