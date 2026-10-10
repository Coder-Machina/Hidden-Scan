# 🤖 Bot Discord Officiel — Hidden Scan

Ce bot gère deux aspects essentiels pour la team **Hidden Scan** :
1. **Les Annonces de Sorties Publiques** : Publication automatique des nouveaux chapitres avec grande affiche, pings de rôles dédiés par projet et bouton interactif vers le lecteur.
2. **Le Workflow & Agenda du Staff (Style Raijin Staff)** :
   * Commande `/fiche [manga]` : Affiche la fiche de production avec les liens Drive & VA, le staff assigné et les compteurs de chapitres.
   * Boutons interactifs cliquables : `[✏️ Trad]`, `[✅ Check]`, `[🧹 Clean]`, `[📝 Edit]`, `[🔍 QC]` pour incrémenter les chapitres d'un simple clic et pinguer le membre suivant.
   * Bouton `[🚨 Activer Rush]` pour sonner l'alerte sur un projet prioritaire.
   * Commande `/stats-staff` : Classement et comptage précis des chapitres complétés par chaque membre.
   * Commande `/agenda` : Liste des projets en retard ou nécessitant une relance.

---

## 🚀 Installation & Configuration Rapide (3 minutes)

### 1. Créer le Bot sur Discord Developer Portal
1. Rendez-vous sur https://discord.com/developers/applications
2. Cliquez sur **"New Application"** et nommez-la `Hidden Scan` (ou `Hidden Sorties`).
3. Allez dans l'onglet **"Bot"** :
   * Cliquez sur **"Reset Token"** pour copier votre `DISCORD_BOT_TOKEN`.
   * Cochez les options **"Privileged Gateway Intents"** :
     * ✅ `Server Members Intent`
     * ✅ `Message Content Intent`
4. Allez dans l'onglet **"OAuth2" ➔ "URL Generator"** :
   * Cochez `bot` et `applications.commands`.
   * Cochez les permissions : `Send Messages`, `Embed Links`, `Attach Files`, `Read Message History`, `Use Slash Commands`.
   * Copiez l'URL générée et ouvrez-la dans votre navigateur pour inviter le bot sur votre serveur Discord.

### 2. Configurer le fichier `.env` du Bot
Dans le dossier `discord-bot/` :
```bash
cp .env.example .env
```
Renseignez les valeurs dans `discord-bot/.env` :
```env
DISCORD_BOT_TOKEN=OTgz...votre_token_ici...
DISCORD_CLIENT_ID=votre_application_id_ici
DISCORD_GUILD_ID=id_de_votre_serveur_discord
API_BASE_URL=http://127.0.0.1:8000
DISCORD_BOT_SECRET=hiddenscan-secret-bot-key
```

### 3. Lancer le Bot
Dans le terminal :
```bash
cd discord-bot
npm install
npm start
```

Le bot se connecte, enregistre les commandes slash `/fiche`, `/stats-staff`, `/rush`, `/agenda` et est immédiatement opérationnel !
