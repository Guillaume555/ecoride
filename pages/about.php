<?php
/*
================================================
FICHIER: pages/about.php - À propos EcoRide (VERSION REFONTE)
Développé par: Guillaume
Description: Présentation professionnelle de l'entreprise EcoRide
================================================
*/

// Configuration de la page
$page_title = "EcoRide - À propos";
$extra_css = ['about.css']; // CSS spécifique uniquement
$extra_js = []; //Js spécifique a la page
?>

<!-- HERO SECTION -->
<section class="about-hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="about-hero-title">À propos d'EcoRide</h1>
                <p class="about-hero-subtitle">
                    La startup française qui révolutionne le covoiturage écologique
                </p>
                <div class="about-hero-stats">
                    <div class="row">
                        <div class="col-4">
                            <div class="hero-stat">
                                <span class="stat-number">15k+</span>
                                <span class="stat-label">Utilisateurs</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="hero-stat">
                                <span class="stat-number">78T</span>
                                <span class="stat-label">CO₂ évitées</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="hero-stat">
                                <span class="stat-number">4.8/5</span>
                                <span class="stat-label">Satisfaction</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MISSION SECTION -->
<section class="about-mission">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="mission-content">
                    <h2 class="section-title">Notre Mission</h2>
                    <p class="mission-text">
                        <strong>EcoRide est née d'une conviction simple :</strong> nous pouvons réduire l'impact
                        environnemental des déplacements tout en créant du lien social et en
                        économisant de l'argent.
                    </p>
                    <p class="mission-text">
                        Fondée en France en 2025, notre plateforme encourage le covoiturage en mettant
                        l'accent sur les <strong>véhicules écologiques</strong> et une communauté bienveillante.
                    </p>
                    <div class="mission-highlight">
                        <i class="fas fa-leaf"></i>
                        <span>Chaque trajet partagé compte pour la planète</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mission-visual">
                    <div class="eco-circle">
                        <i class="fas fa-car"></i>
                        <span class="eco-text">Mobilité<br>Durable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VALEURS SECTION -->
<section class="about-values">
    <div class="container">
        <h2 class="section-title text-center mb-5">Nos Valeurs</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="value-card">
                    <div class="value-icon ecology">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h4 class="value-title">Écologie</h4>
                    <p class="value-description">
                        Chaque trajet partagé réduit les émissions de CO₂.
                        Nous privilégions les véhicules électriques et hybrides.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="value-icon solidarity">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4 class="value-title">Solidarité</h4>
                    <p class="value-description">
                        Créer du lien social et rendre la mobilité accessible
                        à tous grâce au partage des frais.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="value-icon trust">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="value-title">Confiance</h4>
                    <p class="value-description">
                        Profils vérifiés, système d'évaluation et
                        support client pour des trajets en toute sérénité.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ÉQUIPE SECTION -->
<section class="about-team">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="section-title">L'Équipe EcoRide</h2>
                <p class="team-intro">Une équipe passionnée de mobilité durable et d'innovation</p>

                <div class="row g-4 mt-4">
                    <div class="col-md-6">
                        <div class="team-member">
                            <div class="member-avatar">J</div>
                            <h5 class="member-name">José Martinez</h5>
                            <p class="member-role">Directeur Technique & Fondateur</p>
                            <p class="member-bio">
                                Passionné de mobilité durable et d'innovation technologique.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="team-member">
                            <div class="member-avatar">G</div>
                            <h5 class="member-name">Guillaume</h5>
                            <p class="member-role">Développeur Web & Web Mobile</p>
                            <p class="member-bio">
                                Spécialisé dans le développement d'applications web modernes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="about-cta">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="cta-title">Rejoignez la révolution EcoRide</h2>
                <p class="cta-subtitle">
                    Ensemble, construisons une mobilité plus verte et plus solidaire
                </p>
                <div class="cta-buttons">
                    <a href="?page=register" class="btn btn-cta-primary">
                        <i class="fas fa-user-plus"></i> S'inscrire gratuitement
                    </a>
                    <a href="?page=search" class="btn btn-cta-secondary">
                        <i class="fas fa-search"></i> Rechercher un trajet
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>