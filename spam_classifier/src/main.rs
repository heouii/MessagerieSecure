use csv::ReaderBuilder;
use linfa::prelude::*;
use linfa_logistic::LogisticRegression;
use ndarray::{Array2, Array1};
use regex::Regex;
use std::{error::Error, fs};

/// Lecture brute d'un CSV avec détourage UTF-8
fn read_csv(path: &str) -> Result<Vec<(String, usize)>, Box<dyn Error>> {
    let raw = fs::read(path)?;
    let content = String::from_utf8_lossy(&raw);
    let mut rdr = ReaderBuilder::new().has_headers(false).from_reader(content.as_bytes());
    let re = Regex::new(r#"[^a-zA-Z0-9\s]"#)?;
    let mut data = Vec::new();
    for result in rdr.records() {
        let record = result?;
        let label = if &record[0] == "spam" { 1 } else { 0 };
        let text = re.replace_all(&record[1], "").to_lowercase();
        data.push((text, label));
    }
    Ok(data)
}

/// Construit vocabulaire à partir d'un jeu de textes
fn build_vocab(corpus: &[(String, usize)]) -> Vec<String> {
    let mut freq = std::collections::HashMap::new();
    for (text, _) in corpus {
        for w in text.split_whitespace() {
            *freq.entry(w.to_string()).or_insert(0) += 1;
        }
    }
    freq.into_iter()
        .filter(|(_, c)| *c > 5)
        .map(|(w, _)| w)
        .collect()
}

/// Transforme corpus en matrice et labels en Array1 selon vocab
fn vectorize(
    corpus: &[(String, usize)],
    vocab_index: &std::collections::HashMap<String, usize>
) -> (Array2<f64>, Array1<usize>) {
    let n = corpus.len();
    let m = vocab_index.len();
    let mut mat = Array2::<f64>::zeros((n, m));
    let mut labels = Vec::with_capacity(n);
    for (i, (text, label)) in corpus.iter().enumerate() {
        labels.push(*label);
        for w in text.split_whitespace() {
            if let Some(&j) = vocab_index.get(w) {
                mat[[i, j]] += 1.;
            }
        }
    }
    (mat, Array1::from(labels))
}

fn main() -> Result<(), Box<dyn Error>> {
    // Charge corpus
    let train_raw = read_csv("../data/train.csv")?;
    let test_raw  = read_csv("../data/test.csv")?;

    // Vocabulaire basé uniquement sur l'entraînement
    let vocab = build_vocab(&train_raw);
    let vocab_index: std::collections::HashMap<_, _> = vocab
        .iter().cloned().enumerate().map(|(i,w)| (w,i)).collect();

    // Vectorisation
    let (train_x, train_y) = vectorize(&train_raw, &vocab_index);
    let (test_x, test_y)   = vectorize(&test_raw, &vocab_index);

    // Création de Datasets
    let train_ds = Dataset::new(train_x, train_y);
    let test_ds  = Dataset::new(test_x, test_y);

    // Entraînement
    let model = LogisticRegression::default()
        .max_iterations(100)
        .fit(&train_ds)?;

    // Évaluation
    let cm_train = model.predict(&train_ds).confusion_matrix(&train_ds)?;
    println!("Train Acc: {:.2}%", cm_train.accuracy() * 100.);

    let cm_test = model.predict(&test_ds).confusion_matrix(&test_ds)?;
    println!("Test  Acc: {:.2}%", cm_test.accuracy() * 100.);
    println!("Precision: {:.2}%", cm_test.precision() * 100.);
    println!("Recall: {:.2}%", cm_test.recall() * 100.);

    Ok(())
}
