mod data;
mod vectorizer;
mod model;

use anyhow::Result;
use serde::{Deserialize, Serialize};
use std::sync::Arc;
use tokio::sync::RwLock;
use warp::{http::StatusCode, reject, reply, Filter, Rejection, Reply};

// Types pour l'API
#[derive(Debug, Deserialize)]
struct ClassifyRequest {
    text: String,
}

#[derive(Debug, Serialize)]
struct ClassifyResponse {
    is_spam: bool,
    spam_probability: f64,
    confidence: String,
}

#[derive(Debug, Serialize)]
struct HealthResponse {
    status: String,
    model_loaded: bool,
}

#[derive(Debug, Serialize)]
struct ErrorResponse {
    error: String,
}

// Structure pour partager le modèle entre les requêtes
type SharedClassifier = Arc<RwLock<Option<model::SpamClassifier>>>;

// Gestionnaire pour classifier un email
async fn classify_handler(
    request: ClassifyRequest,
    classifier: SharedClassifier,
) -> Result<impl Reply, Rejection> {
    let classifier_guard = classifier.read().await;
    
    match classifier_guard.as_ref() {
        Some(classifier) => {
            let (prediction, spam_prob) = classifier.predict_single(&request.text);
            
            let confidence = if spam_prob > 0.8 {
                "high".to_string()
            } else if spam_prob > 0.6 {
                "medium".to_string()
            } else {
                "low".to_string()
            };
            
            let response = ClassifyResponse {
                is_spam: prediction == 1,
                spam_probability: spam_prob,
                confidence,
            };
            
            Ok(reply::with_status(reply::json(&response), StatusCode::OK))
        }
        None => {
            let error = ErrorResponse {
                error: "Model not loaded".to_string(),
            };
            Ok(reply::with_status(reply::json(&error), StatusCode::SERVICE_UNAVAILABLE))
        }
    }
}

// Gestionnaire pour le health check
async fn health_handler(classifier: SharedClassifier) -> Result<impl Reply, Rejection> {
    let classifier_guard = classifier.read().await;
    let model_loaded = classifier_guard.is_some();
    
    let response = HealthResponse {
        status: "ok".to_string(),
        model_loaded,
    };
    
    Ok(reply::with_status(reply::json(&response), StatusCode::OK))
}

// Gestionnaire d'erreurs
async fn handle_rejection(err: Rejection) -> Result<impl Reply, std::convert::Infallible> {
    let error_response = if err.is_not_found() {
        ErrorResponse {
            error: "Not found".to_string(),
        }
    } else if let Some(_) = err.find::<warp::filters::body::BodyDeserializeError>() {
        ErrorResponse {
            error: "Invalid JSON body".to_string(),
        }
    } else {
        ErrorResponse {
            error: "Internal server error".to_string(),
        }
    };
    
    Ok(reply::with_status(reply::json(&error_response), StatusCode::BAD_REQUEST))
}

// Charge le modèle depuis un fichier (à implémenter)
async fn load_model() -> Result<model::SpamClassifier> {
    // Pour l'instant, on va entraîner un modèle simple
    // Plus tard, on chargera depuis un fichier binaire
    println!("🚀 Chargement/Entraînement du modèle...");
    
    let processor = data::DataProcessor::new()?;
    
    // Essaie de charger les données
    let train_emails = match processor.read_csv("/data/train.csv") {
        Ok(emails) => {
            println!("✅ {} emails d'entraînement chargés depuis /data/train.csv", emails.len());
            emails
        }
        Err(_) => {
            println!("⚠️  Échec chargement /data/train.csv, création d'un dataset minimal...");
            create_minimal_dataset()
        }
    };
    
    if train_emails.len() < 10 {
        anyhow::bail!("❌ Dataset trop petit ({}), minimum 10 emails requis", train_emails.len());
    }
    
    // Construction du vocabulaire et vectorisation
    let vocabulary = processor.build_vocabulary(&train_emails, 2, 1000);
    let mut vectorizer = vectorizer::TfIdfVectorizer::new(vocabulary);
    
    vectorizer.fit(&train_emails);
    let train_features = vectorizer.transform(&train_emails);
    let train_labels = vectorizer.extract_labels(&train_emails);
    
    // Entraînement
    let mut classifier = model::SpamClassifier::new(vectorizer);
    classifier.train(train_features, train_labels)?;
    
    println!("✅ Modèle entraîné et prêt!");
    Ok(classifier)
}

// Crée un dataset minimal pour les tests
fn create_minimal_dataset() -> Vec<data::Email> {
    vec![
        data::Email { text: "free money win now click here urgent".to_string(), label: 1 },
        data::Email { text: "viagra cheap pills online pharmacy".to_string(), label: 1 },
        data::Email { text: "congratulations winner lottery million dollars".to_string(), label: 1 },
        data::Email { text: "urgent nigerian prince money transfer".to_string(), label: 1 },
        data::Email { text: "buy now limited time offer discount".to_string(), label: 1 },
        data::Email { text: "hello how are you today meeting tomorrow".to_string(), label: 0 },
        data::Email { text: "thanks for the document will review".to_string(), label: 0 },
        data::Email { text: "reminder about project deadline next week".to_string(), label: 0 },
        data::Email { text: "lunch meeting scheduled for friday noon".to_string(), label: 0 },
        data::Email { text: "please find attached report quarterly results".to_string(), label: 0 },
    ]
}

#[tokio::main]
async fn main() -> Result<()> {
    println!("🚀 Démarrage du serveur API Spam Classifier");
    println!("📡 Port: 8081");
    
    // Initialise le classificateur partagé
    let classifier: SharedClassifier = Arc::new(RwLock::new(None));
    
    // Charge le modèle en arrière-plan
    let classifier_clone = classifier.clone();
    tokio::spawn(async move {
        match load_model().await {
            Ok(model) => {
                let mut guard = classifier_clone.write().await;
                *guard = Some(model);
                println!("✅ Modèle chargé avec succès!");
            }
            Err(e) => {
                eprintln!("❌ Erreur chargement modèle: {}", e);
            }
        }
    });
    
    // Routes
    let health = warp::path("health")
        .and(warp::get())
        .and(with_classifier(classifier.clone()))
        .and_then(health_handler);
    
    let classify = warp::path("classify")
        .and(warp::post())
        .and(warp::body::json())
        .and(with_classifier(classifier.clone()))
        .and_then(classify_handler);
    
    let routes = health
        .or(classify)
        .with(warp::cors().allow_any_origin())
        .recover(handle_rejection);
    
    println!("🌐 Serveur démarré sur http://0.0.0.0:8081");
    println!("📋 Routes disponibles:");
    println!("   GET  /health   - Health check");
    println!("   POST /classify - Classifier un email");
    
    warp::serve(routes)
        .run(([0, 0, 0, 0], 8081))
        .await;
    
    Ok(())
}

// Helper pour injecter le classificateur dans les handlers
fn with_classifier(
    classifier: SharedClassifier,
) -> impl Filter<Extract = (SharedClassifier,), Error = std::convert::Infallible> + Clone {
    warp::any().map(move || classifier.clone())
}