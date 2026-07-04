/*
================================================
FICHIER: assets/js/search-trip.js
Description: Filtrage asynchrone des résultats de recherche.
             Quand l'utilisateur change un filtre (prix, énergie), on interroge
             search-api.php en fetch() et on reconstruit la liste des trajets
             dans le DOM, sans recharger la page.
================================================
*/

document.addEventListener("DOMContentLoaded", function () {
  // Le formulaire de filtres n'existe que sur la page de résultats.
  // S'il est absent, on ne fait rien (page d'accueil de recherche, etc.).
  const filtresForm = document.getElementById("filtres-form");
  if (!filtresForm) {
    return;
  }

  const resultsContainer = document.getElementById("trips-results");
  const infoElement = document.getElementById("filtres-info");
  const activeFiltersElement = document.getElementById("filtres-actifs");

  const maxPriceInput = filtresForm.querySelector('[name="max_price"]');
  const fuelTypeSelect = filtresForm.querySelector('[name="fuel_type"]');

  // 1. Soumission du formulaire de filtres : on intercepte pour éviter le rechargement.
  filtresForm.addEventListener("submit", function (event) {
    event.preventDefault();
    lancerRecherche();
  });

  // 2. Filtrage "en direct" : dès qu'on change un filtre, on relance la recherche.
  if (fuelTypeSelect) {
    fuelTypeSelect.addEventListener("change", lancerRecherche);
  }
  if (maxPriceInput) {
    // On ne garde que les chiffres : si l'utilisateur tape un point, une virgule
    // ou une lettre, le caractère est retiré immédiatement. Le champ reste donc
    // toujours dans un état valide (un nombre entier, ou vide).
    maxPriceInput.addEventListener("input", function () {
      this.value = this.value.replace(/[^0-9]/g, "");
    });
    // Après la frappe, on attend un court instant avant de filtrer (anti-rebond).
    maxPriceInput.addEventListener("input", debounce(lancerRecherche, 750));
  }

  /**
   * Construit l'URL, appelle l'API et déclenche l'affichage.
   */
  function lancerRecherche() {
    const params = new URLSearchParams({
      depart: filtresForm.querySelector('[name="depart"]').value,
      arrivee: filtresForm.querySelector('[name="arrivee"]').value,
      date: filtresForm.querySelector('[name="date"]').value,
      max_price: maxPriceInput ? maxPriceInput.value : "",
      fuel_type: fuelTypeSelect ? fuelTypeSelect.value : "",
    });

    resultsContainer.classList.add("is-loading");

    fetch("search-api.php?" + params.toString())
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        afficherResultats(
          data,
          params.get("max_price"),
          params.get("fuel_type"),
        );
      })
      .catch(function () {
        resultsContainer.innerHTML =
          '<div class="alert alert-danger" role="alert">Une erreur est survenue lors de la recherche.</div>';
      })
      .finally(function () {
        resultsContainer.classList.remove("is-loading");
      });
  }

  /**
   * Met à jour le compteur, les filtres actifs et la liste des trajets.
   */
  function afficherResultats(data, maxPrice, fuelType) {
    const trips = data.trips || [];
    const count = data.count || 0;

    // Compteur dans la barre latérale
    if (infoElement) {
      infoElement.textContent =
        count +
        " trajet" +
        (count > 1 ? "s" : "") +
        " trouvé" +
        (count > 1 ? "s" : "");
    }

    // Rappel des filtres actifs
    if (activeFiltersElement) {
      let badges = "";
      if (maxPrice) {
        badges +=
          '<span class="badge bg-info">Max ' +
          escapeHtml(maxPrice) +
          "€</span> ";
      }
      if (fuelType) {
        badges +=
          '<span class="badge bg-success">' +
          escapeHtml(ucfirst(fuelType)) +
          "</span>";
      }
      activeFiltersElement.innerHTML = badges
        ? '<span class="text-muted">Filtres actifs :</span> ' + badges
        : "";
    }

    // Liste des trajets ou message "aucun résultat"
    if (count === 0) {
      resultsContainer.innerHTML = buildNoResults();
      return;
    }

    resultsContainer.innerHTML =
      '<div class="trips-list">' + trips.map(buildTripCard).join("") + "</div>";
  }

  /**
   * Construit le HTML d'une carte de trajet (même structure que le rendu PHP).
   */
  function buildTripCard(trip) {
    const depart = escapeHtml(trip.departure_city);
    const arrivee = escapeHtml(trip.arrival_city);
    const moment = formatDateHeure(trip.departure_time);
    const conducteur = escapeHtml(trip.driver_name || "");
    const initiale = conducteur ? conducteur.charAt(0).toUpperCase() : "?";
    const marque = escapeHtml(trip.brand || "");
    const modele = escapeHtml(trip.model || "");
    const prix = Math.round(parseFloat(trip.price_per_seat));
    const places = parseInt(trip.available_seats, 10);
    const placesLabel = places > 1 ? "places" : "place";
    const badgeEco =
      trip.fuel_type === "électrique"
        ? '<span class="eco-badge">⚡Éco</span>'
        : "";

    // Bloc préférences (présent seulement si le conducteur en a indiqué)
    let blocBas;
    if (trip.preferences) {
      const prefs = escapeHtml(trip.preferences);
      blocBas =
        '<div class="row mt-2"><div class="col-12"><div class="trip-preferences">' +
        '<div class="preferences-content">' +
        '<small class="text-muted"><i class="fas fa-info-circle"></i> Préférences :</small> ' +
        '<span class="preferences-badge" title="' +
        prefs +
        '">' +
        prefs +
        "</span>" +
        "</div>" +
        '<a href="?page=detail&id=' +
        encodeURIComponent(trip.id) +
        '" class="btn btn-outline-success btn-sm">' +
        '<i class="fas fa-eye"></i> Voir détail</a>' +
        "</div></div></div>";
    } else {
      blocBas =
        '<div class="row mt-2"><div class="col-12"><div class="trip-preferences">' +
        "<div></div>" +
        '<a href="?page=detail&id=' +
        encodeURIComponent(trip.id) +
        '" class="btn btn-outline-success btn-sm">' +
        '<i class="fas fa-eye"></i> Voir détail</a>' +
        "</div></div></div>";
    }

    return (
      "" +
      '<div class="trip-card">' +
      '<div class="row align-items-center">' +
      '<div class="col-md-6"><div class="trip-route">' +
      '<h4 class="route-cities">' +
      depart +
      ' <i class="fas fa-arrow-right text-success"></i> ' +
      arrivee +
      "</h4>" +
      '<p class="route-time">' +
      '<i class="fas fa-calendar"></i> ' +
      moment.date +
      ' <span class="ms-2"><i class="fas fa-clock"></i> ' +
      moment.heure +
      "</span>" +
      "</p>" +
      "</div></div>" +
      '<div class="col-md-3"><div class="trip-driver"><div class="driver-info">' +
      '<div class="driver-avatar">' +
      escapeHtml(initiale) +
      "</div>" +
      '<div class="driver-details">' +
      "<strong>" +
      conducteur +
      "</strong>" +
      '<div class="driver-rating"><span class="stars">★★★★☆</span>' +
      '<span class="rating-text">4.2/5</span></div>' +
      '<div class="vehicle-info"><small class="text-muted">' +
      marque +
      " " +
      modele +
      " " +
      badgeEco +
      "</small></div>" +
      "</div>" +
      "</div></div></div>" +
      '<div class="col-md-3 text-end"><div class="trip-booking">' +
      '<div class="trip-price"><span class="price-amount">' +
      prix +
      "€</span>" +
      '<small class="price-label">par place</small></div>' +
      '<div class="trip-seats"><i class="fas fa-users"></i> ' +
      places +
      " " +
      placesLabel +
      "</div>" +
      "</div></div>" +
      "</div>" +
      blocBas +
      "</div>"
    );
  }

  /**
   * HTML affiché quand aucun trajet ne correspond aux filtres.
   */
  function buildNoResults() {
    return (
      "" +
      '<div class="no-results"><div class="text-center py-5">' +
      '<i class="fas fa-search fa-4x text-muted mb-4"></i>' +
      '<h4 class="text-muted mb-3">Aucun trajet trouvé</h4>' +
      '<p class="text-muted mb-4">Aucun trajet ne correspond à vos filtres.' +
      "<br>Essayez d'élargir votre recherche.</p>" +
      '<button type="button" class="btn btn-outline-primary" onclick="clearFilters()">' +
      '<i class="fas fa-eraser"></i> Réinitialiser les filtres</button>' +
      "</div></div>"
    );
  }

  // On expose lancerRecherche pour que le bouton "Réinitialiser" puisse la rappeler.
  window.__relancerRecherche = lancerRecherche;
});

/* ---------- Fonctions utilitaires ---------- */

/**
 * Réinitialise les filtres puis relance la recherche.
 * (Appelée par les boutons "Réinitialiser les filtres".)
 */
function clearFilters() {
  const form = document.getElementById("filtres-form");
  if (!form) {
    return;
  }
  const maxPrice = form.querySelector('[name="max_price"]');
  const fuelType = form.querySelector('[name="fuel_type"]');
  if (maxPrice) maxPrice.value = "";
  if (fuelType) fuelType.value = "";

  if (typeof window.__relancerRecherche === "function") {
    window.__relancerRecherche();
  }
}

/**
 * Échappe les caractères HTML pour éviter les injections (XSS) lors de
 * l'insertion de données dans le DOM.
 */
function escapeHtml(valeur) {
  if (valeur === null || valeur === undefined) {
    return "";
  }
  return String(valeur)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/**
 * Met la première lettre en majuscule.
 */
function ucfirst(texte) {
  if (!texte) {
    return "";
  }
  return texte.charAt(0).toUpperCase() + texte.slice(1);
}

/**
 * Transforme une date SQL ("2026-07-10 08:00:00") en date et heure lisibles.
 */
function formatDateHeure(dateSql) {
  if (!dateSql) {
    return { date: "", heure: "" };
  }
  // On remplace l'espace par "T" pour une compatibilité correcte entre navigateurs.
  const d = new Date(String(dateSql).replace(" ", "T"));
  if (isNaN(d.getTime())) {
    return { date: "", heure: "" };
  }
  const deuxChiffres = function (n) {
    return String(n).padStart(2, "0");
  };
  return {
    date:
      deuxChiffres(d.getDate()) +
      "/" +
      deuxChiffres(d.getMonth() + 1) +
      "/" +
      d.getFullYear(),
    heure: deuxChiffres(d.getHours()) + ":" + deuxChiffres(d.getMinutes()),
  };
}

/**
 * Retarde l'exécution d'une fonction tant que l'utilisateur agit (anti-rebond).
 * Évite d'envoyer une requête à chaque frappe dans le champ prix.
 */
function debounce(fonction, delai) {
  let minuteur;
  return function () {
    const contexte = this;
    const args = arguments;
    clearTimeout(minuteur);
    minuteur = setTimeout(function () {
      fonction.apply(contexte, args);
    }, delai);
  };
}
