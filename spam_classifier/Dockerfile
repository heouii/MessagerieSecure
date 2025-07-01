# Dockerfile pour le service Rust antispam

# 1. Builder stage: compile le binaire en release
FROM rust:latest AS builder
WORKDIR /usr/src/spam_classifier

# Copie les fichiers Rust
COPY Cargo.toml Cargo.lock ./
COPY src ./src

# Compile avec optimisation
RUN cargo build --release

# 2. Runtime stage: déploie uniquement le binaire
FROM debian:bookworm-slim

# Installer le minimum nécessaire
RUN apt-get update \
    && apt-get install -y ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Copier le binaire compilé depuis le builder
COPY --from=builder /usr/src/spam_classifier/target/release/spam_classifier /usr/local/bin/spam_classifier

# Créer le dossier /data où seront montés train.csv/test.csv
WORKDIR /data

# Exposer le port sur lequel le service écoute
EXPOSE 8081

# Commande par défaut
CMD ["spam_classifier"]
