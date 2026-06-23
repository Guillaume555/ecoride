function revealCards() {
  const cards = document.querySelectorAll(".detail-card");
  cards.forEach((card) => {
    const cardTop = card.getBoundingClientRect().top;
    const windowHeight = window.innerHeight;
    if (cardTop < windowHeight * 0.8) {
      card.classList.add("card-visible");
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  // Animation de scroll + déclenchement initial
  revealCards();
  window.addEventListener("scroll", revealCards);

  // Gestion du prix total dynamique
  const seatsSelect = document.getElementById("seats");
  const totalPriceElement = document.getElementById("total-price");

  if (seatsSelect && totalPriceElement) {
    // Récupération du prix par place depuis l'attribut data
    const pricePerSeat = parseFloat(seatsSelect.dataset.pricePerSeat || 0);
    
    // 🐛 DEBUG : afficher ce qui est récupéré
    console.log("Prix par place récupéré :", pricePerSeat);
    console.log("Attribut data-price-per-seat :", seatsSelect.dataset.pricePerSeat);

    // Fonction de calcul du total
    function updateTotalPrice() {
      const seats = parseInt(seatsSelect.value);
      const total = seats * pricePerSeat;
      
      // 🐛 DEBUG : afficher le calcul
      console.log(`Calcul : ${seats} places × ${pricePerSeat}€ = ${total}€`);
      
      // Afficher avec 2 décimales (ou 0 si entier)
      totalPriceElement.textContent = total % 1 === 0 ? total + "€" : total.toFixed(2) + "€";
    }

    // ✅ CORRECTION : initialiser le prix dès le chargement
    updateTotalPrice();

    // Mettre à jour quand on change le nombre de places
    seatsSelect.addEventListener("change", updateTotalPrice);
  } else {
    console.warn("⚠️ Élément manquant : #seats ou #total-price introuvable.");
  }
});