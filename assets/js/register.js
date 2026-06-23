// Initialisation globale au chargement du DOM
document.addEventListener("DOMContentLoaded", function () {
  // Éléments du formulaire
  const password = document.getElementById("password");
  const passwordConfirm = document.getElementById("password_confirm");
  const passwordField = document.getElementById("password");

  // Initialiser toutes les fonctionnalités
  initPasswordValidation();
  initPasswordStrength();
});

// Fonction pour basculer la visibilité du mot de passe
function togglePasswordVisibility(fieldId) {
  const field = document.getElementById(fieldId);
  const icon = document.getElementById(
    "eye" + fieldId.charAt(0).toUpperCase() + fieldId.slice(1)
  );

  if (field && icon) {
    if (field.type === "password") {
      field.type = "text";
      icon.className = "fas fa-eye-slash";
    } else {
      field.type = "password";
      icon.className = "fas fa-eye";
    }
  }
}

// Initialisation de la validation des mots de passe
function initPasswordValidation() {
  const password = document.getElementById("password");
  const passwordConfirm = document.getElementById("password_confirm");

  function checkPasswordMatch() {
    if (passwordConfirm.value && password.value !== passwordConfirm.value) {
      passwordConfirm.setCustomValidity(
        "Les mots de passe ne correspondent pas"
      );
      passwordConfirm.classList.add("is-invalid");
    } else {
      passwordConfirm.setCustomValidity("");
      passwordConfirm.classList.remove("is-invalid");
    }
  }

  // Ajouter les événements seulement si les éléments existent
  if (password && passwordConfirm) {
    password.addEventListener("input", checkPasswordMatch);
    passwordConfirm.addEventListener("input", checkPasswordMatch);
  }
}

// Vérification force du mot de passe
function checkPasswordStrength(password) {
  let strength = 0;
  let feedback = [];

  if (password.length >= 8) strength++;
  else feedback.push("8 caractères minimum");

  if (/[a-z]/.test(password)) strength++;
  else feedback.push("une minuscule");

  if (/[A-Z]/.test(password)) strength++;
  else feedback.push("une majuscule");

  if (/[0-9]/.test(password)) strength++;
  else feedback.push("un chiffre");

  if (/[^A-Za-z0-9]/.test(password)) strength++;
  else feedback.push("un caractère spécial");

  return {
    strength,
    feedback,
  };
}

// Initialisation de l'indicateur de force
function initPasswordStrength() {
  const passwordField = document.getElementById("password");
  const indicator = document.getElementById("passwordStrength");

  // Vérifier que les éléments existent avant d'ajouter les événements
  if (passwordField && indicator) {
    passwordField.addEventListener("input", function () {
      const password = this.value;

      // Ne pas afficher l'indicateur si le champ est vide
      if (password.length === 0) {
        indicator.innerHTML = "";
        return;
      }

      const result = checkPasswordStrength(password);

      let color, text;
      if (result.strength <= 2) {
        color = "danger";
        text = "Faible";
      } else if (result.strength <= 3) {
        color = "warning";
        text = "Moyen";
      } else {
        color = "success";
        text = "Fort";
      }

      indicator.innerHTML = `
                <div class="mt-2">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-${color}" style="width: ${
        result.strength * 20
      }%"></div>
                    </div>
                    <small class="text-${color}">Force : ${text}</small>
                    ${
                      result.feedback.length > 0
                        ? `<br><small class="text-muted">Manque : ${result.feedback.join(
                            ", "
                          )}</small>`
                        : ""
                    }
                </div>
            `;
    });
  }
}

// Fonction utilitaire pour effacer l'indicateur de force
function clearPasswordStrength() {
  const indicator = document.getElementById("passwordStrength");
  if (indicator) {
    indicator.innerHTML = "";
  }
}

// Validation du formulaire avant soumission (optionnel)
function validateForm() {
  const password = document.getElementById("password");
  const passwordConfirm = document.getElementById("password_confirm");

  if (password && passwordConfirm) {
    if (password.value !== passwordConfirm.value) {
      alert("Les mots de passe ne correspondent pas");
      return false;
    }
  }

  return true;
}
