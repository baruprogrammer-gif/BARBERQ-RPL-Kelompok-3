(() => {
  document.documentElement.classList.add('js');
  document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('#site-navigation');
    const closeNav = () => {
      if (!nav || !navToggle) return;
      nav.classList.remove('is-open');
      navToggle.setAttribute('aria-expanded','false');
      navToggle.setAttribute('aria-label','Buka menu navigasi');
    };
    if (navToggle && nav) {
      navToggle.addEventListener('click', () => {
        const opening = !nav.classList.contains('is-open');
        nav.classList.toggle('is-open', opening);
        navToggle.setAttribute('aria-expanded', String(opening));
        navToggle.setAttribute('aria-label', opening ? 'Tutup menu navigasi' : 'Buka menu navigasi');
      });
      document.addEventListener('keydown', e => { if(e.key==='Escape') closeNav(); });
      document.addEventListener('click', e => { if(!nav.contains(e.target) && !navToggle.contains(e.target)) closeNav(); });
      nav.querySelectorAll('a').forEach(link => link.addEventListener('click',closeNav));
    }
    document.querySelectorAll('[data-confirm]').forEach(el => {
      el.addEventListener('click', e => {
        if (!window.confirm(el.dataset.confirm || 'Lanjutkan tindakan ini?')) e.preventDefault();
      });
    });
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
      button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        if(!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Tutup' : 'Lihat';
        button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
      });
    });

    const dateInput = document.querySelector('[data-reservation-date]');
    const timeSelect = document.querySelector('[data-reservation-time]');
    const slotContainer = document.querySelector('[data-slot-buttons]');
    const slotSummary = document.querySelector('[data-slot-summary]');
    const serviceSelect = document.querySelector('[data-booking-service]');
    const bookingSummary = document.querySelector('[data-booking-summary]');
    const bookingForm = document.querySelector('[data-booking-form]');
    if(dateInput && timeSelect && slotContainer){
      timeSelect.required = false;
      const localNow = new Date();
      const minDate = `${localNow.getFullYear()}-${String(localNow.getMonth()+1).padStart(2,'0')}-${String(localNow.getDate()).padStart(2,'0')}`;
      dateInput.min = minDate;
      let requestCounter = 0;
      const updateSummary = () => {
        const selected = serviceSelect?.selectedOptions[0];
        if(bookingSummary){
          const name = selected?.dataset?.name;
          const price = selected?.dataset?.price;
          bookingSummary.textContent = name ? `${name} · ${price || ''}` : 'Siap buat reservasi?';
        }
      };
      const renderSlots = () => {
        slotContainer.replaceChildren();
        const options = [...timeSelect.options].filter(o=>o.value);
        let available = 0;
        options.forEach(option => {
          const btn = document.createElement('button');
          btn.type='button';
          btn.className='slot-btn';
          btn.textContent=option.value;
          btn.disabled=option.disabled;
          btn.setAttribute('aria-pressed',String(timeSelect.value===option.value));
          if(option.disabled) btn.setAttribute('aria-label',`${option.value} tidak tersedia`);
          if(!option.disabled) available++;
          if(timeSelect.value===option.value) btn.classList.add('selected');
          btn.addEventListener('click', () => {
            timeSelect.value=option.value;
            slotContainer.querySelectorAll('.slot-btn').forEach(b=>{
              const on=b===btn;b.classList.toggle('selected',on);b.setAttribute('aria-pressed',String(on));
            });
            if(slotSummary)slotSummary.textContent=`Jam ${option.value} dipilih untuk tanggal ${dateInput.value}.`;
          });
          slotContainer.appendChild(btn);
        });
        if(slotSummary && !timeSelect.value) slotSummary.textContent = options.length ? `${available} waktu masih tersedia. Pilih salah satu jam di atas.` : 'Belum ada pilihan waktu yang tersedia.';
      };
      const loadSlots = async () => {
        if(!dateInput.value) return;
        const counter=++requestCounter;
        const selectedBefore=timeSelect.value;
        timeSelect.innerHTML='<option value="">Memuat waktu...</option>';
        timeSelect.disabled=true;
        slotContainer.innerHTML='';
        if(slotSummary)slotSummary.textContent='Sedang memeriksa ketersediaan waktu...';
        try{
          const response=await fetch(`${dateInput.dataset.slotUrl}?date=${encodeURIComponent(dateInput.value)}`,{headers:{'Accept':'application/json'},credentials:'same-origin',cache:'no-store'});
          const data=await response.json();
          if(counter!==requestCounter)return;
          if(!response.ok||!data.ok)throw new Error(data.message||'Gagal memuat slot.');
          timeSelect.innerHTML='<option value="">Pilih waktu</option>';
          data.slots.forEach(slot=>{
            const option=document.createElement('option');option.value=slot.time;
            option.textContent=slot.available?slot.time:`${slot.time} — tidak tersedia`;
            option.disabled=!slot.available;
            if(slot.available&&selectedBefore===slot.time)option.selected=true;
            timeSelect.appendChild(option);
          });
          timeSelect.disabled=false;
          renderSlots();
        }catch(err){
          if(counter!==requestCounter)return;
          timeSelect.innerHTML='<option value="">Tidak dapat memuat jadwal</option>';
          timeSelect.disabled=true;
          if(slotSummary)slotSummary.textContent='Gagal memuat jadwal. Periksa koneksi lalu pilih tanggal lagi.';
        }
      };
      dateInput.addEventListener('change',loadSlots);
      serviceSelect?.addEventListener('change',updateSummary);
      bookingForm?.addEventListener('submit',e=>{
        if(!timeSelect.value){
          e.preventDefault();
          if(slotSummary)slotSummary.textContent='Pilih salah satu jam yang tersedia sebelum melanjutkan.';
          slotContainer.querySelector('.slot-btn:not(:disabled)')?.focus();
        }
      });
      renderSlots();updateSummary();
    }

    const queuePicker=document.querySelector('[data-queue-picker]');
    if(queuePicker){queuePicker.addEventListener('change',()=>{window.location.href=queuePicker.value;});}

    const liveQueue=document.querySelector('[data-live-queue]');
    if(liveQueue){
      const statusEl=liveQueue.querySelector('[data-queue-status]');
      const noEl=liveQueue.querySelector('[data-queue-number]');
      const stepEls=[...liveQueue.querySelectorAll('[data-queue-steps] .step')];
      const noticeEl=liveQueue.querySelector('[data-queue-notice]');
      const refreshedEl=liveQueue.querySelector('[data-queue-refresh-status]');
      const labels={BOOKED:'BOOKED',WAITING:'WAITING',IN_SERVICE:'IN_SERVICE',COMPLETED:'COMPLETED',CANCELLED:'CANCELLED'};
      const classes={BOOKED:'neutral',WAITING:'warn',IN_SERVICE:'warn',COMPLETED:'ok',CANCELLED:'danger'};
      const stepIndex={BOOKED:0,WAITING:1,IN_SERVICE:2,COMPLETED:3};
      const descriptions={BOOKED:'Reservasi sudah dibuat. Silakan datang sesuai jadwal.',WAITING:'Kamu sudah hadir dan sedang menunggu giliran.',IN_SERVICE:'Pelayanan sedang berlangsung.',COMPLETED:'Pelayanan selesai. Terima kasih sudah berkunjung!',CANCELLED:'Reservasi ini telah dibatalkan.'};
      const titleEl=liveQueue.querySelector('[data-queue-heading]');
      const descriptionEl=liveQueue.querySelector('[data-queue-description]');
      let checking=false;
      const refresh=async()=>{
        if(document.hidden||checking)return;
        checking=true;
        try{
          const response=await fetch(liveQueue.dataset.queueUrl,{credentials:'same-origin',headers:{'Accept':'application/json'},cache:'no-store'});
          if(!response.ok)throw Error('Tidak dapat memuat status');
          const data=await response.json();
          if(!data.ok)throw Error('Respons tidak valid');
          if(!data.queue){
            if(refreshedEl)refreshedEl.textContent='Tidak ada antrean aktif. Muat ulang halaman untuk melihat status terbaru.';
            return;
          }
          const q=data.queue;
          if(noEl)noEl.textContent=q.number??'—';
          if(statusEl){statusEl.textContent=labels[q.status]||q.status;statusEl.className=`badge ${classes[q.status]||'neutral'}`;}
          if(titleEl)titleEl.textContent=labels[q.status]||q.status;
          if(descriptionEl)descriptionEl.textContent=descriptions[q.status]||'Status reservasi.';
          const step=stepIndex[q.status];
          stepEls.forEach((el,i)=>{
            el.classList.toggle('active',Number.isInteger(step)&&step===i);
            el.classList.toggle('is-done',Number.isInteger(step)&&step>i);
          });
          if(noticeEl){
            noticeEl.replaceChildren();
            if(q.status==='COMPLETED' || q.status==='CANCELLED'){
              const div=document.createElement('div');div.className='note';
              div.textContent=q.status==='CANCELLED'?'Reservasi ini tidak lagi aktif.':'Detail kunjungan tersedia di menu Riwayat.';
              noticeEl.appendChild(div);
            }
          }
          if(refreshedEl)refreshedEl.textContent=`Terakhir diperbarui ${new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}.`;
        }catch(err){if(refreshedEl)refreshedEl.textContent='Status belum dapat diperbarui. Coba muat ulang halaman.';}
        finally{checking=false;}
      };
      const polling=setInterval(refresh,4000);
      document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
      window.addEventListener('pagehide',()=>clearInterval(polling),{once:true});
    }
  });
})();
