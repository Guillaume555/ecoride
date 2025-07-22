<?php
// ========== FOOTER PRINCIPAL ECORIDE ==========
// Fichier : includes/footer.php
?>

<!-- Footer enrichi -->
<footer class="footer bg-dark text-white">
    <div class="container">
        <div class="row py-5">
            <!-- Colonne 1 : À propos -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5><i class="fas fa-leaf text-success"></i> EcoRide</h5>
                <p class="text-light">
                    La plateforme de covoiturage qui allie économie et écologie pour vos déplacements quotidiens.
                </p>
                <div class="social-links">
                    <a href="#" class="text-success me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-success me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-success me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-success"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Colonne 2 : Liens rapides -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6>Navigation</h6>
                <ul class="list-unstyled">
                    <li><a href="?page=home" class="text-light text-decoration-none">Accueil</a></li>
                    <li><a href="?page=search" class="text-light text-decoration-none">Covoiturages</a></li>
                    <li><a href="?page=about" class="text-light text-decoration-none">À propos</a></li>
                    <li><a href="?page=contact" class="text-light text-decoration-none">Contact</a></li>
                </ul>
            </div>

            <!-- Colonne 3 : Services -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6>Services</h6>
                <ul class="list-unstyled">
                    <li><a href="?page=register" class="text-light text-decoration-none">Devenir conducteur</a></li>
                    <li><a href="?page=search" class="text-light text-decoration-none">Trouver un trajet</a></li>
                    <li><a href="?page=help" class="text-light text-decoration-none">Aide</a></li>
                    <li><a href="?page=faq" class="text-light text-decoration-none">FAQ</a></li>
                </ul>
            </div>

            <!-- Colonne 4 : Contact -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h6>Contact</h6>
                <ul class="list-unstyled">
                    <li class="text-light">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="mailto:contact@ecoride.fr" class="text-success text-decoration-none">
                            contact@ecoride.fr
                        </a>
                    </li>
                    <li class="text-light">
                        <i class="fas fa-phone me-2"></i>
                        <a href="tel:+33123456789" class="text-light text-decoration-none">
                            +33 1 23 45 67 89
                        </a>
                    </li>
                    <li class="text-light">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        123 Rue de l'Écologie, 75000 Paris
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ligne de séparation -->
        <hr class="text-secondary">

        <!-- Copyright et mentions légales -->
        <div class="row py-3">
            <div class="col-md-6">
                <p class="mb-0 text-light">
                    &copy; 2025 <strong>EcoRide</strong>. Tous droits réservés.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="?page=legal" class="text-light text-decoration-none me-3">Mentions légales</a>
                <a href="?page=privacy" class="text-light text-decoration-none me-3">Confidentialité</a>
                <a href="?page=terms" class="text-light text-decoration-none">CGU</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts JavaScript -->
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript personnalisé -->
<script src="assets/js/main.js"></script>

<!-- JS spécifique à la page (optionnel) -->
<?php if (isset($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js_file): ?>
        <script src="assets/js/<?= $js_file ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Fermeture HTML -->
</body>

</html>

<?php
// Fichier appelé en bas de toutes les pages du site.

// Contenu :
// - Présentation courte de la plateforme
// - Liens de navigation rapide
// - Raccourcis vers les pages de services
// - Coordonnées de contact (email, téléphone)
// - Liens vers les réseaux sociaux

// Utilisé pour : uniformiser le pied de page et renforcer l’image de marque

?>