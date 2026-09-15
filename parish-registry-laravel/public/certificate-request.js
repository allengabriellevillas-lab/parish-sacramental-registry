const api=window.location.pathname.replace(/\/(certificate-request|request-status|certificate-request\.html)$/,'').replace(/\/$/,'')+'/api/v1';
const $=s=>document.querySelector(s);
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const labels={submitted:'Submitted',under_review:'Under Review',needs_more_info:'Needs More Information',approved:'Approved',ready_for_pickup:'Ready for Pickup',released:'Released',rejected:'Rejected',not_found:'Not Found'};
let active=location.pathname.includes('request-status')?'track':'request';

async function apiCall(path,opt={}){
    let r=await fetch(api+path,{credentials:'same-origin',headers:{...(opt.body instanceof FormData?{}:{'Content-Type':'application/json'}),...(opt.headers||{})},...opt});
    let body=await r.json().catch(()=>({error:'Server returned an invalid response'}));
    if(!r.ok)throw Error(body.error||'Request failed');
    return body;
}

function field(label,name,type='text',extra=''){
    return `<div><label class="field-label block mb-1">${label}</label><input name="${name}" type="${type}" class="w-full border border-slate2-300 rounded-md p-2.5 text-sm" ${extra}></div>`;
}

function select(label,name,options){
    return `<div><label class="field-label block mb-1">${label}</label><select name="${name}" class="w-full border border-slate2-300 rounded-md p-2.5 text-sm">${options.map(o=>`<option value="${esc(o[0])}">${esc(o[1])}</option>`).join('')}</select></div>`;
}

function requestForm(){
    $('#portal-content').innerHTML=`<form id="request-form" class="space-y-6">
        <section>
            <h2 class="font-semibold mb-3">Requester Details</h2>
            <div class="grid md:grid-cols-2 gap-4">
                ${field('FULL NAME','requester_name','text','required maxlength="200"')}
                ${field('MOBILE NUMBER','requester_phone','tel','required maxlength="50"')}
                ${field('EMAIL ADDRESS','requester_email','email','maxlength="150"')}
                ${field('RELATIONSHIP TO PERSON','relationship_to_person','text','maxlength="100" placeholder="Self, parent, spouse, child..."')}
                ${select('PURPOSE','purpose',[['Personal Record','Personal Record'],['Marriage Preparation','Marriage Preparation'],['School Requirement','School Requirement'],['Employment','Employment'],['Travel/Passport','Travel/Passport'],['Other','Other']])}
                ${select('RELEASE OPTION','delivery_method',[['pickup','Pickup at parish office'],['email_copy','Email copy after approval'],['courier','Courier / delivery coordination']])}
            </div>
        </section>
        <section>
            <h2 class="font-semibold mb-3">Certificate Details</h2>
            <div class="grid md:grid-cols-2 gap-4">
                ${select('CERTIFICATE TYPE','sacrament_type',[['Baptism','Baptism'],['Communion','First Communion'],['Confirmation','Confirmation'],['Marriage','Marriage'],['Death','Death']])}
                ${select('GENDER','person_gender',[['Unknown','Unknown'],['Female','Female'],['Male','Male']])}
                ${field('FIRST NAME','person_first_name','text','required maxlength="100"')}
                ${field('MIDDLE NAME','person_middle_name','text','maxlength="100"')}
                ${field('LAST NAME','person_last_name','text','required maxlength="100"')}
                ${field('DATE OF BIRTH','person_date_of_birth','date')}
                ${field("FATHER'S NAME",'father_name','text','maxlength="200"')}
                ${field("MOTHER'S MAIDEN NAME",'mother_maiden_name','text','maxlength="200"')}
                ${field('SPOUSE NAME','spouse_name','text','maxlength="200"')}
                ${field('SACRAMENT DATE','event_date','date')}
                ${field('APPROXIMATE YEAR','event_year','text','maxlength="4" pattern="[0-9]{4}" placeholder="YYYY"')}
            </div>
        </section>
        <section>
            <h2 class="font-semibold mb-3">Supporting Information</h2>
            <div class="grid gap-4">
                <div><label class="field-label block mb-1">NOTES</label><textarea name="notes" rows="4" class="w-full border border-slate2-300 rounded-md p-2.5 text-sm" maxlength="4000" placeholder="Book/page reference, old parish name, spelling notes, or other helpful details"></textarea></div>
                <div><label class="field-label block mb-1">VALID ID OR AUTHORIZATION FILE</label><input name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border border-slate2-300 rounded-md p-2.5 text-sm bg-paper"><p class="text-xs text-slate2-500 mt-1">Accepted: JPG, PNG, or PDF up to 4MB.</p></div>
            </div>
        </section>
        <div id="request-message" class="hidden rounded-md p-4 text-sm"></div>
        <div class="flex justify-end"><button class="bg-gold-600 text-white px-5 py-2.5 rounded-md text-sm font-semibold">Submit Request</button></div>
    </form>`;
    $('#request-form').onsubmit=submitRequest;
}

async function submitRequest(e){
    e.preventDefault();
    let msg=$('#request-message');
    msg.className='rounded-md p-4 text-sm bg-paper border border-slate2-300 text-slate2-700';
    msg.textContent='Submitting request...';
    try{
        let x=await apiCall('/certificate-requests',{method:'POST',body:new FormData(e.target)});
        let code=x.data.tracking_code;
        e.target.reset();
        msg.className='rounded-md p-4 text-sm bg-green-50 border border-green-200 text-green-800';
        msg.innerHTML=`Request submitted. Your tracking code is <b class="font-mono">${esc(code)}</b>.`;
    }catch(err){
        msg.className='rounded-md p-4 text-sm bg-wine-100 border border-wine-600/30 text-wine-700';
        msg.textContent=err.message;
    }
}

function trackForm(){
    $('#portal-content').innerHTML=`<form id="track-form" class="grid md:grid-cols-[1fr_auto] gap-3">
        <div><label class="field-label block mb-1">TRACKING CODE</label><input name="tracking_code" class="w-full border border-slate2-300 rounded-md p-2.5 text-sm font-mono uppercase" placeholder="PCR-260915-ABC123" required></div>
        <button class="self-end bg-navy-800 text-white px-5 py-2.5 rounded-md text-sm font-semibold">Check Status</button>
    </form><div id="track-result" class="mt-5"></div>`;
    $('#track-form').onsubmit=trackRequest;
}

async function trackRequest(e){
    e.preventDefault();
    let code=new FormData(e.target).get('tracking_code');
    $('#track-result').innerHTML='<div class="bg-paper border border-slate2-300 rounded-md p-4 text-sm text-slate2-600">Checking request...</div>';
    try{
        let x=await apiCall('/certificate-requests/track?tracking_code='+encodeURIComponent(code));
        renderTrack(x.data);
    }catch(err){
        $('#track-result').innerHTML=`<div class="bg-wine-100 border border-wine-600/30 rounded-md p-4 text-sm text-wine-700">${esc(err.message)}</div>`;
    }
}

function renderTrack(r){
    let logs=r.status_logs||[];
    $('#track-result').innerHTML=`<section class="bg-white border border-slate2-300 rounded-lg overflow-hidden">
        <div class="p-5 border-b">
            <p class="text-xs text-slate2-500">TRACKING CODE</p>
            <h2 class="font-mono text-lg font-semibold mt-1">${esc(r.tracking_code)}</h2>
            <p class="mt-3 inline-flex px-2.5 py-1 rounded-full bg-gold-100 text-gold-700 text-xs font-semibold">${esc(labels[r.status]||r.status)}</p>
            ${r.public_note?`<p class="mt-4 text-sm bg-paper rounded-md p-3">${esc(r.public_note)}</p>`:''}
        </div>
        <div class="p-5 grid gap-3 text-sm">
            <div class="flex justify-between gap-3"><span class="text-slate2-500">Certificate</span><b>${esc(r.sacrament_type)}</b></div>
            <div class="flex justify-between gap-3"><span class="text-slate2-500">Name</span><b>${esc(r.person_name)}</b></div>
            <div class="flex justify-between gap-3"><span class="text-slate2-500">Submitted</span><b>${esc(r.submitted_at||'')}</b></div>
        </div>
        <div class="p-5 border-t bg-paper">
            <h3 class="font-semibold text-sm mb-3">Status History</h3>
            <div class="grid gap-3">${logs.map(l=>`<div class="flex gap-3 items-start"><span class="status-dot done mt-1"></span><div><b class="text-sm">${esc(labels[l.status]||l.status)}</b><p class="text-xs text-slate2-500">${esc(l.created_at||'')}</p></div></div>`).join('')||'<p class="text-sm text-slate2-500">No status updates yet.</p>'}</div>
        </div>
    </section>`;
}

function setTab(tab){
    active=tab;
    $('#request-tab').className=`portal-tab px-4 py-2 rounded-md text-sm font-semibold ${tab==='request'?'active':'bg-paper text-slate2-700'}`;
    $('#track-tab').className=`portal-tab px-4 py-2 rounded-md text-sm font-semibold ${tab==='track'?'active':'bg-paper text-slate2-700'}`;
    tab==='request'?requestForm():trackForm();
}

$('#request-tab').onclick=()=>setTab('request');
$('#track-tab').onclick=()=>setTab('track');
setTab(active);
