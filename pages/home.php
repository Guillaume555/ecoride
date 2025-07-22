<?php
$extra_css = ['home.css']; // Style CSS de la page.
$page_title = "EcoRide - Accueil";
?>


<!-- ========== HERO SECTION ========== -->
<section class="hero-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-10">
                <h1 class="hero-title">Partageons la route, économisons la planète</h1>
                <p class="hero-slogan">La plateforme de covoiturage qui allie économie et écologie pour vos déplacements quotidiens</p>

                <!-- Search Box -->
                <div class="search-box">
                    <h3 class="search-title">Trouvez votre trajet</h3>
                    <form method="GET" action="?" class="search-form">
                        <input type="hidden" name="page" value="search">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="depart" placeholder="Ville de départ" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="arrivee" placeholder="Ville d'arrivée" required>
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" name="date">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-lg w-100 mt-3 btn-search">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </form>

                    <!-- Mentions flexibles -->
                    <div class="mt-4 d-flex justify-content-center gap-4 flex-wrap text-success">
                        <span><i class="fas fa-euro-sign me-2"></i> Économique</span>
                        <span><i class="fas fa-leaf me-2"></i> Écologique</span>
                        <span><i class="fa-regular fa-clock"></i> Flexible</span>
                        <span><i class="fas fa-shield-alt me-2"></i> Sécurité</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</section>

<!-- ========== POURQUOI ECORIDE ========== -->
<section class="why-ecoride-section">
    <div class="container">
        <h2>Pourquoi choisir EcoRide ?</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card">
                    <div class="card-body">
                        <div class="feature-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h5 class="card-title">Écologique</h5>
                        <p class="card-text">Réduisez votre empreinte carbone en partageant vos trajets. Chaque kilomètre partagé compte pour la planète.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card">
                    <div class="card-body">
                        <div class="feature-icon">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h5 class="card-title">Économique</h5>
                        <p class="card-text">Partagez les frais de carburant et de péage. Voyagez moins cher tout en rencontrant de nouvelles personnes.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card">
                    <div class="card-body">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title">Convivial</h5>
                        <p class="card-text">Créez du lien social lors de vos déplacements. Rencontrez des personnes partageant vos valeurs.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card feature-card">
                    <div class="card-body">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="card-title">Sécurisé</h5>
                        <p class="card-text">Profils vérifiés, avis utilisateurs et système de notation pour voyager en toute confiance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== TÉMOIGNAGES AVEC MOCKUP ========== -->
<section class="temoignages-section">
    <div class="container">
        <!-- Titre centré -->
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2>Ils nous font confiance</h2>
                <p class="lead text-muted">Découvrez les témoignages de nos utilisateurs satisfaits</p>
            </div>
        </div>

        <!-- Contenu : Témoignages + Mockup -->
        <div class="row align-items-center">
            <!-- Colonne gauche : Témoignages -->
            <div class="col-lg-7">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card testimonial-card">
                            <div class="card-body">
                                <div class="testimonial-header">
                                    <div class="testimonial-avatar">M</div>
                                    <div class="testimonial-info">
                                        <h6>Marie Dupont</h6>
                                        <div class="testimonial-stars">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="testimonial-text">"Grâce à EcoRide, j'ai divisé mes frais de transport par deux ! Les conducteurs sont sympas et l'application est super simple à utiliser."</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card testimonial-card">
                            <div class="card-body">
                                <div class="testimonial-header">
                                    <div class="testimonial-avatar">P</div>
                                    <div class="testimonial-info">
                                        <h6>Pierre Martin</h6>
                                        <div class="testimonial-stars">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="testimonial-text">"En tant que conducteur, j'apprécie de pouvoir amortir mes trajets tout en rendant service. L'aspect écologique est un vrai plus !"</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ajoutez d'autres témoignages si nécessaire -->
                </div>
            </div>

            <!-- Colonne droite : Mockup Mobile -->
            <div class="col-lg-5">
                <div class="mockup-container text-center">
                    <div class="mockup-content">
                        <h4 class="mockup-title">L'application mobile</h4>
                        <p class="mockup-subtitle">Disponible sur tous vos appareils</p>

                        <!-- Image mockup -->
                        <div class="mockup-phone">
                            <img src="assets/img/mockup-mobile.png" alt="EcoRide Mobile App" class="img-fluid mockup-image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- SECTION 4: NOS ENGAGEMENTS -->
<section class="engagements-section">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Nos engagements écologiques</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="engagement-item">
                    <div class="engagement-icon">
                        <i class="fas fa-tree"></i>
                    </div>
                    <h5>1 arbre planté</h5>
                    <p class="text-muted">Pour chaque trajet de plus de 100km partagé sur notre plateforme</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="engagement-item">
                    <div class="engagement-icon">
                        <i class="fas fa-charging-station"></i>
                    </div>
                    <h5>Véhicules électriques</h5>
                    <p class="text-muted">Bonus crédits pour les conducteurs de voitures électriques</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="engagement-item">
                    <div class="engagement-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <h5>Impact carbone</h5>
                    <p class="text-muted">Calculateur d'émissions CO₂ évitées affiché sur chaque trajet</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 5: STATISTIQUES -->
<section class="stats-section">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">EcoRide en chiffres</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number">15,420</span>
                    <div class="stat-label">Utilisateurs actifs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number">2,845</span>
                    <div class="stat-label">Trajets par semaine</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number">78</span>
                    <div class="stat-label">Tonnes CO₂ évitées</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <span class="stat-number">4.8/5</span>
                    <div class="stat-label">Note moyenne</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 6: COMMENT ÇA MARCHE -->
<section class="how-it-works-section">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Comment ça marche ?</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h5>Inscrivez-vous</h5>
                    <p class="text-muted">Créez votre profil en 2 minutes</p>
                    <div class="step-arrow d-none d-md-block">→</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h5>Recherchez</h5>
                    <p class="text-muted">Trouvez le trajet qui vous convient</p>
                    <div class="step-arrow d-none d-md-block">→</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h5>Réservez</h5>
                    <p class="text-muted">Confirmez votre place en un clic</p>
                    <div class="step-arrow d-none d-md-block">→</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">4</div>
                    <h5>Voyagez !</h5>
                    <p class="text-muted">Profitez de votre trajet écologique</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 7: NEWSLETTER -->
<section class="newsletter-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="newsletter-box">
                    <h3 class="mb-4">Rejoignez la communauté EcoRide</h3>
                    <p class="mb-4">Recevez nos conseils éco-mobilité et les meilleures offres de covoiturage</p>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <input type="email" class="form-control form-control-lg" placeholder="Votre adresse email">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-light btn-lg w-100">
                                S'abonner
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Page d’accueil uniquement : contenu HTML spécifique sans balises globales
// Le formulaire de recherche redirige vers la page de résultats
// La variable $page_title est définie avant l’inclusion du header

?>