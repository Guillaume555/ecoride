// Validation en temps réel de la confirmation de mot de passe
document.addEventListener("DOMContentLoaded", function () {
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

  if (password && passwordConfirm) {
    password.addEventListener("input", checkPasswordMatch);
    passwordConfirm.addEventListener("input", checkPasswordMatch);
  }
});
