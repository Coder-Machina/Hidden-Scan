require('dotenv').config();
const fs = require('fs');
const path = require('path');
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

const DATA_DIR = path.join(__dirname, 'data');
const PROJECTS_FILE = path.join(DATA_DIR, 'projects.json');
const STATS_FILE = path.join(DATA_DIR, 'stats.json');

// Assure la création du dossier data
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

// Chargement & sauvegarde des projets locaux
function loadProjects() {
    try {
        if (!fs.existsSync(PROJECTS_FILE)) return {};
        return JSON.parse(fs.readFileSync(PROJECTS_FILE, 'utf8') || '{}');
    } catch (e) {
        return {};
    }
}

function saveProjects(data) {
    fs.writeFileSync(PROJECTS_FILE, JSON.stringify(data, null, 2), 'utf8');
}

// Chargement & sauvegarde des stats du staff
function loadStats() {
    try {
        if (!fs.existsSync(STATS_FILE)) return [];
        return JSON.parse(fs.readFileSync(STATS_FILE, 'utf8') || '[]');
    } catch (e) {
        return [];
    }
}

function recordContribution(userId, userName, projectKey, step, chapterNumber) {
    const stats = loadStats();
    stats.push({
        userId,
        userName,
        projectKey,
        step,
        chapterNumber,
        timestamp: new Date().toISOString(),
    });
    fs.writeFileSync(STATS_FILE, JSON.stringify(stats, null, 2), 'utf8');
}

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
    ],
});

/**
 * Construit l'Embed de fiche de production (Exactement comme Raijin Staff)
 */
function buildFicheEmbed(project) {
    const rushPrefix = project.is_rush ? '🚨 • ⚠️ RUSH ACTIF ! • ' : '📚 • 💀 UP! • ';
    const embedColor = project.is_rush ? 0xEF4444 : 0xDC2626;

    let desc = '';

    // Liens
    desc += `**— Liens —**\n`;
    desc += `📁 **Drive :**\n${project.drive_url ? `[Ouvrir le dossier Google Drive](${project.drive_url})` : '*Non configuré*'}\n\n`;
    desc += `🌐 **VA :**\n${project.va_url ? `[Accéder à la source VA](${project.va_url})` : '*Non configuré*'}\n\n`;

    // Avancement
    desc += `**— Avancement —**\n`;
    desc += `✏️ **Trad**  > \`[${project.steps.trad || 0}]\` chap. — ${project.staff.trad || '*Non assigné*'}\n`;
    desc += `✅ **Check** > \`[${project.steps.check || 0}]\` chap. — ${project.staff.check || '*Non assigné*'}\n`;
    desc += `🧹 **Clean** > \`[${project.steps.clean || 0}]\` chap. — ${project.staff.clean || '*Non assigné*'}\n`;
    desc += `✏️ **Edit**  > \`[${project.steps.edit || 0}]\` chap. — ${project.staff.edit || '*Non assigné*'}\n`;
    desc += `🔍 **QC**    > \`[${project.steps.qc || 0}]\` chap. — ${project.staff.qc || '*Non assigné*'}\n\n`;

    // Compteurs
    desc += `**— Chapitres —**\n`;
    desc += `🇬🇧 **VA :** \` ${project.va_chapter || 0} \`   |   🇫🇷 **FR :** \` ${project.steps.qc || project.steps.edit || 0} \`\n`;

    const embed = new EmbedBuilder()
        .setTitle(`${rushPrefix}${project.title}`)
        .setDescription(desc)
        .setColor(embedColor)
        .setFooter({ text: `Hidden Scan Staff • Projet : ${project.slug} • ${new Date().toLocaleDateString('fr-FR')}` })
        .setTimestamp();

    if (project.cover_url) {
        embed.setImage(project.cover_url);
    }

    return embed;
}

/**
 * Boutons cliquables interactifs
 */
function buildFicheComponents(project) {
    const row1 = new ActionRowBuilder().addComponents(
        new ButtonBuilder()
            .setCustomId(`step_trad_${project.slug}`)
            .setLabel(`Trad (${project.steps.trad || 0})`)
            .setEmoji('✏️')
            .setStyle(ButtonStyle.Primary),
        new ButtonBuilder()
            .setCustomId(`step_check_${project.slug}`)
            .setLabel(`Check (${project.steps.check || 0})`)
            .setEmoji('✅')
            .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
            .setCustomId(`step_clean_${project.slug}`)
            .setLabel(`Clean (${project.steps.clean || 0})`)
            .setEmoji('🧹')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_edit_${project.slug}`)
            .setLabel(`Edit (${project.steps.edit || 0})`)
            .setEmoji('📝')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_qc_${project.slug}`)
            .setLabel(`Q-Check (${project.steps.qc || 0})`)
            .setEmoji('🔍')
            .setStyle(ButtonStyle.Primary)
    );

    const row2 = new ActionRowBuilder().addComponents(
        new ButtonBuilder()
            .setCustomId(`toggle_rush_${project.slug}`)
            .setLabel(project.is_rush ? '🚨 Désactiver Rush' : '🚨 Activer Rush')
            .setStyle(project.is_rush ? ButtonStyle.Danger : ButtonStyle.Secondary)
    );

    if (project.drive_url) {
        row2.addComponents(
            new ButtonBuilder()
                .setLabel('📁 Ouvrir Drive')
                .setStyle(ButtonStyle.Link)
                .setURL(project.drive_url)
        );
    }

    return [row1, row2];
}

/**
 * Enregistrement des Commandes Slash 100% sur Discord
 */
async function registerCommands() {
    const commands = [
        new SlashCommandBuilder()
            .setName('fiche')
            .setDescription('Affiche la fiche de production d\'un manga')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Nom du projet')
                    .setRequired(true)
                    .setAutocomplete(true)
            ),
        new SlashCommandBuilder()
            .setName('creer-fiche')
            .setDescription('Crée une nouvelle fiche de projet directement depuis Discord')
            .addStringOption(option =>
                option.setName('nom')
                    .setDescription('Nom de l\'œuvre (ex: The Executioner)')
                    .setRequired(true)
            )
            .addStringOption(option =>
                option.setName('drive')
                    .setDescription('Lien du dossier Google Drive partagé')
                    .setRequired(false)
            )
            .addStringOption(option =>
                option.setName('va')
                    .setDescription('Lien de la VA / RAW source')
                    .setRequired(false)
            )
            .addNumberOption(option =>
                option.setName('va_chapitre')
                    .setDescription('Dernier numéro de chapitre disponible en VA')
                    .setRequired(false)
            )
            .addStringOption(option =>
                option.setName('image')
                    .setDescription('Lien de l\'affiche/couverture du projet')
                    .setRequired(false)
            ),
        new SlashCommandBuilder()
            .setName('config-staff')
            .setDescription('Assigne un membre du staff à une étape sur un projet')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Projet concerné')
                    .setRequired(true)
                    .setAutocomplete(true)
            )
            .addStringOption(option =>
                option.setName('role')
                    .setDescription('Étape à assigner')
                    .setRequired(true)
                    .addChoices(
                        { name: 'Traducteur (Trad)', value: 'trad' },
                        { name: 'Correcteur (Check)', value: 'check' },
                        { name: 'Cleaneur (Clean)', value: 'clean' },
                        { name: 'Éditeur / Lettrage (Edit)', value: 'edit' },
                        { name: 'Contrôle Qualité (QC)', value: 'qc' },
                    )
            )
            .addUserOption(option =>
                option.setName('membre')
                    .setDescription('Membre Discord assigné')
                    .setRequired(true)
            ),
        new SlashCommandBuilder()
            .setName('set-chapitre')
            .setDescription('Modifie manuellement le numéro de chapitre d\'une étape')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Projet concerné')
                    .setRequired(true)
                    .setAutocomplete(true)
            )
            .addStringOption(option =>
                option.setName('role')
                    .setDescription('Étape')
                    .setRequired(true)
                    .addChoices(
                        { name: 'Trad', value: 'trad' },
                        { name: 'Check', value: 'check' },
                        { name: 'Clean', value: 'clean' },
                        { name: 'Edit', value: 'edit' },
                        { name: 'QC', value: 'qc' },
                        { name: 'Dernier VA', value: 'va' },
                    )
            )
            .addNumberOption(option =>
                option.setName('numero')
                    .setDescription('Nouveau numéro de chapitre')
                    .setRequired(true)
            ),
        new SlashCommandBuilder()
            .setName('rush')
            .setDescription('Active ou désactive le mode Rush pour un projet prioritaire')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Nom du projet')
                    .setRequired(true)
                    .setAutocomplete(true)
            ),
        new SlashCommandBuilder()
            .setName('stats-staff')
            .setDescription('Affiche le classement des chapitres faits par chaque membre'),
        new SlashCommandBuilder()
            .setName('agenda')
            .setDescription('Affiche la liste des projets en retard ou en mode rush'),
    ];

    const rest = new REST({ version: '10' }).setToken(process.env.DISCORD_BOT_TOKEN);

    try {
        console.log('🔄 Enregistrement des commandes slash pour chaque serveur...');
        for (const [guildId, guild] of client.guilds.cache) {
            try {
                await rest.put(
                    Routes.applicationGuildCommands(client.user.id, guildId),
                    { body: commands }
                );
                console.log(`✅ Commandes enregistrées instantanément sur "${guild.name}" (${guildId})`);
            } catch (err) {
                console.warn(`⚠️ Erreur guild ${guild.name}:`, err.message);
            }
        }

        await rest.put(
            Routes.applicationCommands(client.user.id),
            { body: commands }
        );
        console.log('✅ Commandes globales synchronisées !');
    } catch (error) {
        console.error('❌ Erreur enregistrement commandes:', error);
    }
}

// ═══ Événements Bot ═══

client.once('ready', async () => {
    console.log(`🤖 Bot Hidden Scan connecté en tant que ${client.user.tag}`);
    await registerCommands();
});

// Autocomplétion dynamique des projets
client.on('interactionCreate', async interaction => {
    if (!interaction.isAutocomplete()) return;

    const focusedValue = interaction.options.getFocused().toLowerCase();
    const projects = loadProjects();
    const list = Object.values(projects);

    const filtered = list
        .filter(p => p.title.toLowerCase().includes(focusedValue) || p.slug.toLowerCase().includes(focusedValue))
        .slice(0, 25);

    await interaction.respond(
        filtered.map(p => ({ name: p.title, value: p.slug }))
    );
});

// Commandes Slash
client.on('interactionCreate', async interaction => {
    if (!interaction.isChatInputCommand()) return;

    const { commandName } = interaction;
    const projects = loadProjects();

    // /creer-fiche
    if (commandName === 'creer-fiche') {
        const nom = interaction.options.getString('nom');
        const drive = interaction.options.getString('drive');
        const va = interaction.options.getString('va');
        const vaChap = interaction.options.getNumber('va_chapitre') || 0;
        const img = interaction.options.getString('image');

        const slug = nom.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');

        projects[slug] = {
            slug,
            title: nom,
            drive_url: drive || null,
            va_url: va || null,
            va_chapter: vaChap,
            cover_url: img || null,
            is_rush: false,
            staff: {
                trad: null,
                check: null,
                clean: null,
                edit: null,
                qc: null,
            },
            steps: {
                trad: 0,
                check: 0,
                clean: 0,
                edit: 0,
                qc: 0,
            },
        };

        saveProjects(projects);

        const embed = buildFicheEmbed(projects[slug]);
        const components = buildFicheComponents(projects[slug]);

        await interaction.reply({
            content: `🎉 **Fiche créée avec succès pour « ${nom} » !** Utilisez \`/config-staff\` pour assigner les membres de votre équipe.`,
            embeds: [embed],
            components,
        });
    }

    // /config-staff
    else if (commandName === 'config-staff') {
        const slug = interaction.options.getString('manga');
        const role = interaction.options.getString('role');
        const membre = interaction.options.getUser('membre');

        if (!projects[slug]) {
            return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
        }

        projects[slug].staff[role] = `<@${membre.id}>`;
        saveProjects(projects);

        const embed = buildFicheEmbed(projects[slug]);
        const components = buildFicheComponents(projects[slug]);

        await interaction.reply({
            content: `✅ <@${membre.id}> a été assigné au rôle **${role.toUpperCase()}** sur **${projects[slug].title}** !`,
            embeds: [embed],
            components,
        });
    }

    // /set-chapitre
    else if (commandName === 'set-chapitre') {
        const slug = interaction.options.getString('manga');
        const role = interaction.options.getString('role');
        const numero = interaction.options.getNumber('numero');

        if (!projects[slug]) {
            return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
        }

        if (role === 'va') {
            projects[slug].va_chapter = numero;
        } else {
            projects[slug].steps[role] = numero;
        }
        saveProjects(projects);

        await interaction.reply({
            content: `✅ Chapitre pour **${role.toUpperCase()}** mis à jour à **${numero}** sur **${projects[slug].title}**.`,
            embeds: [buildFicheEmbed(projects[slug])],
            components: buildFicheComponents(projects[slug]),
        });
    }

    // /fiche
    else if (commandName === 'fiche') {
        const slug = interaction.options.getString('manga');

        if (!projects[slug]) {
            return await interaction.reply({
                content: `❌ Projet « ${slug} » non trouvé. Utilisez d'abord \`/creer-fiche\` pour l'ajouter !`,
                ephemeral: true,
            });
        }

        const project = projects[slug];
        await interaction.reply({
            embeds: [buildFicheEmbed(project)],
            components: buildFicheComponents(project),
        });
    }

    // /rush
    else if (commandName === 'rush') {
        const slug = interaction.options.getString('manga');
        if (!projects[slug]) {
            return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
        }

        projects[slug].is_rush = !projects[slug].is_rush;
        saveProjects(projects);

        const status = projects[slug].is_rush ? '🚨 **MODE RUSH ACTIVÉ**' : '✅ Mode Rush désactivé';
        await interaction.reply({
            content: `${status} pour **${projects[slug].title}** !`,
            embeds: [buildFicheEmbed(projects[slug])],
            components: buildFicheComponents(projects[slug]),
        });
    }

    // /stats-staff
    else if (commandName === 'stats-staff') {
        const stats = loadStats();
        if (stats.length === 0) {
            return await interaction.reply('📊 Aucun chapitre enregistré pour l\'instant. Cliquez sur les boutons des fiches pour comptabiliser le travail !');
        }

        // Regroupement par utilisateur
        const countsByUser = {};
        const stepTotals = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };

        stats.forEach(item => {
            const key = item.userName || item.userId;
            countsByUser[key] = (countsByUser[key] || 0) + 1;
            if (stepTotals[item.step] !== undefined) {
                stepTotals[item.step]++;
            }
        });

        const sorted = Object.entries(countsByUser).sort((a, b) => b[1] - a[1]);

        let leaderboardText = '';
        sorted.slice(0, 10).forEach(([user, count], idx) => {
            const medal = idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : '▫️'));
            leaderboardText += `${medal} **${user}** : **${count}** étape(s) complétée(s)\n`;
        });

        const embed = new EmbedBuilder()
            .setTitle('📊 Classement & Rendement du Staff Hidden Scan')
            .setColor(0xDC2626)
            .setDescription(`Total des étapes réalisées par le staff :`)
            .addFields(
                { name: '🏆 Top Contributeurs', value: leaderboardText || '*Aucune donnée.*' },
                {
                    name: '📈 Répartition Totale',
                    value: `✏️ Trad : **${stepTotals.trad}** | ✅ Check : **${stepTotals.check}** | 🧹 Clean : **${stepTotals.clean}** | 📝 Edit : **${stepTotals.edit}** | 🔍 QC : **${stepTotals.qc}**`
                }
            )
            .setFooter({ text: 'Hidden Scan Staff Stats' })
            .setTimestamp();

        await interaction.reply({ embeds: [embed] });
    }

    // /agenda
    else if (commandName === 'agenda') {
        const list = Object.values(projects);
        const late = list.filter(p => p.is_rush || ((p.va_chapter || 0) - (p.steps.trad || 0)) >= 3);

        if (late.length === 0) {
            return await interaction.reply('🎉 **Tout est nickel !** Aucun projet en retard ou en mode rush.');
        }

        const embed = new EmbedBuilder()
            .setTitle('⏰ Agenda des Projets en Attente / En Retard')
            .setColor(0xF59E0B)
            .setDescription('Voici les projets nécessitant un coup de boost :');

        late.forEach(p => {
            const diff = Math.max(0, (p.va_chapter || 0) - (p.steps.trad || 0));
            const rush = p.is_rush ? '🚨 [RUSH] ' : '';
            embed.addFields({
                name: `${rush}${p.title} (Retard : ${diff} chapitres)`,
                value: `VA : **${p.va_chapter || 0}** | Trad actuel : **${p.steps.trad || 0}**\nStaff : Trad: ${p.staff.trad || '—'} | Check: ${p.staff.check || '—'} | Edit: ${p.staff.edit || '—'}`
            });
        });

        await interaction.reply({ embeds: [embed] });
    }
});

// Clics sur les Boutons
client.on('interactionCreate', async interaction => {
    if (!interaction.isButton()) return;

    const customId = interaction.customId;
    const projects = loadProjects();

    // Boutons d'étape (Trad, Check, Clean, Edit, QC)
    if (customId.startsWith('step_')) {
        const parts = customId.split('_');
        const step = parts[1]; // trad, check, clean, edit, qc
        const slug = parts.slice(2).join('_');

        if (!projects[slug]) {
            return await interaction.reply({ content: 'Projet introuvable.', ephemeral: true });
        }

        const currentVal = projects[slug].steps[step] || 0;
        const newVal = currentVal + 1;
        projects[slug].steps[step] = newVal;
        saveProjects(projects);

        // Enregistre la contribution dans les statistiques
        recordContribution(
            interaction.user.id,
            interaction.user.tag || interaction.user.username,
            slug,
            step,
            newVal
        );

        // Mise à jour de l'affichage de la fiche
        await interaction.update({
            embeds: [buildFicheEmbed(projects[slug])],
            components: buildFicheComponents(projects[slug]),
        });

        // Détermine qui doit être notifié pour la suite
        const nextRoles = {
            trad: { name: 'Check', mention: projects[slug].staff.check },
            check: { name: 'Clean', mention: projects[slug].staff.clean },
            clean: { name: 'Edit', mention: projects[slug].staff.edit },
            edit: { name: 'QC', mention: projects[slug].staff.qc },
            qc: { name: 'Publication', mention: null },
        };

        const next = nextRoles[step];
        let followUpMsg = `🎉 <@${interaction.user.id}> vient de terminer **${step.toUpperCase()}** pour le chapitre **${newVal}** de **${projects[slug].title}** !`;
        if (next && next.mention) {
            followUpMsg += `\n👉 C'est ton tour pour le **${next.name}** : ${next.mention}`;
        }

        await interaction.followUp({ content: followUpMsg });
    }

    // Bouton Rush
    else if (customId.startsWith('toggle_rush_')) {
        const slug = customId.replace('toggle_rush_', '');
        if (!projects[slug]) return;

        projects[slug].is_rush = !projects[slug].is_rush;
        saveProjects(projects);

        await interaction.update({
            embeds: [buildFicheEmbed(projects[slug])],
            components: buildFicheComponents(projects[slug]),
        });

        const alert = projects[slug].is_rush
            ? `🚨 **ATTENTION LE STAFF : Mode RUSH activé sur « ${projects[slug].title} » !** Projet prioritaire !`
            : `✅ Mode Rush désactivé pour « ${projects[slug].title} ».`;

        await interaction.followUp({ content: alert });
    }
});

// Connexion
if (process.env.DISCORD_BOT_TOKEN) {
    client.login(process.env.DISCORD_BOT_TOKEN).catch(err => {
        console.error('❌ Erreur de connexion bot:', err.message);
    });
}
