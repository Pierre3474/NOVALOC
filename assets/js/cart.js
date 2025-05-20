
document.querySelectorAll('.add-cart').forEach(btn=>{
  btn.onclick=()=>{
    let cart=JSON.parse(sessionStorage.getItem('cart')||'[]');
    cart.push(btn.dataset.id);
    sessionStorage.setItem('cart',JSON.stringify(cart));
    alert('Ajouté au panier');
  };
});

// Vérification stock AJAX
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.car-card').forEach(function(card) {
    var id = card.dataset.id;
    fetch('stock.php?car_id='+id)
      .then(r => r.json())
      .then(data => {
        var status = card.querySelector('.stock-status');
        if(status) {
          status.textContent = data.stock > 0 ? 'Disponible ('+data.stock+')' : 'Indisponible';
        }
      });
  });
});
