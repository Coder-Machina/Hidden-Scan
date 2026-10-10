require('dotenv').config();
const {
    Client,
    GatewayIntentBits,
    REST,
    Routes,
    SlashCommandBuilder,
    EmbedBuilder,
    ActionRowBuilder,
    ButtonBuilder,
    ButtonStyle,
} = require('discord.js');

const API_BASE_URL = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
const BOT_SECRET = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
    ],
});

/**
 * Appel sécurisé à l'API interne du site Hidden Scan
 */
async function fetchApi(endpoint, options = {}) {
    const url = `${API_BASE_URL.replace(/\/+$/, '')}/api/bot/${endpoint.replace(/^\/+/, '')}`;
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Bot-Token': BOT_SECRET,
        ...(options.headers || {}),
    };

    const res = await fetch(url, { ...options, headers });
    if (!res.ok) {
        const errorText = await res.text();
        throw new Error(`API Error [${res.status}]: ${errorText}`);
    }
    return res.json();
}

/**
 * Construit l'Embed de fiche de production (Exactement le style Raijin Staff)
 */
function buildFicheEmbed(manga) {
    const rushPrefix = manga.is_rush ? '🚨 • ⚠️ RUSH ACTIF ! • ' : '📚 • 💀 UP! • ';
    const embedColor = manga.is_rush ? 0xEF4444 : 0xDC2626; // Rouge vif si rush

    let desc = '';

    // Liens de travail
    desc += `**— Liens —**\n`;
    desc += `📁 **Drive :**\n${manga.drive_url ? `[Ouvrir le dossier Google Drive](${manga.drive_url})` : '*Non configuré*'}\n\n`;
    desc += `🌐 **VA / RAW :**\n${manga.va_url ? `[Accéder à la source VA](${manga.va_url})` : '*Non configuré*'}\n\n`;

    // Avancement par étape
    desc += `**— Avancement —**\n`;
    desc += `✏️ **Trad**  > \`[${manga.steps.trad}]\` chap. — ${manga.staff.trad}\n`;
    desc += `✅ **Check** > \`[${manga.steps.check}]\` chap. — ${manga.staff.check}\n`;
    desc += `🧹 **Clean** > \`[${manga.steps.clean}]\` chap. — ${manga.staff.clean}\n`;
    desc += `✏️ **Edit**  > \`[${manga.steps.edit}]\` chap. — ${manga.staff.edit}\n`;
    desc += `🔍 **QC**    > \`[${manga.steps.qc}]\` chap. — ${manga.staff.qc}\n\n`;

    // Compteurs de chapitres
    desc += `**— Chapitres —**\n`;
    desc += `🇬🇧 **VA :** \` ${manga.va_chapter} \`   |   🇫🇷 **FR :** \` ${manga.fr_chapter} \`\n`;

    const embed = new EmbedBuilder()
        .setTitle(`${rushPrefix}${manga.title}`)
        .setDescription(desc)
        .setColor(embedColor)
        .setFooter({ text: `Hidden Scan Staff • Projet #${manga.id} • ${new Date().toLocaleDateString('fr-FR')}` })
        .setTimestamp();

    if (manga.cover_url) {
        embed.setImage(manga.cover_url);
    }

    return embed;
}

/**
 * Construit les boutons interactifs sous la fiche (Trad, Check, Clean, Edit, QC, Rush)
 */
function buildFicheComponents(manga) {
    // Ligne 1 : Boutons d'avancement des étapes
    const row1 = new ActionRowBuilder().addComponents(
        new ButtonBuilder()
            .setCustomId(`step_trad_${manga.slug}`)
            .setLabel(`Trad (${manga.steps.trad})`)
            .setEmoji('✏️')
            .setStyle(ButtonStyle.Primary),
        new ButtonBuilder()
            .setCustomId(`step_check_${manga.slug}`)
            .setLabel(`Check (${manga.steps.check})`)
            .setEmoji('✅')
            .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
            .setCustomId(`step_clean_${manga.slug}`)
            .setLabel(`Clean (${manga.steps.clean})`)
            .setEmoji('🧹')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_edit_${manga.slug}`)
            .setLabel(`Edit (${manga.steps.edit})`)
            .setEmoji('📝')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_qc_${manga.slug}`)
            .setLabel(`Q-Check (${manga.steps.qc})`)
            .setEmoji('🔍')
            .setStyle(ButtonStyle.Primary)
    );

    // Ligne 2 : Bouton Rush + Lien Drive
    const row2 = new ActionRowBuilder().addComponents(
        new ButtonBuilder()
            .setCustomId(`toggle_rush_${manga.slug}`)
            .setLabel(manga.is_rush ? '🚨 Désactiver Rush' : '🚨 Activer Rush')
            .setStyle(manga.is_rush ? ButtonStyle.Danger : ButtonStyle.Secondary)
    );

    if (manga.drive_url) {
        row2.addComponents(
            new ButtonBuilder()
                .setLabel('📁 Ouvrir Drive')
                .setStyle(ButtonStyle.Link)
                .setURL(manga.drive_url)
        );
    }

    return [row1, row2];
}

/**
 * Enregistrement des Slash Commands auprès de Discord
 */
async function registerCommands() {
    const commands = [
        new SlashCommandBuilder()
            .setName('fiche')
            .setDescription('Affiche la fiche de production complète d\'un manga')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Nom ou slug de l\'œuvre')
                    .setRequired(true)
                    .setAutocomplete(true)
            ),
        new SlashCommandBuilder()
            .setName('stats-staff')
            .setDescription('Classement et comptage des chapitres produits par chaque membre')
            .addIntegerOption(option =>
                option.setName('jours')
                    .setDescription('Période en jours (défaut: 30 jours)')
                    .setRequired(false)
            ),
        new SlashCommandBuilder()
            .setName('rush')
            .setDescription('Active ou désactive le mode Rush pour un projet prioritaire')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Nom ou slug de l\'œuvre')
                    .setRequired(true)
                    .setAutocomplete(true)
            ),
        new SlashCommandBuilder()
            .setName('agenda')
            .setDescription('Affiche les projets en retard ou nécessitant une relance'),
    ];

    const rest = new REST({ version: '10' }).setToken(process.env.DISCORD_BOT_TOKEN);

    try {
        console.log('🔄 Enregistrement des commandes slash...');
        if (process.env.DISCORD_GUILD_ID) {
            await rest.put(
                Routes.applicationGuildCommands(process.env.DISCORD_CLIENT_ID, process.env.DISCORD_GUILD_ID),
                { body: commands }
            );
        } else {
            await rest.put(
                Routes.applicationCommands(process.env.DISCORD_CLIENT_ID),
                { body: commands }
            );
        }
        console.log('✅ Commandes slash enregistrées avec succès !');
    } catch (error) {
        console.error('❌ Erreur enregistrement commandes:', error);
    }
}

// ═══ Événements Client Discord ═══

client.once('ready', async () => {
    console.log(`🤖 Bot Hidden Scan connecté en tant que ${client.user.tag}`);
    await registerCommands();
});

// Autocomplétion dynamique pour les slash commands
client.on('interactionCreate', async interaction => {
    if (!interaction.isAutocomplete()) return;

    if (interaction.commandName === 'fiche' || interaction.commandName === 'rush') {
        const focusedValue = interaction.options.getFocused().toLowerCase();
        try {
            const data = await fetchApi('mangas');
            const choices = data.mangas || [];
            const filtered = choices
                .filter(m => m.title.toLowerCase().includes(focusedValue) || m.slug.toLowerCase().includes(focusedValue))
                .slice(0, 25);

            await interaction.respond(
                filtered.map(m => ({ name: m.title, value: m.slug }))
            );
        } catch (e) {
            console.error('Erreur autocomplétion:', e);
            await interaction.respond([]);
        }
    }
});

// Gestion des commandes Slash
client.on('interactionCreate', async interaction => {
    if (!interaction.isChatInputCommand()) return;

    const { commandName } = interaction;

    // Commande /fiche
    if (commandName === 'fiche') {
        await interaction.deferReply();
        const slug = interaction.options.getString('manga');

        try {
            const res = await fetchApi(`fiche/${encodeURIComponent(slug)}`);
            const manga = res.manga;
            const embed = buildFicheEmbed(manga);
            const components = buildFicheComponents(manga);

            await interaction.editReply({
                embeds: [embed],
                components: components,
            });
        } catch (err) {
            await interaction.editReply(`❌ Impossible de charger la fiche : ${err.message}`);
        }
    }

    // Commande /rush
    else if (commandName === 'rush') {
        await interaction.deferReply();
        const slug = interaction.options.getString('manga');

        try {
            const res = await fetchApi('toggle-rush', {
                method: 'POST',
                body: JSON.stringify({ slug }),
            });

            const manga = res.manga;
            const status = manga.is_rush ? '🚨 **MODE RUSH ACTIVÉ**' : '✅ Mode Rush désactivé';
            await interaction.editReply({
                content: `${status} pour **${manga.title}** !`,
                embeds: [buildFicheEmbed(manga)],
                components: buildFicheComponents(manga),
            });
        } catch (err) {
            await interaction.editReply(`❌ Erreur bascule rush : ${err.message}`);
        }
    }

    // Commande /stats-staff
    else if (commandName === 'stats-staff') {
        await interaction.deferReply();
        const days = interaction.options.getInteger('jours') || 30;

        try {
            const stats = await fetchApi(`staff-stats?days=${days}`);
            const embed = new EmbedBuilder()
                .setTitle(`📊 Rendement & Statistiques Staff (${days} derniers jours)`)
                .setColor(0xDC2626)
                .setDescription(`Total des chapitres traités par le staff de Hidden Scan sur cette période :`);

            let leaderboardText = '';
            (stats.leaderboard || []).forEach((user, idx) => {
                const medal = idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : '▫️'));
                leaderboardText += `${medal} **${user.member}** : **${user.total_chapters}** étape(s) complétée(s)\n`;
            });

            embed.addFields(
                { name: '🏆 Top Contributeurs', value: leaderboardText || '*Aucune donnée sur cette période.*' },
                {
                    name: '📈 Répartition par Étape',
                    value: `✏️ Trad : **${stats.by_step?.trad || 0}** | ✅ Check : **${stats.by_step?.check || 0}** | 🧹 Clean : **${stats.by_step?.clean || 0}** | 📝 Edit : **${stats.by_step?.edit || 0}** | 🔍 QC : **${stats.by_step?.qc || 0}**`
                }
            );

            await interaction.editReply({ embeds: [embed] });
        } catch (err) {
            await interaction.editReply(`❌ Erreur chargement stats : ${err.message}`);
        }
    }

    // Commande /agenda
    else if (commandName === 'agenda') {
        await interaction.deferReply();
        try {
            const data = await fetchApi('agenda-reminders');
            const reminders = data.reminders || [];

            if (reminders.length === 0) {
                return await interaction.editReply('🎉 **Tout est à jour !** Aucun projet en retard ou en mode rush.');
            }

            const embed = new EmbedBuilder()
                .setTitle('⏰ Agenda des Projets en Attente / En Retard')
                .setColor(0xF59E0B)
                .setDescription(`Voici les projets nécessitant l'attention du staff :`);

            reminders.forEach(r => {
                const rushBadge = r.is_rush ? '🚨 [RUSH] ' : '';
                embed.addFields({
                    name: `${rushBadge}${r.manga} (Retard : ${r.chapters_behind} chapitres)`,
                    value: `VA : **${r.va_chapter}** | Trad actuel : **${r.trad_chapter}**\nStaff : Trad: ${r.trad_user || '—'} | Check: ${r.check_user || '—'} | Edit: ${r.edit_user || '—'}`
                });
            });

            await interaction.editReply({ embeds: [embed] });
        } catch (err) {
            await interaction.editReply(`❌ Erreur agenda : ${err.message}`);
        }
    }
});

// Gestion des Boutons Cliquables sur la Fiche
client.on('interactionCreate', async interaction => {
    if (!interaction.isButton()) return;

    const customId = interaction.customId;

    // Clic sur l'un des boutons d'avancement d'étape
    if (customId.startsWith('step_')) {
        const parts = customId.split('_');
        const step = parts[1]; // trad, check, clean, edit, qc
        const slug = parts.slice(2).join('_');

        await interaction.deferUpdate();

        try {
            const res = await fetchApi('step-done', {
                method: 'POST',
                body: JSON.stringify({
                    slug,
                    step,
                    discord_user_id: interaction.user.id,
                    discord_user_name: interaction.user.tag || interaction.user.username,
                }),
            });

            const manga = res.manga;
            const nextInfo = res.next_step;

            // Met à jour la fiche visuelle en direct
            await interaction.editReply({
                embeds: [buildFicheEmbed(manga)],
                components: buildFicheComponents(manga),
            });

            // Message de notification dans le salon pour le membre suivant
            let pingMsg = `🎉 **${interaction.user.username}** vient de terminer l'étape **${step.toUpperCase()}** pour le chapitre **${res.chapter_number}** de **${manga.title}** !`;
            if (nextInfo && nextInfo.next_user && nextInfo.next_user !== 'Non assigné') {
                pingMsg += `\n👉 C'est ton tour pour le **${nextInfo.label}** : ${nextInfo.next_user}`;
            }

            await interaction.followUp({ content: pingMsg });
        } catch (err) {
            await interaction.followUp({ content: `❌ Erreur validation étape : ${err.message}`, ephemeral: true });
        }
    }

    // Clic sur le bouton Activer/Désactiver Rush
    else if (customId.startsWith('toggle_rush_')) {
        const slug = customId.replace('toggle_rush_', '');
        await interaction.deferUpdate();

        try {
            const res = await fetchApi('toggle-rush', {
                method: 'POST',
                body: JSON.stringify({ slug }),
            });

            const manga = res.manga;
            await interaction.editReply({
                embeds: [buildFicheEmbed(manga)],
                components: buildFicheComponents(manga),
            });

            const alert = manga.is_rush
                ? `🚨 **ATTENTION LE STAFF : Le mode RUSH vient d'être activé sur « ${manga.title} » !** Projet prioritaire !`
                : `✅ Mode Rush désactivé pour « ${manga.title} ».`;

            await interaction.followUp({ content: alert });
        } catch (err) {
            await interaction.followUp({ content: `❌ Erreur mode rush : ${err.message}`, ephemeral: true });
        }
    }
});

// Connexion du bot
if (process.env.DISCORD_BOT_TOKEN && process.env.DISCORD_BOT_TOKEN !== 'VOTRE_TOKEN_BOT_DISCORD') {
    client.login(process.env.DISCORD_BOT_TOKEN).catch(err => {
        console.error('❌ Impossible de connecter le bot Discord:', err.message);
    });
} else {
    console.log('⚠️ DISCORD_BOT_TOKEN non configuré dans discord-bot/.env');
}
