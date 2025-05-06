<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\CompetenceController;
use App\Http\Controllers\GalerieController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\ValidationProfessionnelController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes des services publiques
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/search', [ServiceController::class, 'search']);
Route::get('/services/{service}', [ServiceController::class, 'show']);
Route::get('/services/category/{categorie}', [ServiceController::class, 'getByCategory']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Services
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    Route::put('/services/{service}/toggle-availability', [ServiceController::class, 'toggleAvailability']);
    Route::get('/users/{user}/services', [ServiceController::class, 'getByUser']);

    // Categories routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    // Demandes routes
    Route::get('/demandes', [DemandeController::class, 'index']);
    Route::post('/demandes', [DemandeController::class, 'store']);
    Route::get('/demandes/{demande}', [DemandeController::class, 'show']);
    Route::put('/demandes/{demande}', [DemandeController::class, 'update']);
    Route::get('/my-demandes', [DemandeController::class, 'getMyDemandes']);
    Route::get('/demandes-reçues', [DemandeController::class, 'getDemandesRecues']);
    Route::post('/demandes/{demande}/paiement', [PaiementController::class, 'initierPaiementDemande']);

    // Transactions routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions/{transaction}/validate', [TransactionController::class, 'validateTransaction']);

    // Messages routes
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::get('/conversations', [MessageController::class, 'getConversations']);
    Route::get('/conversations/{user}', [MessageController::class, 'getConversationWith']);
    Route::put('/messages/mark-all-read', [MessageController::class, 'marquerToutLu']);

    // Favoris routes
    Route::prefix('favoris')->group(function () {
        Route::get('/', [FavoriController::class, 'index']);
        Route::post('/{service}', [FavoriController::class, 'store']);
        Route::delete('/{service}', [FavoriController::class, 'destroy']);
    });

    // Routes pour la gestion du profil
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/documents', [ProfileController::class, 'updateDocuments']);
    Route::post('/profile/competences', [ProfileController::class, 'updateCompetences']);

    // Routes pour la gestion des zones d'intervention
    Route::put('/services/{service}/zones', [ZoneController::class, 'updateZones']);
    Route::post('/services/{service}/zones', [ZoneController::class, 'addZone']);
    Route::delete('/services/{service}/zones', [ZoneController::class, 'removeZone']);

    // Routes pour la gestion des compétences
    Route::put('/services/{service}/competences', [CompetenceController::class, 'updateCompetences']);
    Route::post('/services/{service}/competences', [CompetenceController::class, 'addCompetence']);
    Route::delete('/services/{service}/competences', [CompetenceController::class, 'removeCompetence']);

    // Routes pour la gestion de la galerie
    Route::post('/services/{service}/galerie', [GalerieController::class, 'uploadPhoto']);
    Route::delete('/services/{service}/galerie/{photo}', [GalerieController::class, 'removePhoto']);
    Route::put('/services/{service}/galerie/reorder', [GalerieController::class, 'reorderPhotos']);

    // Routes pour les statistiques
    Route::prefix('statistics')->group(function () {
        Route::get('/global', [StatistiqueController::class, 'global']);
        Route::get('/performance', [StatistiqueController::class, 'performance']);
        Route::get('/financial', [StatistiqueController::class, 'financial']);
        Route::get('/professionnel', [StatistiqueController::class, 'professionnel']);
    });

    // Routes pour l'administration
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Routes pour les statistiques
        Route::prefix('statistiques')->group(function () {
            Route::get('/', [StatistiqueController::class, 'global']);
            Route::get('/performances', [StatistiqueController::class, 'performance']);
            Route::get('/financieres', [StatistiqueController::class, 'financial']);
            Route::get('/utilisateurs', [StatistiqueController::class, 'utilisateurs']);
            Route::get('/messages', [StatistiqueController::class, 'messages']);
            Route::get('/competences', [StatistiqueController::class, 'competences']);
        });

        // Routes pour la validation des documents
        Route::prefix('documents')->group(function () {
            Route::put('/{id}/valider', [ValidationProfessionnelController::class, 'validerDocument']);
        });
    });

    // Routes pour les professionnels
    Route::prefix('professionnel')->group(function () {
        // Documents
        Route::post('/documents', [ValidationProfessionnelController::class, 'soumettreDocument']);
        Route::get('/documents', [ValidationProfessionnelController::class, 'listerDocuments']);
        
        // Compétences
        Route::post('/competences', [ValidationProfessionnelController::class, 'soumettreCompetences']);
        Route::get('/competences', [ValidationProfessionnelController::class, 'listerCompetences']);
        
        // Badges
        Route::get('/badges', [ValidationProfessionnelController::class, 'listerBadges']);
        
        // Statistiques
        Route::get('/statistiques', [StatistiqueController::class, 'professionnel']);
    });

    // Routes pour les paiements
    Route::prefix('paiements')->group(function () {
        Route::post('/', [PaiementController::class, 'store']);
        Route::get('/', [PaiementController::class, 'index']);
        Route::get('/{id}', [PaiementController::class, 'show']);
        Route::put('/{id}/confirmer', [PaiementController::class, 'confirmer']);
        Route::post('/{id}/confirmer', [PaiementController::class, 'confirmer']);
        Route::put('/{id}/annuler', [PaiementController::class, 'annuler']);
        Route::delete('/{id}', [PaiementController::class, 'destroy']);
    });

    // Routes pour les notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::get('/non-lues', [NotificationController::class, 'nonLues']);
        Route::get('/non-lues/count', [NotificationController::class, 'nonLues']);
        Route::get('/statistiques', [NotificationController::class, 'getStatistiques']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}/lire', [NotificationController::class, 'marquerCommeLu']);
        Route::put('/{id}/lu', [NotificationController::class, 'marquerCommeLu']);
        Route::put('/{id}/read', [NotificationController::class, 'marquerCommeLu']);
        Route::put('/lire-toutes', [NotificationController::class, 'marquerToutesCommeLues']);
        Route::put('/marquer-tout-lu', [NotificationController::class, 'marquerToutesCommeLues']);
        Route::put('/read-all', [NotificationController::class, 'marquerToutesCommeLues']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'destroyAll']);
    });
});
