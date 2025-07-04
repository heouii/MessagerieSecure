use crate::data::{Email, Vocabulary};
use ndarray::{Array1, Array2};
use std::collections::HashMap;

pub struct TfIdfVectorizer {
    vocabulary: Vocabulary,
    idf_values: Array1<f64>,
}

impl TfIdfVectorizer {
    pub fn new(vocabulary: Vocabulary) -> Self {
        let idf_values = Array1::zeros(vocabulary.size());
        Self { vocabulary, idf_values }
    }
    
    pub fn fit(&mut self, emails: &[Email]) {
        let n_docs = emails.len() as f64;
        let mut doc_freq = vec![0; self.vocabulary.size()];
        
        for email in emails {
            let mut seen_words = vec![false; self.vocabulary.size()];
            
            for word in email.text.split_whitespace() {
                if let Some(idx) = self.vocabulary.get_index(word) {
                    if !seen_words[idx] {
                        doc_freq[idx] += 1;
                        seen_words[idx] = true;
                    }
                }
            }
        }
        
        for (i, &freq) in doc_freq.iter().enumerate() {
            if freq > 0 {
                self.idf_values[i] = (n_docs / freq as f64).ln();
            } else {
                self.idf_values[i] = 0.0;
            }
        }
    }
    
    pub fn transform(&self, emails: &[Email]) -> Array2<f64> {
        let n_docs = emails.len();
        let n_features = self.vocabulary.size();
        let mut matrix = Array2::zeros((n_docs, n_features));
        
        for (doc_idx, email) in emails.iter().enumerate() {
            let tf_vector = self.calculate_tf(&email.text);
            
            for (word_idx, &tf) in tf_vector.iter().enumerate() {
                if tf > 0.0 {
                    matrix[[doc_idx, word_idx]] = tf * self.idf_values[word_idx];
                }
            }
        }
        
        matrix
    }
    
    fn calculate_tf(&self, text: &str) -> Vec<f64> {
        let mut tf_vector = vec![0.0; self.vocabulary.size()];
        let mut word_count = HashMap::new();
        let mut total_words = 0;
        
        for word in text.split_whitespace() {
            if self.vocabulary.get_index(word).is_some() {
                *word_count.entry(word.to_string()).or_insert(0) += 1;
                total_words += 1;
            }
        }
        
        if total_words > 0 {
            for (word, count) in word_count {
                if let Some(idx) = self.vocabulary.get_index(&word) {
                    tf_vector[idx] = count as f64 / total_words as f64;
                }
            }
        }
        
        tf_vector
    }
    
    pub fn transform_single(&self, text: &str) -> Array1<f64> {
        let tf_vector = self.calculate_tf(text);
        let mut tfidf_vector = Array1::zeros(self.vocabulary.size());
        
        for (i, &tf) in tf_vector.iter().enumerate() {
            if tf > 0.0 {
                tfidf_vector[i] = tf * self.idf_values[i];
            }
        }
        
        tfidf_vector
    }
    
    pub fn extract_labels(&self, emails: &[Email]) -> Array1<usize> {
        Array1::from(emails.iter().map(|e| e.label).collect::<Vec<_>>())
    }
}