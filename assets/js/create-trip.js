document.addEventListener("DOMContentLoaded", function () {
  // Gestion dynamique des places disponibles selon le véhicule
  const vehicleSelect = document.getElementById("vehicle_id");
  const seatsSelect = document.getElementById("available_seats");

  vehicleSelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];
    const maxSeats = selectedOption.getAttribute("data-seats");

    // Vider les options existantes
    seatsSelect.innerHTML = "";

    if (maxSeats) {
      const maxAvailable = Math.max(1, maxSeats - 1); // -1 pour le conducteur

      for (let i = 1; i <= maxAvailable; i++) {
        const option = document.createElement("option");
        option.value = i;
        option.textContent = i + " place" + (i > 1 ? "s" : "");
        seatsSelect.appendChild(option);
      }
    } else {
      const option = document.createElement("option");
      option.value = "";
      option.textContent = "Sélectionnez d'abord un véhicule";
      seatsSelect.appendChild(option);
    }
  });

  // Gestion des préférences prédéfinies
  const checkboxes = document.querySelectorAll(
    '.form-check-input[type="checkbox"]'
  );
  const preferencesTextarea = document.getElementById("preferences");

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      updatePreferences();
    });
  });

  function updatePreferences() {
    const selectedPrefs = [];

    if (document.getElementById("no_smoking").checked) {
      selectedPrefs.push("Non-fumeur");
    }
    if (document.getElementById("pets_allowed").checked) {
      selectedPrefs.push("Animaux autorisés");
    }
    if (document.getElementById("luggage_ok").checked) {
      selectedPrefs.push("Bagages autorisés");
    }

    let currentText = preferencesTextarea.value.trim();

    // Nettoyer les anciennes préférences automatiques
    currentText = currentText.replace(/Non-fumeur,?\s?/g, "");
    currentText = currentText.replace(/Animaux autorisés,?\s?/g, "");
    currentText = currentText.replace(/Bagages autorisés,?\s?/g, "");

    // Ajouter les nouvelles préférences
    if (selectedPrefs.length > 0) {
      const newPrefs = selectedPrefs.join(", ");
      if (currentText) {
        preferencesTextarea.value = newPrefs + ", " + currentText;
      } else {
        preferencesTextarea.value = newPrefs;
      }
    } else {
      preferencesTextarea.value = currentText;
    }
  }

  // Validation côté client
  const form = document.getElementById("createTripForm");
  form.addEventListener("submit", function (e) {
    const departureCity = document
      .getElementById("departure_city")
      .value.trim();
    const arrivalCity = document.getElementById("arrival_city").value.trim();

    if (departureCity.toLowerCase() === arrivalCity.toLowerCase()) {
      e.preventDefault();
      alert("Les villes de départ et d'arrivée doivent être différentes.");
      return false;
    }

    const departureDate = new Date(
      document.getElementById("departure_date").value
    );
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (departureDate < today) {
      e.preventDefault();
      alert("La date de départ ne peut pas être dans le passé.");
      return false;
    }
  });
});

// ================== Section add-Véhicule.php ===================================
document.addEventListener("DOMContentLoaded", function () {
  // Formatage automatique plaque d'immatriculation
  const plateInput = document.getElementById("license_plate");

  if (plateInput) {
    plateInput.addEventListener("input", function () {
      let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");

      if (value.length > 2) {
        value = value.substring(0, 2) + "-" + value.substring(2);
      }
      if (value.length > 6) {
        value = value.substring(0, 6) + "-" + value.substring(6);
      }
      if (value.length > 9) {
        value = value.substring(0, 9);
      }

      this.value = value;
    });
  }
});
