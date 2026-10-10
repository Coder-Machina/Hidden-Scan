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
    AttachmentBuilder,
    StringSelectMenuBuilder,
    StringSelectMenuOptionBuilder,
    ChannelType,
} = require('discord.js');

const DATA_DIR = path.join(__dirname, 'data');
const PROJECTS_FILE = path.join(DATA_DIR, 'projects.json');
const STATS_FILE = path.join(DATA_DIR, 'stats.json');
const PANEL_FILE = path.join(DATA_DIR, 'panel.json');

// Assure la création du dossier data
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

function loadPanelConfig() {
    try {
        if (!fs.existsSync(PANEL_FILE)) return {};
        return JSON.parse(fs.readFileSync(PANEL_FILE, 'utf8') || '{}');
    } catch (e) {
        return {};
    }
}

function savePanelConfig(data) {
    fs.writeFileSync(PANEL_FILE, JSON.stringify(data, null, 2), 'utf8');
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

/**
 * Résout l'image de couverture d'un manga (locale ou web) pour affichage garanti sur Discord
 */
function resolveProjectCover(project) {
    if (!project) return null;

    // Détection fichier local dans storage/app/public/
    const relativeCover = project.cover_image || (project.cover_url && project.cover_url.includes('/storage/') ? project.cover_url.split('/storage/')[1] : null);

    if (relativeCover) {
        const fullPath = path.join(__dirname, '..', 'storage', 'app', 'public', relativeCover);
        if (fs.existsSync(fullPath)) {
            const ext = path.extname(fullPath).slice(1) || 'png';
            const attachmentName = `cover_${project.slug}.${ext}`;
            return {
                isAttachment: true,
                attachment: new AttachmentBuilder(fullPath, { name: attachmentName }),
                url: `attachment://${attachmentName}`,
            };
        }
    }

    // URL externe sécurisée
    if (project.cover_url && project.cover_url.startsWith('https://') && !project.cover_url.includes('localhost') && !project.cover_url.includes('127.0.0.1')) {
        return {
            isAttachment: false,
            url: project.cover_url,
        };
    }

    return null;
}

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
    ],
});

/**
 * Construit l'Embed de fiche de production (Style Raijin Staff / Hidden Staff)
 */
function buildFicheEmbed(project, coverInfo = null) {
    const rushPrefix = project.is_rush ? '🚨 • ⚠️ RUSH ACTIF ! • ' : '📚 • 💀 UP! • ';
    const embedColor = project.is_rush ? 0xEF4444 : 0x2563EB;

    let desc = '';

    // Liens
    desc += `**— Liens —**\n`;
    desc += `📁 **Drive :**\n${project.drive_url ? `[Ouvrir le dossier Google Drive](${project.drive_url})` : '*Non configuré*'}\n\n`;
    desc += `🌐 **VA :**\n${project.va_url ? `[Accéder à la source VA](${project.va_url})` : '*Non configuré*'}\n\n`;

    // Avancement
    desc += `**— Avancement —**\n`;
    desc += `✏️ **Trad**  > \`[${project.steps?.trad || 0}]\` chap. — ${project.staff?.trad || '*Non assigné*'}\n`;
    desc += `✅ **Check** > \`[${project.steps?.check || 0}]\` chap. — ${project.staff?.check || '*Non assigné*'}\n`;
    desc += `🧹 **Clean** > \`[${project.steps?.clean || 0}]\` chap. — ${project.staff?.clean || '*Non assigné*'}\n`;
    desc += `✏️ **Edit**  > \`[${project.steps?.edit || 0}]\` chap. — ${project.staff?.edit || '*Non assigné*'}\n`;
    desc += `🔍 **QC**    > \`[${project.steps?.qc || 0}]\` chap. — ${project.staff?.qc || '*Non assigné*'}\n\n`;

    // Compteurs
    desc += `**— Chapitres —**\n`;
    desc += `🇬🇧 **VA :** \` ${project.va_chapter || 0} \`   |   🇫🇷 **FR :** \` ${project.steps?.qc || project.steps?.edit || 0} \`\n`;

    const embed = new EmbedBuilder()
        .setTitle(`${rushPrefix}${project.title}`)
        .setDescription(desc)
        .setColor(embedColor)
        .setFooter({ text: `Hidden Scan Staff • Projet : ${project.slug} • ${new Date().toLocaleDateString('fr-FR')}` })
        .setTimestamp();

    if (coverInfo && coverInfo.url) {
        embed.setImage(coverInfo.url);
    } else if (project.cover_url && !project.cover_url.includes('localhost') && !project.cover_url.includes('127.0.0.1')) {
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
            .setLabel(`Trad (${project.steps?.trad || 0})`)
            .setEmoji('✏️')
            .setStyle(ButtonStyle.Primary),
        new ButtonBuilder()
            .setCustomId(`step_check_${project.slug}`)
            .setLabel(`Check (${project.steps?.check || 0})`)
            .setEmoji('✅')
            .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
            .setCustomId(`step_clean_${project.slug}`)
            .setLabel(`Clean (${project.steps?.clean || 0})`)
            .setEmoji('🧹')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_edit_${project.slug}`)
            .setLabel(`Edit (${project.steps?.edit || 0})`)
            .setEmoji('📝')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_qc_${project.slug}`)
            .setLabel(`Q-Check (${project.steps?.qc || 0})`)
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
 * Synchronise les mangas depuis l'API Laravel vers le stockage local du bot
 */
async function syncProjectsWithSite() {
    try {
        const apiUrl = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
        const secret = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';

        const res = await fetch(`${apiUrl}/api/bot/mangas`, {
            headers: { 'X-Bot-Token': secret },
        });

        if (!res.ok) {
            console.warn(`⚠️ Synchronisation mangas: HTTP ${res.status}`);
            return;
        }

        const data = await res.json();
        if (!data.success || !Array.isArray(data.mangas)) return;

        const currentProjects = loadProjects();
        let changed = false;

        // Nettoyage de l'ancienne clé en doublon
        if (currentProjects['the-returner-s-road-to-retirement']) {
            delete currentProjects['the-returner-s-road-to-retirement'];
            changed = true;
        }

        for (const manga of data.mangas) {
            const slug = manga.slug;
            const isExternal = Boolean(manga.is_external);

            if (!currentProjects[slug]) {
                try {
                    const ficheRes = await fetch(`${apiUrl}/api/bot/fiche/${slug}`, {
                        headers: { 'X-Bot-Token': secret },
                    });
                    if (ficheRes.ok) {
                        const ficheData = await ficheRes.json();
                        if (ficheData.success && ficheData.manga) {
                            const m = ficheData.manga;
                            currentProjects[slug] = {
                                slug: m.slug,
                                title: m.title.trim(),
                                is_external: isExternal,
                                team_name: manga.team_name || null,
                                discord_role_id: isExternal ? null : (manga.discord_role_id || null),
                                drive_url: m.drive_url || null,
                                va_url: m.va_url || null,
                                va_chapter: m.va_chapter || 0,
                                cover_url: m.cover_url || null,
                                cover_image: m.cover_image || null,
                                is_rush: m.is_rush || false,
                                staff: {
                                    trad: m.staff?.trad && m.staff?.trad !== 'Non assigné' ? m.staff?.trad : null,
                                    check: m.staff?.check && m.staff?.check !== 'Non assigné' ? m.staff?.check : null,
                                    clean: m.staff?.clean && m.staff?.clean !== 'Non assigné' ? m.staff?.clean : null,
                                    edit: m.staff?.edit && m.staff?.edit !== 'Non assigné' ? m.staff?.edit : null,
                                    qc: m.staff?.qc && m.staff?.qc !== 'Non assigné' ? m.staff?.qc : null,
                                },
                                steps: {
                                    trad: m.steps?.trad || 0,
                                    check: m.steps?.check || 0,
                                    clean: m.steps?.clean || 0,
                                    edit: m.steps?.edit || 0,
                                    qc: m.steps?.qc || 0,
                                },
                            };
                            changed = true;
                        }
                    }
                } catch (e) {
                    console.error(`Erreur sync manga ${slug}:`, e.message);
                }
            } else {
                if (currentProjects[slug].is_external !== isExternal) {
                    currentProjects[slug].is_external = isExternal;
                    changed = true;
                }
                if (isExternal && currentProjects[slug].discord_role_id) {
                    currentProjects[slug].discord_role_id = null;
                    changed = true;
                } else if (!isExternal && manga.discord_role_id && currentProjects[slug].discord_role_id !== manga.discord_role_id) {
                    currentProjects[slug].discord_role_id = manga.discord_role_id;
                    changed = true;
                }
                if (!currentProjects[slug].cover_image && manga.cover_image) {
                    currentProjects[slug].cover_image = manga.cover_image;
                    changed = true;
                }
                if (!currentProjects[slug].title) {
                    currentProjects[slug].title = manga.title.trim();
                    changed = true;
                }
            }
        }

        if (changed) {
            saveProjects(currentProjects);
            console.log(`✅ Synchronisation réussie : ${Object.keys(currentProjects).length} projets disponibles.`);
        }
    } catch (err) {
        console.error('⚠️ Échec synchronisation mangas avec le site:', err.message);
    }
}

const PROJECT_COLORS = [
    0x3498db, // Bleu azur (Style Rimu)
    0xe74c3c, // Rouge rubis (Style Poseidon)
    0x9b59b6, // Violet améthyste
    0x2ecc71, // Vert émeraude
    0xf39c12, // Or / Ambre
    0xe67e22, // Orange vif
    0x1abc9c, // Cyan néon
    0xe91e63, // Rose fuchsia
    0x5865f2, // Bleu Discord Blurple
    0x00cec9, // Turquoise
];

/**
 * Vérifie et crée automatiquement les rôles Discord stylisés avec couleur pour tous les mangas
 */
async function ensureProjectRoles() {
    try {
        const targetGuildId = process.env.DISCORD_GUILD_ID || '1535251657326075906';
        const apiUrl = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
        const secret = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';
        const projects = loadProjects();

        for (const [guildId, guild] of client.guilds.cache) {
            console.log(`🔍 Vérification des rôles sur "${guild.name}" (${guildId})...`);
            const roles = await guild.roles.fetch();
            let changed = false;

            // 1. Rôle global "Tous les projets"
            let allProjectsRole = roles.find(r => r.name.toLowerCase() === 'tous les projets');
            if (!allProjectsRole) {
                try {
                    allProjectsRole = await guild.roles.create({
                        name: 'Tous les projets',
                        color: 0x5865f2,
                        mentionable: true,
                        reason: 'Rôle global de notification pour toutes les sorties',
                    });
                    console.log(`✨ Rôle global "Tous les projets" créé sur "${guild.name}": ${allProjectsRole.id}`);
                } catch (e) {
                    console.warn(`Impossible de créer "Tous les projets" sur "${guild.name}":`, e.message);
                }
            } else if (!allProjectsRole.mentionable || allProjectsRole.color === 0) {
                try {
                    await allProjectsRole.edit({
                        color: allProjectsRole.color === 0 ? 0x5865f2 : allProjectsRole.color,
                        mentionable: true,
                    });
                } catch (e) {}
            }

            // 2. Rôles de chaque projet avec couleur attribuée (uniquement les projets traduits par l'équipe !)
            let colorIndex = 0;
            for (const [slug, project] of Object.entries(projects)) {
                // Ignore les projets externes traduits par d'autres teams
                if (project.is_external) continue;
                let role = null;
                const assignedColor = PROJECT_COLORS[colorIndex % PROJECT_COLORS.length];
                colorIndex++;

                if (project.discord_role_id && !isNaN(project.discord_role_id)) {
                    role = roles.get(project.discord_role_id);
                }

                if (!role) {
                    role = roles.find(r => r.name.toLowerCase() === project.title.toLowerCase());
                }

                if (!role) {
                    try {
                        role = await guild.roles.create({
                            name: project.title,
                            color: assignedColor,
                            mentionable: true,
                            reason: `Rôle officiel de projet pour ${project.title}`,
                        });
                        console.log(`✨ Rôle créé avec couleur #${assignedColor.toString(16)} pour "${project.title}": ${role.id}`);
                    } catch (err) {
                        console.error(`Impossible de créer le rôle pour "${project.title}":`, err.message);
                    }
                } else {
                    if (!role.mentionable || role.color === 0) {
                        try {
                            await role.edit({
                                color: role.color === 0 ? assignedColor : role.color,
                                mentionable: true,
                            });
                            console.log(`🎨 Couleur #${assignedColor.toString(16)} et mention appliquées à "${project.title}"`);
                        } catch (e) {}
                    }
                }

                // Associe le rôle si on est sur la guild cible des annonces ou si guild unique
                if (role && (guildId === targetGuildId || client.guilds.cache.size === 1)) {
                    if (project.discord_role_id !== role.id) {
                        project.discord_role_id = role.id;
                        changed = true;

                        try {
                            await fetch(`${apiUrl}/api/bot/link-role`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Bot-Token': secret,
                                },
                                body: JSON.stringify({ slug, role_id: role.id }),
                            });
                        } catch (e) {
                            console.error('Erreur sync link-role API:', e.message);
                        }
                    }
                }
            }

            if (changed) {
                saveProjects(projects);
                console.log(`✅ Rôles synchronisés et sauvegardés pour "${guild.name}" !`);
            }

            // Met à jour le panneau interactif des rôles si configuré ou sur le serveur principal
            await updateOrCreateRolesPanel(guild);
        }
    } catch (e) {
        console.error('Erreur ensureProjectRoles:', e.message);
    }
}

/**
 * Construit l'Embed du panneau de rôles des séries (Style Hidden Scan)
 */
function buildRolesPanelEmbed(guild, rolesCount) {
    const icon = guild.iconURL({ dynamic: true }) || 'https://hidden-scan.onrender.com/logo.png';
    return new EmbedBuilder()
        .setTitle('📕 • Choisissez vos Rôles de Notification !')
        .setDescription(
            `Bienvenue sur **${guild.name}** !\n\n` +
            `Pour être notifié des sorties de vos séries préférées ou de l'ensemble de nos projets, sélectionnez vos rôles ci-dessous :\n\n` +
            `📚 **Tous les projets** : Recevoir une notification pour **chaque nouveau chapitre** publié sur le site.\n` +
            `📖 **Par série** : Choisir uniquement les séries que vous suivez.\n\n` +
            `*(Sélectionnez dans le menu déroulant ci-dessous ou utilisez les boutons rapides)*`
        )
        .setColor(0x2563EB)
        .setThumbnail(icon)
        .addFields({
            name: '✨ Site officiel',
            value: `[Accéder à Hidden Scan](https://hidden-scan.onrender.com/) • **${rolesCount}** rôle(s) de séries configuré(s)`,
            inline: false,
        })
        .setFooter({ text: 'Hidden Scan • Système automatique de notification', iconURL: icon })
        .setTimestamp();
}

const PROJECT_EMOJIS = {
    'genius-grandson-of-the-loan-shark-king': '💰',
    'the-returners-road-to-retirement': '⌛',
    'the-returner-s-road-to-retirement': '⌛',
    'my-passive-skills-are-invincible': '🗡️',
    'all-football-talents-are-mine': '⚽',
};

/**
 * Construit les composants (Menu déroulant + Boutons rapides) du panneau de rôles
 */
function buildRolesPanelComponents(guild, projects, allProjectsRoleId) {
    const options = [];

    // Option: Tous les projets
    if (allProjectsRoleId && guild.roles.cache.has(allProjectsRoleId)) {
        options.push(
            new StringSelectMenuOptionBuilder()
                .setLabel('Tous les projets')
                .setValue(allProjectsRoleId)
                .setDescription('Notification globale pour chaque sortie de chapitre')
                .setEmoji('📚')
        );
    }

    // Options par manga traduits par l'équipe (dédoublonnées par rôle)
    const seenRoles = new Set();
    if (allProjectsRoleId) seenRoles.add(allProjectsRoleId);

    const sortedProjects = Object.values(projects)
        .filter(p => !p.is_external && p.discord_role_id && guild.roles.cache.has(p.discord_role_id))
        .sort((a, b) => a.title.localeCompare(b.title));

    for (const p of sortedProjects) {
        if (seenRoles.has(p.discord_role_id)) continue;
        seenRoles.add(p.discord_role_id);

        if (options.length >= 25) break;

        const emoji = PROJECT_EMOJIS[p.slug] || '📖';

        options.push(
            new StringSelectMenuOptionBuilder()
                .setLabel(p.title.length > 100 ? p.title.substring(0, 97) + '...' : p.title)
                .setValue(p.discord_role_id)
                .setDescription(`Notifications pour ${p.title}`.substring(0, 100))
                .setEmoji(emoji)
        );
    }

    if (options.length === 0) {
        return [];
    }

    const selectMenu = new StringSelectMenuBuilder()
        .setCustomId('select_series_roles')
        .setPlaceholder('👉 Cliquez ici pour choisir vos séries...')
        .setMinValues(0)
        .setMaxValues(options.length)
        .addOptions(options);

    const row1 = new ActionRowBuilder().addComponents(selectMenu);

    const btnAll = new ButtonBuilder()
        .setCustomId('btn_roles_select_all')
        .setLabel('Tout activer (Toutes les séries)')
        .setStyle(ButtonStyle.Success)
        .setEmoji('📚');

    const btnClear = new ButtonBuilder()
        .setCustomId('btn_roles_clear_all')
        .setLabel('Tout retirer')
        .setStyle(ButtonStyle.Secondary)
        .setEmoji('❌');

    const row2 = new ActionRowBuilder().addComponents(btnAll, btnClear);

    return [row1, row2];
}

/**
 * Récupère tous les IDs de rôles gérés par le panneau
 */
function getPanelRoleIds(guild, projects, allProjectsRoleId) {
    const roleIds = new Set();
    if (allProjectsRoleId && guild.roles.cache.has(allProjectsRoleId)) {
        roleIds.add(allProjectsRoleId);
    }
    for (const p of Object.values(projects)) {
        if (!p.is_external && p.discord_role_id && guild.roles.cache.has(p.discord_role_id)) {
            roleIds.add(p.discord_role_id);
        }
    }
    return Array.from(roleIds);
}

/**
 * Déploie ou met à jour le panneau interactif de sélection des rôles
 */
async function updateOrCreateRolesPanel(guild, customChannelId = null, cleanOld = false) {
    try {
        const panelConfig = loadPanelConfig();
        const defaultChannelId = process.env.DISCORD_ROLES_CHANNEL_ID || '1535265358963806248';
        const channelId = customChannelId || panelConfig[guild.id]?.channel_id || defaultChannelId;

        const channel = await guild.channels.fetch(channelId).catch(() => null);
        if (!channel) {
            return null;
        }

        const projects = loadProjects();
        const allProjectsRoleId = process.env.DISCORD_ALL_PROJECTS_ROLE_ID || '1535294276618158180';
        const panelRoleIds = getPanelRoleIds(guild, projects, allProjectsRoleId);

        const embed = buildRolesPanelEmbed(guild, panelRoleIds.length);
        const components = buildRolesPanelComponents(guild, projects, allProjectsRoleId);

        if (cleanOld) {
            try {
                const msgs = await channel.messages.fetch({ limit: 50 });
                for (const [, msg] of msgs) {
                    if (msg.deletable) {
                        await msg.delete().catch(() => {});
                    }
                }
            } catch (e) {
                console.warn('[Panel] Nettoyage anciens messages:', e.message);
            }
        }

        let message = null;
        const savedMessageId = panelConfig[guild.id]?.message_id;

        if (savedMessageId && !cleanOld) {
            try {
                message = await channel.messages.fetch(savedMessageId);
            } catch (e) {
                message = null;
            }
        }

        if (message) {
            await message.edit({ embeds: [embed], components });
            console.log(`✅ Panneau des rôles mis à jour sur "${guild.name}" (#${channel.name})`);
        } else {
            message = await channel.send({ embeds: [embed], components });
            console.log(`✨ Nouveau panneau des rôles déployé sur "${guild.name}" (#${channel.name}): ${message.id}`);
        }

        panelConfig[guild.id] = {
            guild_id: guild.id,
            channel_id: channel.id,
            message_id: message.id,
            updated_at: new Date().toISOString(),
        };
        savePanelConfig(panelConfig);

        return message;
    } catch (err) {
        console.error(`❌ Erreur updateOrCreateRolesPanel sur "${guild.name}":`, err);
        return null;
    }
}

/**
 * Enregistrement des Commandes Slash 100% sur Discord (sans doublons)
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
            .setName('role-projet')
            .setDescription('Associe un rôle Discord de notification à une œuvre')
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Nom de l\'œuvre')
                    .setRequired(true)
                    .setAutocomplete(true)
            )
            .addRoleOption(option =>
                option.setName('role')
                    .setDescription('Rôle Discord à notifier lors des sorties')
                    .setRequired(true)
            ),
        new SlashCommandBuilder()
            .setName('stats-staff')
            .setDescription('Affiche le classement des chapitres faits par chaque membre'),
        new SlashCommandBuilder()
            .setName('agenda')
            .setDescription('Affiche la liste des projets en retard ou en mode rush'),
        new SlashCommandBuilder()
            .setName('sync-roles')
            .setDescription('Vérifie et crée automatiquement les rôles Discord manquants pour tous les mangas'),
        new SlashCommandBuilder()
            .setName('panneau-roles')
            .setDescription('Déploie ou met à jour le panneau interactif de sélection des rôles de séries')
            .addChannelOption(option =>
                option.setName('salon')
                    .setDescription('Salon où envoyer le panneau (défaut: rôles-séries)')
                    .addChannelTypes(ChannelType.GuildText)
                    .setRequired(false)
            )
            .addBooleanOption(option =>
                option.setName('nettoyer')
                    .setDescription('Supprimer les anciens messages du salon avant d\'envoyer le panneau')
                    .setRequired(false)
            ),
    ];

    const rest = new REST({ version: '10' }).setToken(process.env.DISCORD_BOT_TOKEN);

    try {
        console.log('🔄 Nettoyage des commandes globales pour éliminer les doublons...');
        await rest.put(
            Routes.applicationCommands(client.user.id),
            { body: [] }
        );
        console.log('🧹 Commandes globales vidées (aucun doublon possible) !');

        console.log('🔄 Enregistrement des commandes slash sur chaque serveur...');
        for (const [guildId, guild] of client.guilds.cache) {
            try {
                await rest.put(
                    Routes.applicationGuildCommands(client.user.id, guildId),
                    { body: commands }
                );
                console.log(`✅ Commandes enregistrées sur "${guild.name}" (${guildId})`);
            } catch (err) {
                console.warn(`⚠️ Erreur guild ${guild.name}:`, err.message);
            }
        }
    } catch (error) {
        console.error('❌ Erreur enregistrement commandes:', error);
    }
}

/**
 * Assure le pseudo approprié pour chaque serveur :
 * - "Hidden Scan" sur le serveur public (HIDDEN SCANTRAD)
 * - "Hidden Staff" sur le serveur interne du staff (HIDDEN STAFF)
 */
async function ensureBotNicknames() {
    try {
        const publicGuildId = process.env.DISCORD_GUILD_ID || '1535251657326075906';
        for (const [guildId, guild] of client.guilds.cache) {
            const me = await guild.members.fetch(client.user.id).catch(() => null);
            if (!me) continue;

            const desiredNick = (guildId === publicGuildId) ? 'Hidden Scan' : 'Hidden Staff';
            if (me.nickname !== desiredNick) {
                await me.setNickname(desiredNick).catch(err => {
                    console.warn(`Impossible d'ajuster le pseudo sur ${guild.name}:`, err.message);
                });
                console.log(`🏷️ Pseudo du bot ajusté sur "${guild.name}": "${desiredNick}"`);
            }
        }
    } catch (e) {
        console.error('Erreur ensureBotNicknames:', e.message);
    }
}

// ═══ Événements Bot ═══

client.once('ready', async () => {
    console.log(`🤖 Bot Hidden Scan connecté en tant que ${client.user.tag}`);
    await ensureBotNicknames();
    await registerCommands();
    await syncProjectsWithSite();
    await ensureProjectRoles();
    // Synchro toutes les 10 minutes
    setInterval(async () => {
        await ensureBotNicknames();
        await syncProjectsWithSite();
        await ensureProjectRoles();
    }, 10 * 60 * 1000);
});

client.on('guildCreate', async guild => {
    console.log(`🎉 Le bot a rejoint le serveur : "${guild.name}" (${guild.id}) !`);
    await ensureBotNicknames();
    await registerCommands();
    await syncProjectsWithSite();
    await ensureProjectRoles();
});

// Autocomplétion dynamique des projets (avec tous les mangas du site)
client.on('interactionCreate', async interaction => {
    if (!interaction.isAutocomplete()) return;

    try {
        const focusedValue = interaction.options.getFocused().toLowerCase();
        const projects = loadProjects();
        const list = Object.values(projects);

        const filtered = list
            .filter(p => p.title.toLowerCase().includes(focusedValue) || p.slug.toLowerCase().includes(focusedValue))
            .slice(0, 25);

        await interaction.respond(
            filtered.map(p => ({ name: p.title, value: p.slug }))
        );
    } catch (e) {
        console.error('Erreur autocomplete:', e.message);
    }
});

// Commandes Slash
client.on('interactionCreate', async interaction => {
    if (!interaction.isChatInputCommand()) return;

    const { commandName } = interaction;
    let projects = loadProjects();

    try {
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

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.reply({
                content: `🎉 **Fiche créée avec succès pour « ${nom} » !** Utilisez \`/config-staff\` pour assigner les membres de votre équipe.`,
                embeds: [embed],
                components,
                files,
            });
        }

        // /config-staff
        else if (commandName === 'config-staff') {
            const slug = interaction.options.getString('manga');
            const role = interaction.options.getString('role');
            const membre = interaction.options.getUser('membre');

            if (!projects[slug]) {
                await syncProjectsWithSite();
                projects = loadProjects();
            }

            if (!projects[slug]) {
                return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
            }

            if (!projects[slug].staff) {
                projects[slug].staff = {};
            }

            projects[slug].staff[role] = `<@${membre.id}>`;
            saveProjects(projects);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.reply({
                content: `✅ <@${membre.id}> a été assigné au rôle **${role.toUpperCase()}** sur **${projects[slug].title}** !`,
                embeds: [embed],
                components,
                files,
            });
        }

        // /role-projet
        else if (commandName === 'role-projet') {
            const slug = interaction.options.getString('manga');
            const role = interaction.options.getRole('role');

            if (!projects[slug]) {
                await syncProjectsWithSite();
                projects = loadProjects();
            }

            if (!projects[slug]) {
                return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
            }

            projects[slug].discord_role_id = role.id;
            saveProjects(projects);

            // Sync avec l'API Laravel
            try {
                const apiUrl = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
                const secret = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';
                await fetch(`${apiUrl}/api/bot/link-role`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Bot-Token': secret,
                    },
                    body: JSON.stringify({ slug, role_id: role.id }),
                });
            } catch (e) {
                console.error('Erreur link-role API:', e.message);
            }

            await interaction.reply({
                content: `✅ Le rôle <@&${role.id}> est désormais associé aux annonces de **${projects[slug].title}** ! Les sorties de chapitres mentionneront ce rôle avec un badge cliquable.`,
            });
        }

        // /sync-roles
        else if (commandName === 'sync-roles') {
            await interaction.deferReply({ ephemeral: true });
            await syncProjectsWithSite();
            await ensureProjectRoles();
            await interaction.editReply({
                content: '✅ Tous les rôles Discord des projets ont été vérifiés et synchronisés avec succès avec le site !',
            });
        }

        // /panneau-roles
        else if (commandName === 'panneau-roles') {
            await interaction.deferReply({ ephemeral: true });
            const targetChannel = interaction.options.getChannel('salon');
            const cleanOld = interaction.options.getBoolean('nettoyer') || false;

            const channelId = targetChannel ? targetChannel.id : null;
            const msg = await updateOrCreateRolesPanel(interaction.guild, channelId, cleanOld);

            if (msg) {
                await interaction.editReply({
                    content: `✅ Panneau interactif des rôles déployé avec succès dans <#${msg.channelId}> !`,
                });
            } else {
                await interaction.editReply({
                    content: `❌ Impossible de déployer le panneau. Vérifiez les permissions du bot dans le salon ciblé.`,
                });
            }
        }

        // /set-chapitre
        else if (commandName === 'set-chapitre') {
            const slug = interaction.options.getString('manga');
            const role = interaction.options.getString('role');
            const numero = interaction.options.getNumber('numero');

            if (!projects[slug]) {
                await syncProjectsWithSite();
                projects = loadProjects();
            }

            if (!projects[slug]) {
                return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
            }

            if (!projects[slug].steps) {
                projects[slug].steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
            }

            if (role === 'va') {
                projects[slug].va_chapter = numero;
            } else {
                projects[slug].steps[role] = numero;
            }
            saveProjects(projects);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.reply({
                content: `✅ Chapitre pour **${role.toUpperCase()}** mis à jour à **${numero}** sur **${projects[slug].title}**.`,
                embeds: [embed],
                components,
                files,
            });
        }

        // /fiche
        else if (commandName === 'fiche') {
            const slug = interaction.options.getString('manga');

            if (!projects[slug]) {
                await syncProjectsWithSite();
                projects = loadProjects();
            }

            if (!projects[slug]) {
                return await interaction.reply({
                    content: `❌ Projet « ${slug} » non trouvé. Utilisez d'abord \`/creer-fiche\` pour l'ajouter !`,
                    ephemeral: true,
                });
            }

            const project = projects[slug];
            const coverInfo = resolveProjectCover(project);
            const embed = buildFicheEmbed(project, coverInfo);
            const components = buildFicheComponents(project);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.reply({
                embeds: [embed],
                components,
                files,
            });
        }

        // /rush
        else if (commandName === 'rush') {
            const slug = interaction.options.getString('manga');

            if (!projects[slug]) {
                await syncProjectsWithSite();
                projects = loadProjects();
            }

            if (!projects[slug]) {
                return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
            }

            projects[slug].is_rush = !projects[slug].is_rush;
            saveProjects(projects);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            const status = projects[slug].is_rush ? '🚨 **MODE RUSH ACTIVÉ**' : '✅ Mode Rush désactivé';
            await interaction.reply({
                content: `${status} pour **${projects[slug].title}** !`,
                embeds: [embed],
                components,
                files,
            });
        }

        // /stats-staff
        else if (commandName === 'stats-staff') {
            const stats = loadStats();
            if (stats.length === 0) {
                return await interaction.reply('📊 Aucun chapitre enregistré pour l\'instant. Cliquez sur les boutons des fiches pour comptabiliser le travail !');
            }

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
                .setColor(0x2563EB)
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
            const late = list.filter(p => p.is_rush || ((p.va_chapter || 0) - (p.steps?.trad || 0)) >= 3);

            if (late.length === 0) {
                return await interaction.reply('🎉 **Tout est nickel !** Aucun projet en retard ou en mode rush.');
            }

            const embed = new EmbedBuilder()
                .setTitle('⏰ Agenda des Projets en Attente / En Retard')
                .setColor(0xF59E0B)
                .setDescription('Voici les projets nécessitant un coup de boost :');

            late.forEach(p => {
                const diff = Math.max(0, (p.va_chapter || 0) - (p.steps?.trad || 0));
                const rush = p.is_rush ? '🚨 [RUSH] ' : '';
                embed.addFields({
                    name: `${rush}${p.title} (Retard : ${diff} chapitres)`,
                    value: `VA : **${p.va_chapter || 0}** | Trad actuel : **${p.steps?.trad || 0}**\nStaff : Trad: ${p.staff?.trad || '—'} | Check: ${p.staff?.check || '—'} | Edit: ${p.staff?.edit || '—'}`
                });
            });

            await interaction.reply({ embeds: [embed] });
        }
    } catch (err) {
        console.error(`Erreur commande ${commandName}:`, err);
        if (!interaction.replied && !interaction.deferred) {
            await interaction.reply({ content: '⚠️ Une erreur est survenue lors de l\'exécution de la commande.', ephemeral: true }).catch(() => {});
        }
    }
});

// Clics sur les Boutons
client.on('interactionCreate', async interaction => {
    if (!interaction.isButton()) return;

    try {
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

            if (!projects[slug].steps) {
                projects[slug].steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
            }

            const currentVal = projects[slug].steps[step] || 0;
            const newVal = currentVal + 1;
            projects[slug].steps[step] = newVal;
            saveProjects(projects);

            recordContribution(
                interaction.user.id,
                interaction.user.tag || interaction.user.username,
                slug,
                step,
                newVal
            );

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.update({
                embeds: [embed],
                components,
                files,
            });

            // Détermine qui doit être notifié pour la suite
            const nextRoles = {
                trad: { name: 'Check', mention: projects[slug].staff?.check },
                check: { name: 'Clean', mention: projects[slug].staff?.clean },
                clean: { name: 'Edit', mention: projects[slug].staff?.edit },
                edit: { name: 'QC', mention: projects[slug].staff?.qc },
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

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.update({
                embeds: [embed],
                components,
                files,
            });

            const alert = projects[slug].is_rush
                ? `🚨 **ATTENTION LE STAFF : Mode RUSH activé sur « ${projects[slug].title} » !** Projet prioritaire !`
                : `✅ Mode Rush désactivé pour « ${projects[slug].title} ».`;

            await interaction.followUp({ content: alert });
        }

        // Bouton Activer Tout (Toutes les séries)
        else if (customId === 'btn_roles_select_all') {
            await interaction.deferReply({ ephemeral: true });
            const member = interaction.member;
            const projects = loadProjects();
            const allProjectsRoleId = process.env.DISCORD_ALL_PROJECTS_ROLE_ID || '1535294276618158180';
            const panelRoleIds = getPanelRoleIds(interaction.guild, projects, allProjectsRoleId);

            const added = [];
            const alreadyHave = [];
            const failed = [];

            for (const roleId of panelRoleIds) {
                const role = interaction.guild.roles.cache.get(roleId);
                if (!role) continue;
                if (member.roles.cache.has(roleId)) {
                    alreadyHave.push(role);
                    continue;
                }
                if (!role.editable) {
                    failed.push({ role, reason: 'Hiérarchie Discord (rôle placé au-dessus du bot)' });
                    continue;
                }
                try {
                    await member.roles.add(roleId);
                    added.push(role);
                } catch (e) {
                    failed.push({ role, reason: e.message });
                }
            }

            let msg = `### 📚 Activation de toutes les séries\n\n`;
            if (added.length > 0) {
                msg += `✅ **${added.length} rôle(s) ajouté(s) :**\n` + added.map(r => `• <@&${r.id}>`).join('\n') + '\n\n';
            }
            if (alreadyHave.length > 0) {
                msg += `ℹ️ Vous aviez déjà **${alreadyHave.length}** rôle(s).\n\n`;
            }
            if (failed.length > 0) {
                msg += `⚠️ **${failed.length} rôle(s) non modifiables :**\n` +
                    failed.map(f => `• <@&${f.role.id}> (${f.reason})`).join('\n') +
                    `\n*Demandez à un administrateur de monter le rôle **Hidden Staff** dans les paramètres du serveur.*`;
            }
            await interaction.editReply({ content: msg });
        }

        // Bouton Tout Retirer
        else if (customId === 'btn_roles_clear_all') {
            await interaction.deferReply({ ephemeral: true });
            const member = interaction.member;
            const projects = loadProjects();
            const allProjectsRoleId = process.env.DISCORD_ALL_PROJECTS_ROLE_ID || '1535294276618158180';
            const panelRoleIds = getPanelRoleIds(interaction.guild, projects, allProjectsRoleId);

            const removed = [];
            const failed = [];

            for (const roleId of panelRoleIds) {
                const role = interaction.guild.roles.cache.get(roleId);
                if (!role || !member.roles.cache.has(roleId)) continue;
                if (!role.editable) {
                    failed.push({ role, reason: 'Hiérarchie Discord (rôle placé au-dessus du bot)' });
                    continue;
                }
                try {
                    await member.roles.remove(roleId);
                    removed.push(role);
                } catch (e) {
                    failed.push({ role, reason: e.message });
                }
            }

            let msg = `### ❌ Désactivation des notifications\n\n`;
            if (removed.length > 0) {
                msg += `🗑️ **${removed.length} rôle(s) retiré(s) :**\n` + removed.map(r => `• <@&${r.id}>`).join('\n') + '\n\n';
            } else {
                msg += `ℹ️ Vous n'aviez aucun rôle de série actif.\n\n`;
            }
            if (failed.length > 0) {
                msg += `⚠️ **${failed.length} rôle(s) non modifiables :**\n` +
                    failed.map(f => `• <@&${f.role.id}> (${f.reason})`).join('\n') +
                    `\n*Demandez à un administrateur de monter le rôle **Hidden Staff** dans les paramètres du serveur.*`;
            }
            await interaction.editReply({ content: msg });
        }
    } catch (err) {
        console.error('Erreur clic bouton:', err);
    }
});

// Menu déroulant de sélection des rôles de séries
client.on('interactionCreate', async interaction => {
    if (!interaction.isStringSelectMenu()) return;
    if (interaction.customId !== 'select_series_roles') return;

    try {
        await interaction.deferReply({ ephemeral: true });

        const member = interaction.member;
        const selectedRoleIds = interaction.values;
        const projects = loadProjects();
        const allProjectsRoleId = process.env.DISCORD_ALL_PROJECTS_ROLE_ID || '1535294276618158180';
        const panelRoleIds = getPanelRoleIds(interaction.guild, projects, allProjectsRoleId);

        const added = [];
        const removed = [];
        const failed = [];

        for (const roleId of panelRoleIds) {
            const isSelected = selectedRoleIds.includes(roleId);
            const hasRole = member.roles.cache.has(roleId);
            const role = interaction.guild.roles.cache.get(roleId);

            if (!role) continue;

            if (isSelected && !hasRole) {
                if (!role.editable) {
                    failed.push({ role, action: 'ajout', reason: 'Hiérarchie Discord (rôle placé au-dessus du bot)' });
                } else {
                    try {
                        await member.roles.add(roleId);
                        added.push(role);
                    } catch (e) {
                        failed.push({ role, action: 'ajout', reason: e.message });
                    }
                }
            } else if (!isSelected && hasRole) {
                if (!role.editable) {
                    failed.push({ role, action: 'retrait', reason: 'Hiérarchie Discord (rôle placé au-dessus du bot)' });
                } else {
                    try {
                        await member.roles.remove(roleId);
                        removed.push(role);
                    } catch (e) {
                        failed.push({ role, action: 'retrait', reason: e.message });
                    }
                }
            }
        }

        let replyText = '### 📕 Vos rôles de notification ont été mis à jour !\n\n';
        if (added.length > 0) {
            replyText += `✅ **Rôles ajoutés (${added.length}) :**\n` + added.map(r => `• <@&${r.id}>`).join('\n') + '\n\n';
        }
        if (removed.length > 0) {
            replyText += `🗑️ **Rôles retirés (${removed.length}) :**\n` + removed.map(r => `• <@&${r.id}>`).join('\n') + '\n\n';
        }
        if (added.length === 0 && removed.length === 0 && failed.length === 0) {
            replyText += 'ℹ️ Vos rôles correspondent déjà exactement à votre sélection.\n';
        }
        if (failed.length > 0) {
            replyText += `⚠️ **Certains rôles n'ont pas pu être modifiés (${failed.length}) :**\n` +
                failed.map(f => `• <@&${f.role.id}> (${f.reason})`).join('\n') +
                `\n*Demandez à un administrateur de monter le rôle **Hidden Staff** dans les paramètres du serveur.*`;
        }

        await interaction.editReply({ content: replyText });
    } catch (err) {
        console.error('Erreur select_series_roles:', err);
        if (interaction.deferred && !interaction.replied) {
            await interaction.editReply({ content: '⚠️ Une erreur est survenue lors de la mise à jour de vos rôles.' }).catch(() => {});
        }
    }
});

// Protection contre les rejets non interceptés
process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});
process.on('uncaughtException', (error) => {
    console.error('Uncaught Exception:', error);
});

// Connexion
if (process.env.DISCORD_BOT_TOKEN) {
    client.login(process.env.DISCORD_BOT_TOKEN).catch(err => {
        console.error('❌ Erreur de connexion bot:', err.message);
    });
}
