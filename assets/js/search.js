// Section Aucun résultat

function clearFilters() {
  // Réinitialiser le formulaire de recherche
  document.getElementById("departure").value = "";
  document.getElementById("arrival").value = "";
  document.getElementById("date").value = "";

  // Réinitialiser aussi les filtres si ils existent
  const maxPrice = document.getElementById("max_price");
  const fuelPreference = document.getElementById("fuel_preference");

  if (maxPrice) maxPrice.value = "";
  if (fuelPreference) fuelPreference.value = "";

  // Recharger la page sans paramètres
  window.location.href = "?page=search";
}
