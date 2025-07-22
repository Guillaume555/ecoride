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
    const pricePerSeat = parseFloat(seatsSelect.dataset.pricePerSeat || 25);

    seatsSelect.addEventListener("change", function () {
      const seats = parseInt(this.value);
      const total = seats * pricePerSeat;
      totalPriceElement.textContent = total + "€";
    });
  } else {
    console.warn("⚠️ Élément manquant : #seats ou #total-price introuvable.");
  }
});
