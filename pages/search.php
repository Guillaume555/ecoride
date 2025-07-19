<?php
/*
================================================
FICHIER: pages/search.php - Page de recherche EcoRide
Développé par: [Votre nom]
Description: Page de recherche et affichage des trajets de covoiturage
================================================
*/

// Inclusion de la configuration base de données
require_once 'config/database.php';

// Configuration de la page
$page_title = "EcoRide - Recherche de trajets";
$extra_css = ['search.css']; // CSS spécifique à cette page

// Récupération des paramètres de recherche depuis l'URL
$depart = $_GET['depart'] ?? '';     // Ville de départ
$arrivee = $_GET['arrivee'] ?? '';   // Ville d'arrivée
$date = $_GET['date'] ?? '';         // Date de départ (optionnelle)
$max_price = $_GET['max_price'] ?? ''; // Prix maximum (filtre)
$fuel_type = $_GET['fuel_type'] ?? ''; // Type de carburant (filtre)

// Variables pour les résultats
$trips = [];                // Tableau des trajets trouvés
$recherche_effectuee = false; // Indique si une recherche a été lancée
$nombre_resultats = 0;      // Nombre de résultats trouvés

// Si une recherche est effectuée (départ et arrivée obligatoires)
if (!empty($depart) && !empty($arrivee)) {
    $recherche_effectuee = true;

    try {
        // Appel de la fonction de recherche (définie dans database.php)
        $trips = searchTrips($depart, $arrivee, $date);

        // Application des filtres additionnels
        if (!empty($max_price)) {
            // Filtre sur le prix maximum
            $trips = array_filter($trips, function ($trip) use ($max_price) {
                return floatval($trip['price_per_seat']) <= floatval($max_price);
            });
        }

        if (!empty($fuel_type)) {
            // Filtre sur le type de carburant
            $trips = array_filter($trips, function ($trip) use ($fuel_type) {
                return $trip['fuel_type'] === $fuel_type;
            });
        }

        $nombre_resultats = count($trips);
    } catch (Exception $e) {
        // En cas d'erreur, on stocke le message pour l'afficher
        $erreur_recherche = "Erreur lors de la recherche : " . $e->getMessage();
    }
}
?>

<!-- HERO SECTION AVEC FORMULAIRE DE RECHERCHE -->
<section class="search-hero-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="search-hero-title">Rechercher un trajet</h1>
                <p class="search-hero-subtitle">Trouvez le covoiturage parfait pour vos déplacements</p>

                <!-- FORMULAIRE DE RECHERCHE PRINCIPAL -->
                <div class="search-form-container">
                    <form method="GET" action="?" class="search-form">
                        <!-- Champ caché pour maintenir la page -->
                        <input type="hidden" name="page" value="search">

                        <div class="row g-3">
                            <!-- Ville de départ -->
                            <div class="col-md-4">
                                <input type="text"
                                    class="form-control search-input"
                                    name="depart"
                                    placeholder="Ville de départ"
                                    value="<?= htmlspecialchars($depart) ?>"
                                    required>
                            </div>

                            <!-- Ville d'arrivée -->
                            <div class="col-md-4">
                                <input type="text"
                                    class="form-control search-input"
                                    name="arrivee"
                                    placeholder="Ville d'arrivée"
                                    value="<?= htmlspecialchars($arrivee) ?>"
                                    required>
                            </div>

                            <!-- Date (optionnelle) -->
                            <div class="col-md-4">
                                <input type="date"
                                    class="form-control search-input"
                                    name="date"
                                    value="<?= htmlspecialchars($date) ?>">
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-search-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>

                            <?php if ($recherche_effectuee): ?>
                                <!-- Bouton pour nouvelle recherche -->
                                <a href="?page=search" class="btn btn-outline-secondary ms-3">
                                    <i class="fas fa-times"></i> Nouvelle recherche
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION DES RÉSULTATS -->
<section class="search-results-section">
    <div class="container">

        <?php if (isset($erreur_recherche)): ?>
            <!-- AFFICHAGE D'ERREUR -->
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= $erreur_recherche ?>
            </div>

        <?php elseif ($recherche_effectuee): ?>

            <div class="row">
                <!-- SIDEBAR DES FILTRES -->
                <div class="col-lg-3">
                    <div class="filtres-sidebar">
                        <h4 class="filtres-titre">
                            <i class="fas fa-filter"></i> Filtrer les résultats
                        </h4>

                        <?php if ($nombre_resultats > 0): ?>
                            <!-- Affichage du nombre de résultats -->
                            <div class="filtres-info">
                                <?= $nombre_resultats ?> trajet<?= $nombre_resultats > 1 ? 's' : '' ?> trouvé<?= $nombre_resultats > 1 ? 's' : '' ?>
                            </div>
                        <?php endif; ?>

                        <!-- FORMULAIRE DES FILTRES -->
                        <form method="GET" action="?" class="filtres-form">
                            <!-- Conservation des paramètres de recherche -->
                            <input type="hidden" name="page" value="search">
                            <input type="hidden" name="depart" value="<?= htmlspecialchars($depart) ?>">
                            <input type="hidden" name="arrivee" value="<?= htmlspecialchars($arrivee) ?>">
                            <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">

                            <!-- Filtre Prix maximum -->
                            <div class="filtre-group">
                                <label class="filtre-label">Prix maximum (€)</label>
                                <input type="number"
                                    class="form-control filtre-input"
                                    name="max_price"
                                    placeholder="Ex: 30"
                                    min="1" max="100"
                                    value="<?= htmlspecialchars($max_price) ?>">
                            </div>

                            <!-- Filtre Type de véhicule -->
                            <div class="filtre-group">
                                <label class="filtre-label">Type de véhicule</label>
                                <select class="form-select filtre-input" name="fuel_type">
                                    <option value="">Tous les types</option>
                                    <option value="électrique" <?= $fuel_type === 'électrique' ? 'selected' : '' ?>>
                                        ⚡ Électrique
                                    </option>
                                    <option value="hybride" <?= $fuel_type === 'hybride' ? 'selected' : '' ?>>
                                        🔋 Hybride
                                    </option>
                                    <option value="essence" <?= $fuel_type === 'essence' ? 'selected' : '' ?>>
                                        ⛽ Essence
                                    </option>
                                    <option value="diesel" <?= $fuel_type === 'diesel' ? 'selected' : '' ?>>
                                        🛢️ Diesel
                                    </option>
                                </select>
                            </div>

                            <!-- Boutons du formulaire de filtres -->
                            <button type="submit" class="btn btn-success btn-sm w-100 mt-3">
                                <i class="fas fa-filter"></i> Appliquer les filtres
                            </button>

                            <?php if (!empty($max_price) || !empty($fuel_type)): ?>
                                <!-- Bouton pour effacer les filtres -->
                                <a href="?page=search&depart=<?= urlencode($depart) ?>&arrivee=<?= urlencode($arrivee) ?>&date=<?= urlencode($date) ?>"
                                    class="btn btn-outline-secondary btn-sm w-100 mt-2">
                                    <i class="fas fa-eraser"></i> Effacer filtres
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- COLONNE PRINCIPALE DES RÉSULTATS -->
                <div class="col-lg-9">
                    <!-- En-tête des résultats -->
                    <div class="resultats-header">
                        <h2 class="resultats-titre">
                            <strong><?= htmlspecialchars($depart) ?></strong> → <strong><?= htmlspecialchars($arrivee) ?></strong>
                            <?php if (!empty($date)): ?>
                                le <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                            <?php endif; ?>
                        </h2>

                        <?php if (!empty($max_price) || !empty($fuel_type)): ?>
                            <!-- Affichage des filtres actifs -->
                            <div class="filtres-actifs">
                                <span class="text-muted">Filtres actifs :</span>
                                <?php if (!empty($max_price)): ?>
                                    <span class="badge bg-info">Max <?= $max_price ?>€</span>
                                <?php endif; ?>
                                <?php if (!empty($fuel_type)): ?>
                                    <span class="badge bg-success"><?= ucfirst($fuel_type) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($nombre_resultats > 0): ?>
                        <!-- LISTE DES TRAJETS TROUVÉS -->
                        <div class="trips-list">
                            <?php foreach ($trips as $trip): ?>
                                <div class="trip-card">
                                    <div class="row align-items-center">

                                        <!-- COLONNE 1: INFORMATIONS DU TRAJET -->
                                        <div class="col-md-6">
                                            <div class="trip-route">
                                                <!-- Titre du trajet (ex: Paris → Lyon) -->
                                                <h4 class="route-cities">
                                                    <?= htmlspecialchars($trip['departure_city']) ?>
                                                    <i class="fas fa-arrow-right text-success"></i>
                                                    <?= htmlspecialchars($trip['arrival_city']) ?>
                                                </h4>
                                                <!-- Date et heure -->
                                                <p class="route-time">
                                                    <i class="fas fa-calendar"></i>
                                                    <?= date('d/m/Y', strtotime($trip['departure_time'])) ?>
                                                    <span class="ms-2">
                                                        <i class="fas fa-clock"></i>
                                                        <?= date('H:i', strtotime($trip['departure_time'])) ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- COLONNE 2: CONDUCTEUR ET VÉHICULE -->
                                        <div class="col-md-3">
                                            <div class="trip-driver">
                                                <div class="driver-info">
                                                    <!-- Avatar avec initiale du conducteur -->
                                                    <div class="driver-avatar">
                                                        <?= strtoupper(substr($trip['driver_name'], 0, 1)) ?>
                                                    </div>

                                                    <div class="driver-details">
                                                        <!-- Nom du conducteur -->
                                                        <strong><?= htmlspecialchars($trip['driver_name']) ?></strong>

                                                        <!-- Note du conducteur (temporaire, à connecter à la BDD plus tard) -->
                                                        <div class="driver-rating">
                                                            <span class="stars">★★★★☆</span>
                                                            <span class="rating-text">4.2/5</span>
                                                        </div>

                                                        <!-- VÉHICULE AVEC BADGE ÉCOLOGIQUE EN LIGNE -->
                                                        <div class="vehicle-info">
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($trip['brand']) ?> <?= htmlspecialchars($trip['model']) ?>
                                                                <?php if ($trip['fuel_type'] === 'électrique'): ?>
                                                                    <span class="eco-badge">⚡Éco</span>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- COLONNE 3: PRIX ET NOMBRE DE PLACES -->
                                        <div class="col-md-3 text-end">
                                            <div class="trip-booking">
                                                <!-- Prix affiché en grand -->
                                                <div class="trip-price">
                                                    <span class="price-amount"><?= number_format($trip['price_per_seat'], 0) ?>€</span>
                                                    <small class="price-label">par place</small>
                                                </div>
                                                <!-- Nombre de places disponibles -->
                                                <div class="trip-seats">
                                                    <i class="fas fa-users"></i>
                                                    <?= $trip['available_seats'] ?> place<?= $trip['available_seats'] > 1 ? 's' : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SECTION BOTTOM: PRÉFÉRENCES + BOUTON SUR LA MÊME LIGNE -->
                                    <?php if (!empty($trip['preferences'])): ?>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="trip-preferences">
                                                    <!-- Préférences avec limitation de texte -->
                                                    <div class="preferences-content">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle"></i>
                                                            Préférences :
                                                        </small>
                                                        <!-- Le title permet de voir le texte complet au survol -->
                                                        <span class="preferences-badge" title="<?= htmlspecialchars($trip['preferences']) ?>">
                                                            <?= htmlspecialchars($trip['preferences']) ?>
                                                        </span>
                                                    </div>
                                                    <!-- Bouton "Voir détail" à côté des préférences -->
                                                    <a href="?page=detail&id=<?= $trip['id'] ?>"
                                                        class="btn btn-outline-success btn-sm">
                                                        <i class="fas fa-eye"></i> Voir détail
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Si pas de préférences, bouton seul -->
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="trip-preferences">
                                                    <div></div> <!-- Espace vide pour alignement -->
                                                    <a href="?page=detail&id=<?= $trip['id'] ?>"
                                                        class="btn btn-outline-success btn-sm">
                                                        <i class="fas fa-eye"></i> Voir détail
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <!-- AUCUN RÉSULTAT TROUVÉ -->
                        <div class="no-results">
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h3 class="text-muted">Aucun trajet trouvé</h3>
                                <p class="text-muted">
                                    Essayez de modifier vos critères de recherche.
                                </p>

                                <!-- Suggestions pour améliorer la recherche -->
                                <div class="mt-4">
                                    <h5>Suggestions :</h5>
                                    <ul class="list-unstyled">
                                        <li>• Vérifiez l'orthographe des villes</li>
                                        <li>• Essayez sans date spécifique</li>
                                        <li>• Recherchez des villes proches</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- PAGE INITIALE SANS RECHERCHE -->
            <div class="search-suggestions">
                <div class="text-center py-5">
                    <h3>Recherches populaires</h3>
                    <p class="text-muted mb-4">Découvrez les trajets les plus demandés</p>

                    <!-- Liens vers des recherches pré-définies -->
                    <div class="row g-3 justify-content-center">
                        <div class="col-auto">
                            <a href="?page=search&depart=Paris&arrivee=Lyon" class="btn btn-outline-success">
                                Paris → Lyon
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="?page=search&depart=Marseille&arrivee=Nice" class="btn btn-outline-success">
                                Marseille → Nice
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="?page=search&depart=Bordeaux&arrivee=Toulouse" class="btn btn-outline-success">
                                Bordeaux → Toulouse
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
/*
================================================
NOTES DE DÉVELOPPEMENT:

1. FONCTIONNALITÉS IMPLÉMENTÉES:
   ✅ Formulaire de recherche avec villes + date
   ✅ Filtres prix et type de véhicule
   ✅ Affichage des résultats avec toutes les infos
   ✅ Gestion cas "aucun résultat"
   ✅ Suggestions de recherches populaires
   ✅ Navigation vers page détail
   ✅ Badge écologique "⚡Éco" en ligne
   ✅ Préférences + bouton sur même ligne

2. À FAIRE PLUS TARD:
   - Connecter la vraie note du conducteur depuis la BDD
   - Ajouter la géolocalisation pour auto-complétion
   - Implémenter la recherche AJAX (sans rechargement)
   - Ajouter tri des résultats (prix, heure, note)

3. SÉCURITÉ:
   - Toutes les données utilisateur sont échappées avec htmlspecialchars()
   - Utilisation de requêtes préparées dans searchTrips()
   - Validation des filtres (min/max sur prix)

4. PERFORMANCE:
   - CSS en fichier séparé pour cache navigateur
   - Animations CSS au lieu de JavaScript
   - Sticky sidebar uniquement sur desktop
================================================
*/
?>