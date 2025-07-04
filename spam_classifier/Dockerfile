# Build stage - utilise la même base que runtime
FROM rust:1.82-bookworm as builder

WORKDIR /app
COPY Cargo.toml ./
COPY src/ ./src/

# Build release
RUN cargo build --release

# Runtime stage - même version de Debian
FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app/target/release/spam_classifier /app/spam_classifier
COPY --from=builder /app/target/release/train /app/train

RUN chmod +x /app/spam_classifier /app/train
RUN mkdir -p /app/model

EXPOSE 8081

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8081/health || exit 1

CMD ["./spam_classifier"]