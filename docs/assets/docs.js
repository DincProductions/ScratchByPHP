
document.querySelectorAll('.copy').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    const code=btn.closest('.code-wrap').querySelector('code').innerText;
    try{await navigator.clipboard.writeText(code);btn.textContent='Kopyalandı';}
    catch(e){btn.textContent='Seçip kopyala';}
    setTimeout(()=>btn.textContent=btn.dataset.label||'Kopyala',1400);
  });
});
const search=document.querySelector('#doc-search');
if(search){
 search.addEventListener('input',()=>{
  const q=search.value.toLocaleLowerCase();
  document.querySelectorAll('.nav a').forEach(a=>{
    a.style.display=a.textContent.toLocaleLowerCase().includes(q)?'block':'none';
  });
 });
}
