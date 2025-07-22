// Auto-focus sur le champ email si vide
document.addEventListener("DOMContentLoaded", function () {
  const emailField = document.getElementById("email");
  if (emailField && emailField.value === "") {
    emailField.focus();
  }
});

// Validation simple côté client
document.querySelector(".auth-form").addEventListener("submit", function (e) {
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  if (!email || !password) {
    e.preventDefault();
    alert("Veuillez remplir tous les champs obligatoires.");
    return false;
  }

  if (!email.includes("@")) {
    e.preventDefault();
    alert("Veuillez saisir une adresse email valide.");
    return false;
  }
});

// Affichage/masquage du mot de passe
document
  .getElementById("togglePassword")
  .addEventListener("click", function () {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passwordField.type === "password") {
      passwordField.type = "text";
      eyeIcon.classList.remove("fa-eye");
      eyeIcon.classList.add("fa-eye-slash");
    } else {
      passwordField.type = "password";
      eyeIcon.classList.remove("fa-eye-slash");
      eyeIcon.classList.add("fa-eye");
    }
  });
