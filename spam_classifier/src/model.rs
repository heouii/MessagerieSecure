use crate::vectorizer::TfIdfVectorizer;
use ndarray::{Array1, Array2};
use anyhow::Result;

pub struct SpamClassifier {
    pub vectorizer: TfIdfVectorizer,
    pub trained: bool,
}

impl SpamClassifier {
    pub fn new(vectorizer: TfIdfVectorizer) -> Self {
        Self { 
            vectorizer,
            trained: false,
        }
    }
    
    pub fn train(&mut self, _features: Array2<f64>, _labels: Array1<usize>) -> Result<()> {
        // Simulation d'entraînement pour l'instant
        println!("🤖 Modèle simulé entraîné");
        self.trained = true;
        Ok(())
    }
    
    pub fn predict(&self, _features: &Array2<f64>) -> Array1<usize> {
        // Prédiction simulée : toujours "ham" pour l'instant
        Array1::zeros(_features.nrows())
    }
    
    pub fn predict_proba(&self, features: &Array2<f64>) -> Array2<f64> {
        // Probabilités simulées
        let mut probs = Array2::zeros((features.nrows(), 2));
        for i in 0..features.nrows() {
            probs[[i, 0]] = 0.8; // Probabilité ham
            probs[[i, 1]] = 0.2; // Probabilité spam
        }
        probs
    }
    
    pub fn predict_single(&self, text: &str) -> (usize, f64) {
        // Classification basique basée sur des mots-clés
        let spam_keywords = ["free", "money", "win", "urgent", "click", "now", "viagra", "lottery", "prince"];
        let text_lower = text.to_lowercase();
        
        let spam_count = spam_keywords.iter()
            .filter(|&&keyword| text_lower.contains(keyword))
            .count();
        
        if spam_count >= 2 {
            (1, 0.8) // Spam avec 80% de confiance
        } else if spam_count == 1 {
            (1, 0.6) // Spam avec 60% de confiance
        } else {
            (0, 0.2) // Ham avec 20% de chance d'être spam
        }
    }
}