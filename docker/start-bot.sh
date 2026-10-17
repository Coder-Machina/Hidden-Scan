#!/bin/sh
set -e

# Vérifier si le token Discord est configuré
if [ -z "$DISCORD_BOT_TOKEN" ]; then
    echo "ℹ️ [Discord Bot] Aucun DISCORD_BOT_TOKEN fourni dans l'environnement. Le bot reste inactif."
    exec sleep infinity
fi

echo "🤖 [Discord Bot] Lancement du bot officiel Hidden Scan..."
cd /var/www/html/discord-bot

# Assurer l'URL de l'API locale si non fournie
export API_BASE_URL="${API_BASE_URL:-http://127.0.0.1}"

exec node index.js
