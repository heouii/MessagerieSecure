use anyhow::{Result, Context};
use csv::ReaderBuilder;
use regex::Regex;
use std::{collections::HashMap, fs};

#[derive(Debug, Clone)]
pub struct Email {
    pub text: String,
    pub label: usize, // 0 = ham, 1 = spam
}

#[derive(Debug, Clone)]
pub struct Vocabulary {
    pub words: Vec<String>,
    pub word_to_index: HashMap<String, usize>,
}

impl Vocabulary {
    pub fn new(words: Vec<String>) -> Self {
        let word_to_index = words
            .iter()
            .enumerate()
            .map(|(i, word)| (word.clone(), i))
            .collect();
        
        Self { words, word_to_index }
    }
    
    pub fn size(&self) -> usize {
        self.words.len()
    }
    
    pub fn get_index(&self, word: &str) -> Option<usize> {
        self.word_to_index.get(word).copied()
    }
}

pub struct DataProcessor {
    regex_cleaner: Regex,
}

impl DataProcessor {
    pub fn new() -> Result<Self> {
        let regex_cleaner = Regex::new(r#"[^\w\s!?$€£]"#)
            .context("Erreur création regex")?;
        
        Ok(Self { regex_cleaner })
    }
    
    pub fn read_csv(&self, path: &str) -> Result<Vec<Email>> {
        println!("📖 Lecture du fichier: {}", path);
        
        let raw = fs::read(path)
            .with_context(|| format!("Impossible de lire le fichier: {}", path))?;
        
        let content = String::from_utf8_lossy(&raw);
        let mut rdr = ReaderBuilder::new()
            .has_headers(false)
            .from_reader(content.as_bytes());
        
        let mut emails = Vec::new();
        let mut line_count = 0;
        
        for result in rdr.records() {
            line_count += 1;
            let record = result
                .with_context(|| format!("Erreur lecture ligne {}", line_count))?;
            
            if record.len() < 2 {
                continue;
            }
            
            let label = if record[0].trim().to_lowercase() == "spam" { 1 } else { 0 };
            let text = self.clean_text(&record[1]);
            
            emails.push(Email { text, label });
        }
        
        println!("✅ {} emails lus", emails.len());
        Ok(emails)
    }
    
    fn clean_text(&self, text: &str) -> String {
        let cleaned = self.regex_cleaner.replace_all(text, " ");
        cleaned
            .to_lowercase()
            .split_whitespace()
            .collect::<Vec<_>>()
            .join(" ")
    }
    
    pub fn build_vocabulary(&self, emails: &[Email], min_frequency: usize, max_vocab_size: usize) -> Vocabulary {
        let mut word_freq = HashMap::new();
        
        for email in emails {
            for word in email.text.split_whitespace() {
                if word.len() > 1 {
                    *word_freq.entry(word.to_string()).or_insert(0) += 1;
                }
            }
        }
        
        let mut words: Vec<(String, usize)> = word_freq
            .into_iter()
            .filter(|(_, freq)| *freq >= min_frequency)
            .collect();
        
        words.sort_by(|a, b| b.1.cmp(&a.1));
        words.truncate(max_vocab_size);
        
        let vocab_words: Vec<String> = words.into_iter().map(|(word, _)| word).collect();
        
        Vocabulary::new(vocab_words)
    }
}