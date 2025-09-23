/**
 * EcoRide - Script de validation formulaire de contact
 * Gère la validation temps réel et avant envoi + UX améliorée
 */

// Attendre que la page soit complètement chargée avant d'exécuter le script
document.addEventListener("DOMContentLoaded", function () {
  // =====================================================
  // RÉCUPÉRATION DES ÉLÉMENTS DOM
  // =====================================================

  // Récupère le formulaire de contact principal
  const form = document.querySelector(".contact-form");

  // Récupère le bouton de soumission
  const submitBtn = document.getElementById("submitBtn");

  // Récupère les éléments texte et loading du bouton pour l'animation
  const btnText = submitBtn.querySelector(".btn-text"); // Texte normal du bouton
  const btnLoading = submitBtn.querySelector(".btn-loading"); // Animation "chargement"

  // =====================================================
  // VALIDATION TEMPS RÉEL (AU FOCUS/BLUR)
  // =====================================================

  // Vérifier que le formulaire existe avant d'ajouter les événements
  if (form) {
    // Récupère tous les champs de saisie du formulaire
    const inputs = form.querySelectorAll("input, textarea, select");

    // Pour chaque champ de saisie, ajouter une validation temps réel
    inputs.forEach((input) => {
      // Écouter l'événement "blur" (quand l'utilisateur quitte le champ)
      input.addEventListener("blur", function () {
        // Vérifier si le champ est vide ET obligatoire
        if (this.value.trim() === "" && this.hasAttribute("required")) {
          // Ajouter la classe Bootstrap pour affichage erreur (bordure rouge)
          this.classList.add("is-invalid");
        } else {
          // Retirer la classe d'erreur si le champ est valide
          this.classList.remove("is-invalid");
        }
      });
    });

    // =====================================================
    // VALIDATION AVANT ENVOI DU FORMULAIRE
    // =====================================================

    // Écouter l'événement de soumission du formulaire
    form.addEventListener("submit", function (e) {
      // ---------------------------------------------
      // VALIDATION DU NOM COMPLET
      // ---------------------------------------------

      // Récupérer la valeur du champ nom et supprimer espaces début/fin
      const name = document.getElementById("name").value.trim();

      // Vérifier que le nom contient au moins 2 caractères
      if (name.length < 2) {
        // Empêcher l'envoi du formulaire
        e.preventDefault();

        // Afficher une alerte utilisateur
        showAlert("Le nom doit contenir au moins 2 caractères.", "warning");

        // Mettre le focus sur le champ nom pour correction
        document.getElementById("name").focus();

        // Arrêter l'exécution de la fonction
        return false;
      }

      // ---------------------------------------------
      // VALIDATION DE L'ADRESSE EMAIL
      // ---------------------------------------------

      // Récupérer la valeur de l'email
      const email = document.getElementById("email").value.trim();

      // Expression régulière pour valider le format email
      // Expliqué : [caractères autorisés]@[domaine].[extension]
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      // Tester si l'email correspond au format attendu
      if (!emailRegex.test(email)) {
        // Empêcher l'envoi
        e.preventDefault();

        // Alerte d'erreur
        showAlert("Veuillez saisir un email valide.", "warning");

        // Focus sur le champ email
        document.getElementById("email").focus();

        return false;
      }

      // ---------------------------------------------
      // VALIDATION DU MESSAGE
      // ---------------------------------------------

      // Récupérer le contenu du message
      const message = document.getElementById("message").value.trim();

      // Vérifier longueur minimale du message (10 caractères)
      if (message.length < 10) {
        // Empêcher l'envoi
        e.preventDefault();

        // Alerte avec message spécifique
        showAlert(
          "Le message doit contenir au moins 10 caractères.",
          "warning"
        );

        // Focus sur la zone de texte message
        document.getElementById("message").focus();

        return false;
      }

      // ---------------------------------------------
      // ANIMATION DE CHARGEMENT (si toutes validations OK)
      // ---------------------------------------------

      // Masquer le texte normal du bouton ("Envoyer le message")
      btnText.classList.add("d-none");

      // Afficher l'animation de chargement ("Envoi en cours...")
      btnLoading.classList.remove("d-none");

      // Désactiver le bouton pour éviter double-clic
      submitBtn.disabled = true;

      // Note : Le formulaire sera maintenant envoyé au serveur PHP
      // L'animation restera visible jusqu'au rechargement de la page
    });
  }

  // =====================================================
  // FONCTION D'AFFICHAGE D'ALERTES DYNAMIQUES
  // =====================================================

  /**
   * Affiche une alerte temporaire dans l'interface
   * @param {string} message - Le message à afficher
   * @param {string} type - Type d'alerte (info, warning, danger, success)
   */
  function showAlert(message, type = "info") {
    // ---------------------------------------------
    // NETTOYAGE DES ALERTES EXISTANTES
    // ---------------------------------------------

    // Chercher toutes les alertes temporaires précédentes
    const existingAlerts = document.querySelectorAll(".alert-temporary");

    // Supprimer chaque alerte existante pour éviter l'accumulation
    existingAlerts.forEach((alert) => alert.remove());

    // ---------------------------------------------
    // CRÉATION DE LA NOUVELLE ALERTE
    // ---------------------------------------------

    // Créer un nouvel élément div pour l'alerte
    const alertDiv = document.createElement("div");

    // Définir les classes CSS Bootstrap + classe temporaire pour identification
    alertDiv.className = `alert alert-${type} alert-temporary mt-3`;

    // Définir le contenu HTML avec icône + message
    alertDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;

    // ---------------------------------------------
    // INSERTION DE L'ALERTE DANS LA PAGE
    // ---------------------------------------------

    // Trouver la carte contenant le formulaire
    const formCard = document.querySelector(".contact-form-card");

    // Insérer l'alerte juste après le premier élément de la carte
    // (généralement après le titre, avant le formulaire)
    formCard.insertBefore(alertDiv, formCard.firstChild.nextSibling);

    // ---------------------------------------------
    // AUTO-SUPPRESSION DE L'ALERTE
    // ---------------------------------------------

    // Programmer la suppression automatique après 5 secondes
    setTimeout(() => {
      // Vérifier que l'alerte existe encore dans le DOM
      if (alertDiv.parentNode) {
        // Supprimer l'alerte du DOM
        alertDiv.remove();
      }
    }, 5000); // 5000 millisecondes = 5 secondes
  }

  // =====================================================
  // FONCTIONNALITÉS BONUS (OPTIONNELLES)
  // =====================================================

  // Auto-focus sur le premier champ au chargement de la page
  const firstInput = document.getElementById("name");
  if (firstInput) {
    // Mettre automatiquement le curseur dans le champ nom
    firstInput.focus();
  }

  // Compteur de caractères pour le message (optionnel)
  const messageTextarea = document.getElementById("message");
  if (messageTextarea) {
    // Ajouter un compteur sous la zone de texte
    const charCounter = document.createElement("small");
    charCounter.className = "text-muted float-end";
    charCounter.textContent = "0 caractères";

    // Insérer le compteur après la zone de texte
    messageTextarea.parentNode.appendChild(charCounter);

    // Mettre à jour le compteur en temps réel
    messageTextarea.addEventListener("input", function () {
      const length = this.value.length;
      charCounter.textContent = `${length} caractère${length !== 1 ? "s" : ""}`;

      // Changer la couleur selon la longueur
      if (length < 10) {
        charCounter.className = "text-danger float-end";
      } else if (length < 50) {
        charCounter.className = "text-warning float-end";
      } else {
        charCounter.className = "text-success float-end";
      }
    });
  }
}); // Fin du DOMContentLoaded

/**
 * RÉSUMÉ DU SCRIPT :
 *
 * 1. VALIDATION TEMPS RÉEL : Vérifie chaque champ quand l'utilisateur le quitte
 * 2. VALIDATION AVANT ENVOI : Contrôles approfondis avant soumission
 * 3. UX AMÉLIORÉE : Animations, alertes, compteurs, focus automatique
 * 4. SÉCURITÉ : Validation côté client (complément du côté serveur)
 * 5. ACCESSIBILITÉ : Focus approprié, messages clairs, feedback visuel
 *
 * Ce script améliore considérablement l'expérience utilisateur tout en
 * maintenant une validation rigoureuse des données saisies.
 */
