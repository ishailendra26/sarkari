<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/helpers.php';

$pageTitle = 'Online Tools';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="bg-white border-b">
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Free Online Tools</h1>
                <p class="text-gray-600">Quick calculators and helpers for students. All tools run in your browser and are free to use.</p>
            </div>
            <div class="hidden md:block text-sm text-gray-500">Add more tools easily from the developer console (see README)</div>
        </div>
    </div>
</section>

<section class="py-8">
    <div class="container mx-auto px-4">
        <div class="lg:flex lg:gap-6">
            <aside class="lg:w-1/4 mb-6 lg:mb-0">
                <div class="bg-white rounded-lg shadow p-4 space-y-2">
                    <h3 class="font-semibold text-gray-700">Tools</h3>
                    <ul id="tools-list" class="mt-3 space-y-2 text-gray-700">
                        <!-- Tool items injected by JS -->
                    </ul>
                    <div class="mt-4 text-xs text-gray-500">You can add more tools later. Developers: see the TOOLS registry in JS.</div>
                </div>
            </aside>

            <main class="flex-1">
                <div id="tool-container" class="bg-white rounded-lg shadow p-6 min-h-[300px]">
                    <div id="tool-intro" class="text-center text-gray-600">
                        <i class="fas fa-wrench text-3xl text-gray-400"></i>
                        <h2 class="text-xl font-semibold mt-2">Choose a tool from the left</h2>
                        <p class="mt-2">Tools run in your browser. Your data is stored locally for notes.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

<script>
// Tools registry - push new tools here to extend
window.TOOLS = window.TOOLS || [];

// Utility: create element from HTML
function ce(html){ const d=document.createElement('div'); d.innerHTML=html.trim(); return d.firstChild; }

// Tool: Percentage Calculator
window.TOOLS.push({
    id: 'percentage',
    title: 'Percentage Calculator',
    icon: 'fas fa-percent',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Percentage Calculator</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Marks Obtained</label>
                    <input id="pct_obt" class="mt-1 px-3 py-2 border rounded w-full" type="number">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Total Marks</label>
                    <input id="pct_total" class="mt-1 px-3 py-2 border rounded w-full" type="number">
                </div>
            </div>
            <div class="mt-4">
                <button id="pct_calc" class="bg-primary text-white px-4 py-2 rounded">Calculate</button>
                <div id="pct_result" class="mt-3 text-gray-700"></div>
            </div>
        `;
        container.querySelector('#pct_calc').addEventListener('click', function(){
            const obt = parseFloat(document.getElementById('pct_obt').value)||0;
            const tot = parseFloat(document.getElementById('pct_total').value)||0;
            if(tot<=0){ document.getElementById('pct_result').textContent = 'Enter a valid total marks.'; return; }
            const pct = (obt/tot)*100;
            document.getElementById('pct_result').textContent = `Percentage: ${pct.toFixed(2)}%`;
        });
    }
});

// Tool: Negative Marking Calculator
window.TOOLS.push({
    id: 'negative',
    title: 'Negative Marking Calculator',
    icon: 'fas fa-minus-circle',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Negative Marking Calculator</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Correct Answers</label>
                    <input id="neg_corr" class="mt-1 px-3 py-2 border rounded w-full" type="number" min="0">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Wrong Answers</label>
                    <input id="neg_wrong" class="mt-1 px-3 py-2 border rounded w-full" type="number" min="0">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Marks per Correct</label>
                    <input id="neg_mpc" class="mt-1 px-3 py-2 border rounded w-full" type="number" step="0.01" value="1">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Negative per Wrong</label>
                    <input id="neg_neg" class="mt-1 px-3 py-2 border rounded w-full" type="number" step="0.01" value="0.25">
                </div>
            </div>
            <div class="mt-4">
                <button id="neg_calc" class="bg-primary text-white px-4 py-2 rounded">Calculate</button>
                <div id="neg_result" class="mt-3 text-gray-700"></div>
            </div>
        `;
        container.querySelector('#neg_calc').addEventListener('click', function(){
            const c = parseFloat(document.getElementById('neg_corr').value)||0;
            const w = parseFloat(document.getElementById('neg_wrong').value)||0;
            const mpc = parseFloat(document.getElementById('neg_mpc').value)||0;
            const neg = parseFloat(document.getElementById('neg_neg').value)||0;
            const score = (c*mpc) - (w*neg);
            document.getElementById('neg_result').textContent = `Score: ${score.toFixed(2)}`;
        });
    }
});

// Tool: Marks Calculator (marks <-> percentage)
window.TOOLS.push({
    id: 'marks',
    title: 'Marks Calculator',
    icon: 'fas fa-calculator',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Marks Calculator</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Total Marks</label>
                    <input id="m_total" class="mt-1 px-3 py-2 border rounded w-full" type="number">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Marks Obtained (or leave blank to compute from percent)</label>
                    <input id="m_obt" class="mt-1 px-3 py-2 border rounded w-full" type="number">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Percentage (or leave blank to compute from marks)</label>
                    <input id="m_pct" class="mt-1 px-3 py-2 border rounded w-full" type="number" step="0.01">
                </div>
            </div>
            <div class="mt-4">
                <button id="m_calc" class="bg-primary text-white px-4 py-2 rounded">Calculate</button>
                <div id="m_result" class="mt-3 text-gray-700"></div>
            </div>
        `;
        container.querySelector('#m_calc').addEventListener('click', function(){
            const tot = parseFloat(document.getElementById('m_total').value)||0;
            const obt = document.getElementById('m_obt').value;
            const pct = document.getElementById('m_pct').value;
            if(tot<=0){ document.getElementById('m_result').textContent='Enter valid total marks.'; return; }
            if(obt!== ''){
                const o = parseFloat(obt)||0; const p = (o/tot)*100; document.getElementById('m_result').textContent = `Percentage: ${p.toFixed(2)}%`;
            } else if(pct !== ''){
                const p = parseFloat(pct)||0; const marks = (p/100)*tot; document.getElementById('m_result').textContent = `Marks required/obtained: ${marks.toFixed(2)}`;
            } else {
                document.getElementById('m_result').textContent = 'Enter either marks obtained or percentage.';
            }
        });
    }
});

// Tool: Simple Interest
window.TOOLS.push({
    id: 'si',
    title: 'Simple Interest (SI)',
    icon: 'fas fa-percent',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Simple Interest</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="block text-sm text-gray-600">Principal (P)</label><input id="si_p" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
                <div><label class="block text-sm text-gray-600">Rate % (R)</label><input id="si_r" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
                <div><label class="block text-sm text-gray-600">Time (years) (T)</label><input id="si_t" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
            </div>
            <div class="mt-4"><button id="si_calc" class="bg-primary text-white px-4 py-2 rounded">Calculate</button><div id="si_result" class="mt-3 text-gray-700"></div></div>
        `;
        container.querySelector('#si_calc').addEventListener('click', function(){
            const P = parseFloat(document.getElementById('si_p').value)||0;
            const R = parseFloat(document.getElementById('si_r').value)||0;
            const T = parseFloat(document.getElementById('si_t').value)||0;
            const si = (P*R*T)/100;
            document.getElementById('si_result').textContent = `Simple Interest: ${si.toFixed(2)} | Amount: ${(P+si).toFixed(2)}`;
        });
    }
});

// Tool: Compound Interest
window.TOOLS.push({
    id: 'ci',
    title: 'Compound Interest (CI)',
    icon: 'fas fa-percentage',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Compound Interest</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="block text-sm text-gray-600">Principal (P)</label><input id="ci_p" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
                <div><label class="block text-sm text-gray-600">Rate % (R)</label><input id="ci_r" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
                <div><label class="block text-sm text-gray-600">Time (years) (T)</label><input id="ci_t" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
            </div>
            <div class="mt-4"><label class="block text-sm text-gray-600">Compounds per year (n)</label><input id="ci_n" class="mt-1 px-3 py-2 border rounded w-24" type="number" value="1"></div>
            <div class="mt-4"><button id="ci_calc" class="bg-primary text-white px-4 py-2 rounded">Calculate</button><div id="ci_result" class="mt-3 text-gray-700"></div></div>
        `;
        container.querySelector('#ci_calc').addEventListener('click', function(){
            const P = parseFloat(document.getElementById('ci_p').value)||0;
            const R = parseFloat(document.getElementById('ci_r').value)||0;
            const T = parseFloat(document.getElementById('ci_t').value)||0;
            const n = parseFloat(document.getElementById('ci_n').value)||1;
            const A = P * Math.pow(1 + (R/100)/n, n*T);
            const ci = A - P;
            document.getElementById('ci_result').textContent = `Compound Interest: ${ci.toFixed(2)} | Amount: ${A.toFixed(2)}`;
        });
    }
});

// Tool: Profit & Loss
window.TOOLS.push({
    id: 'pl',
    title: 'Profit & Loss Calculator',
    icon: 'fas fa-money-bill-wave',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Profit & Loss</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm text-gray-600">Cost Price (CP)</label><input id="pl_cp" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
                <div><label class="block text-sm text-gray-600">Selling Price (SP)</label><input id="pl_sp" class="mt-1 px-3 py-2 border rounded w-full" type="number"></div>
            </div>
            <div class="mt-4"><button id="pl_calc" class="bg-primary text-white px-4 py-2 rounded">Calculate</button><div id="pl_result" class="mt-3 text-gray-700"></div></div>
        `;
        container.querySelector('#pl_calc').addEventListener('click', function(){
            const cp = parseFloat(document.getElementById('pl_cp').value)||0;
            const sp = parseFloat(document.getElementById('pl_sp').value)||0;
            if(cp<=0){ document.getElementById('pl_result').textContent='Enter valid cost price.'; return; }
            const diff = sp - cp;
            const mode = diff>=0 ? 'Profit' : 'Loss';
            const pct = (Math.abs(diff)/cp)*100;
            document.getElementById('pl_result').textContent = `${mode}: ${Math.abs(diff).toFixed(2)} | ${pct.toFixed(2)}%`;
        });
    }
});

// Tool: Basic Maths Calculator
window.TOOLS.push({
    id: 'basic',
    title: 'Basic Maths Calculator',
    icon: 'fas fa-calculator',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Basic Calculator</h3>
            <div>
                <input id="calc_expr" class="w-full px-3 py-2 border rounded" placeholder="e.g. (25+30)/5 * 3 - 2">
            </div>
            <div class="mt-4">
                <button id="calc_eval" class="bg-primary text-white px-4 py-2 rounded">Evaluate</button>
                <div id="calc_result" class="mt-3 text-gray-700"></div>
            </div>
        `;
        container.querySelector('#calc_eval').addEventListener('click', function(){
            const expr = document.getElementById('calc_expr').value || '';
            try{
                // simple client-side evaluator
                const safe = Function('return (' + expr + ')');
                const res = safe();
                document.getElementById('calc_result').textContent = `Result: ${res}`;
            }catch(e){ document.getElementById('calc_result').textContent = 'Invalid expression.'; }
        });
    }
});

// Tool: Online Notes Taker (localStorage)
window.TOOLS.push({
    id: 'notes',
    title: 'Online Notes Taker',
    icon: 'fas fa-sticky-note',
    render: function(container){
        container.innerHTML = `
            <h3 class="text-lg font-semibold mb-4">Notes Taker</h3>
            <div class="mb-3">
                <input id="note_title" class="w-full px-3 py-2 border rounded" placeholder="Title (optional)">
            </div>
            <div>
                <textarea id="note_body" class="w-full h-48 p-3 border rounded" placeholder="Write your notes here..."></textarea>
            </div>
            <div class="mt-3 flex gap-2">
                <button id="note_save" class="bg-primary text-white px-4 py-2 rounded">Save Note</button>
                <button id="note_clear" class="px-4 py-2 border rounded">Clear</button>
            </div>
            <div class="mt-4">
                <h4 class="font-semibold">Saved Notes</h4>
                <ul id="notes_list" class="mt-2 space-y-2 text-gray-700"></ul>
            </div>
        `;

        const lsKey = 'examsz_notes_v1';
        function loadNotes(){
            const raw = localStorage.getItem(lsKey) || '[]';
            try{ return JSON.parse(raw); }catch(e){ return []; }
        }
        function saveNotes(notes){ localStorage.setItem(lsKey, JSON.stringify(notes)); }
        function renderNotes(){
            const ul = container.querySelector('#notes_list'); ul.innerHTML='';
            const notes = loadNotes();
            if(notes.length===0){ ul.innerHTML='<li class="text-sm text-gray-500">No notes saved</li>'; return; }
            notes.slice().reverse().forEach((n, idx)=>{
                const li = ce(`<li class="p-3 border rounded bg-gray-50"><div class=\"flex justify-between items-start\"><div><div class=\"font-semibold\">${n.title||'Untitled'}</div><div class=\"text-sm text-gray-600 mt-1\">${n.body.replace(/\n/g,'<br>')}</div></div><div class=\"ml-3\"><button data-idx=\"${notes.length-1-idx}\" class=\"del-note text-sm text-red-600\">Delete</button></div></div></li>`);
                ul.appendChild(li);
            });
            ul.querySelectorAll('.del-note').forEach(btn=>btn.addEventListener('click', function(){
                const i = parseInt(this.getAttribute('data-idx'));
                const notes = loadNotes(); notes.splice(i,1); saveNotes(notes); renderNotes();
            }));
        }

        container.querySelector('#note_save').addEventListener('click', function(){
            const title = container.querySelector('#note_title').value.trim();
            const body = container.querySelector('#note_body').value.trim();
            if(!body){ alert('Write some notes before saving.'); return; }
            const notes = loadNotes(); notes.push({title: title, body: body, created: Date.now()}); saveNotes(notes); container.querySelector('#note_title').value=''; container.querySelector('#note_body').value=''; renderNotes();
        });
        container.querySelector('#note_clear').addEventListener('click', function(){ container.querySelector('#note_body').value=''; container.querySelector('#note_title').value=''; });
        renderNotes();
    }
});

// Render tools list and handle selection
function initTools(){
    const list = document.getElementById('tools-list');
    list.innerHTML='';
    window.TOOLS.forEach(tool=>{
        const li = ce(`<li><button data-tool="${tool.id}" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 flex items-center gap-3"><i class=\"${tool.icon} text-gray-600\"></i><span>${tool.title}</span></button></li>`);
        list.appendChild(li);
    });
    list.querySelectorAll('button[data-tool]').forEach(btn=>btn.addEventListener('click', function(){
        const id = this.getAttribute('data-tool'); const t = window.TOOLS.find(x=>x.id===id);
        const container = document.getElementById('tool-container'); container.innerHTML='';
        if(t && typeof t.render==='function'){ t.render(container); }
    }));
}

document.addEventListener('DOMContentLoaded', initTools);
</script>

<?php include 'includes/footer.php'; ?>
