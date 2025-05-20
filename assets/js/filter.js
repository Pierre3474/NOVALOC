
const grid=document.querySelector('.cars-grid');
const searchInput=document.getElementById('searchInput');
let filters={type:'all',motorisation:'all',marque:'all'};

function applyFilters(){
  const cards=Array.from(grid.children);
  let anyVisible=false;
  cards.forEach(card=>{
    const t=card.dataset.type,m=card.dataset.motorisation,b=card.dataset.marque;
    const txt=card.querySelector('h3').innerText.toLowerCase();
    const okSearch=!searchInput.value||txt.includes(searchInput.value.toLowerCase());
    const okType=filters.type==='all'||t===filters.type;
    const okMotor=filters.motorisation==='all'||m===filters.motorisation;
    const okMarque=filters.marque==='all'||b===filters.marque;
    const show=okSearch&&okType&&okMotor&&okMarque;
    card.style.display=show?'':'none'; if(show) anyVisible=true;
  });
  document.getElementById('no-results').style.display=anyVisible?'none':'block';
  if(!anyVisible)setTimeout(()=>location.href='index.php',3000);
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.dropdown button').forEach(btn=>{
    btn.onclick=()=>{
      if(btn.dataset.filterKey)filters[btn.dataset.filterKey]=btn.dataset.filter;
      applyFilters();
    };
  });
  searchInput.oninput=applyFilters;
  applyFilters();
});
