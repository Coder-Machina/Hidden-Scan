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
    PermissionFlagsBits,
} = require('discord.js');

const DATA_DIR = path.join(__dirname, 'data');
const PROJECTS_FILE = path.join(DATA_DIR, 'projects.json');
const STATS_FILE = path.join(DATA_DIR, 'stats.json');
const PANEL_FILE = path.join(DATA_DIR, 'panel.json');

// Assure la création du dossier data
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

// Variables en cache mémoire pour des performances maximales (accès instantané 0ms)
let projectsCache = null;
let statsCache = null;
let panelCache = null;

function loadPanelConfig() {
    if (panelCache !== null) return panelCache;
    try {
        if (!fs.existsSync(PANEL_FILE)) {
            panelCache = {};
            return panelCache;
        }
        panelCache = JSON.parse(fs.readFileSync(PANEL_FILE, 'utf8') || '{}');
        return panelCache;
    } catch (e) {
        panelCache = {};
        return panelCache;
    }
}

function savePanelConfig(data) {
    panelCache = data;
    fs.promises.writeFile(PANEL_FILE, JSON.stringify(data, null, 2), 'utf8').catch(err => {
        console.error('Erreur async savePanelConfig:', err.message);
    });
}

// Chargement & sauvegarde des projets locaux (Cache mémoire haute performance)
function loadProjects() {
    if (projectsCache !== null) return projectsCache;
    try {
        if (!fs.existsSync(PROJECTS_FILE)) {
            projectsCache = {};
            return projectsCache;
        }
        projectsCache = JSON.parse(fs.readFileSync(PROJECTS_FILE, 'utf8') || '{}');
        return projectsCache;
    } catch (e) {
        projectsCache = {};
        return projectsCache;
    }
}

function saveProjects(data) {
    projectsCache = data;
    fs.promises.writeFile(PROJECTS_FILE, JSON.stringify(data, null, 2), 'utf8').catch(err => {
        console.error('Erreur async saveProjects:', err.message);
    });
}

// Chargement & sauvegarde des stats du staff (Cache mémoire haute performance)
function loadStats() {
    if (statsCache !== null) return statsCache;
    try {
        if (!fs.existsSync(STATS_FILE)) {
            statsCache = [];
            return statsCache;
        }
        statsCache = JSON.parse(fs.readFileSync(STATS_FILE, 'utf8') || '[]');
        return statsCache;
    } catch (e) {
        statsCache = [];
        return statsCache;
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
    statsCache = stats;
    fs.promises.writeFile(STATS_FILE, JSON.stringify(stats, null, 2), 'utf8').catch(err => {
        console.error('Erreur async recordContribution:', err.message);
    });
}

function removeContribution(userId, projectKey, step, chapterNumber) {
    const stats = loadStats();
    for (let i = stats.length - 1; i >= 0; i--) {
        const item = stats[i];
        if (item.projectKey === projectKey && item.step === step && Number(item.chapterNumber) === Number(chapterNumber)) {
            stats.splice(i, 1);
            break;
        }
    }
    statsCache = stats;
    fs.promises.writeFile(STATS_FILE, JSON.stringify(stats, null, 2), 'utf8').catch(err => {
        console.error('Erreur async removeContribution:', err.message);
    });
}

// Map en mémoire pour l'anti-miss-click (confirmations éphémères) et rollbacks
const pendingConfirmations = new Map();
const pendingRollbacks = new Map();

// Nettoyage régulier des entrées expirées en mémoire
setInterval(() => {
    const now = Date.now();
    for (const [id, data] of pendingConfirmations.entries()) {
        if (now - data.createdAt > 5 * 60 * 1000) {
            pendingConfirmations.delete(id);
        }
    }
    for (const [id, data] of pendingRollbacks.entries()) {
        if (now - data.createdAt > 15 * 60 * 1000) {
            pendingRollbacks.delete(id);
        }
    }
}, 5 * 60 * 1000);

const ADMIN_IDS = (process.env.ADMIN_USER_IDS || '979449727047323668,1310979117172391940')
    .split(',')
    .map(id => id.trim())
    .filter(Boolean);

/**
 * Vérifie si un utilisateur/membre est administrateur (ID direct, rôle Administrateur Discord, ou propriétaire du serveur).
 */
function isUserAdmin(member, user = null, guild = null) {
    const userId = user?.id || member?.id || member?.user?.id;
    if (userId && ADMIN_IDS.includes(userId)) return true;
    if (guild && userId && guild.ownerId === userId) return true;
    if (member && member.permissions) {
        if (typeof member.permissions.has === 'function' && member.permissions.has(PermissionFlagsBits.Administrator)) {
            return true;
        }
        if (typeof member.permissions === 'string') {
            const perms = BigInt(member.permissions);
            const adminBit = BigInt(PermissionFlagsBits.Administrator);
            if ((perms & adminBit) === adminBit) return true;
        }
    }
    return false;
}

/**
 * Vérifie si un utilisateur/membre est autorisé à valider une étape de production.
 */
function canUserPerformStep(member, user, project, step, guild = null) {
    if (isUserAdmin(member, user, guild)) return { allowed: true, reason: 'admin' };

    const userId = user?.id || member?.id;
    if (!userId) return { allowed: false, reason: 'Utilisateur non identifié.' };

    const assigned = project.staff ? project.staff[step] : null;

    // 1. Si un membre précis est assigné à cette étape (ex: <@123456789>)
    if (assigned) {
        const matchUser = assigned.match(/<@!?(\d+)>/);
        if (matchUser && matchUser[1] === userId) {
            return { allowed: true, reason: 'assigned_user' };
        }

        const matchRole = assigned.match(/<@&(\d+)>/);
        if (matchRole && member?.roles?.cache?.has(matchRole[1])) {
            return { allowed: true, reason: 'assigned_role' };
        }
    }

    // 2. Permission Discord de gestion
    if (member?.permissions) {
        if (typeof member.permissions.has === 'function' &&
            (member.permissions.has(PermissionFlagsBits.ManageMessages) ||
             member.permissions.has(PermissionFlagsBits.ManageGuild))) {
            return { allowed: true, reason: 'staff_permission' };
        }
    }

    // 3. Vérification par rôles staff
    if (member?.roles?.cache) {
        const stepKeywords = {
            trad: ['trad', 'traducteur', 'translator'],
            check: ['check', 'correcteur', 'correction', 'proofreader'],
            clean: ['clean', 'cleaneur', 'cleaner'],
            edit: ['edit', 'editeur', 'éditeur', 'typesetter', 'lettreur'],
            qc: ['qc', 'quality', 'controle', 'contrôle'],
        };
        const keywords = stepKeywords[step] || [];
        const genericStaffKeywords = ['staff', 'responsable', 'lead', 'admin', 'modérateur', 'gestionnaire'];

        const hasRelevantRole = member.roles.cache.some(role => {
            const rName = role.name.toLowerCase();
            return keywords.some(k => rName.includes(k)) || genericStaffKeywords.some(k => rName.includes(k));
        });

        if (hasRelevantRole) {
            return { allowed: true, reason: 'staff_role' };
        }
    }

    if (assigned) {
        return {
            allowed: false,
            reason: `Cette étape est actuellement assignée à ${assigned}. Seul ce membre ou un administrateur peut la valider.`
        };
    }

    return {
        allowed: false,
        reason: `Tu ne possèdes pas de rôle staff correspondant à cette étape (${step.toUpperCase()}) sur ce serveur.`
    };
}

/**
 * Vérifie la cohérence du workflow scanlation pour éviter les sauts d'étapes incohérents.
 */
function validateWorkflowOrder(project, step, targetChapter, isAdmin = false) {
    if (isAdmin) return { valid: true };

    const steps = project.steps || { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
    const trad = steps.trad || 0;
    const check = steps.check || 0;
    const edit = steps.edit || 0;

    // Check ne doit pas dépasser Trad
    if (step === 'check' && targetChapter > trad) {
        return {
            valid: false,
            reason: `⚠️ **Ordre du workflow :** Impossible de valider le **Check Ch. ${targetChapter}** car la **Traduction** n'en est qu'au Ch. **${trad}**.\n*(La traduction doit être terminée avant la correction)*`
        };
    }

    // Edit ne doit pas dépasser le texte (max trad / check)
    if (step === 'edit') {
        const textReady = Math.max(check, trad);
        if (targetChapter > textReady) {
            return {
                valid: false,
                reason: `⚠️ **Ordre du workflow :** Impossible de valider l'**Édition Ch. ${targetChapter}** car la traduction n'est prête que jusqu'au Ch. **${textReady}**.\n*(Le texte doit être traduit/corrigé avant le lettrage)*`
            };
        }
    }

    // QC ne doit pas dépasser Edit
    if (step === 'qc' && targetChapter > edit) {
        return {
            valid: false,
            reason: `⚠️ **Ordre du workflow :** Impossible de valider le **QC Ch. ${targetChapter}** car l'**Édition** n'en est qu'au Ch. **${edit}**.\n*(Le chapitre doit être édité avant le contrôle qualité)*`
        };
    }

    return { valid: true };
}

/**
 * Récupère instantanément en mémoire les mentions des administrateurs du serveur.
 * 0ms de latence, aucun appel réseau Discord, évite tout timeout "L'application ne répond pas".
 */
function getAdminMentions(guild = null) {
    const adminSet = new Set(ADMIN_IDS);
    if (guild?.ownerId) {
        adminSet.add(guild.ownerId);
    }
    return Array.from(adminSet).map(id => `<@${id}>`).join(' ');
}


/**
 * Recherche robuste d'un projet par clé, titre ou slug normalisé.
 * Évite tout décalage entre la création de fiche et la configuration du staff.
 */
function findProjectKey(projects, input) {
    if (!input || typeof input !== 'string') return null;
    const cleanInput = input.trim();
    if (!cleanInput) return null;

    // 1. Correspondance clé directe
    if (projects[cleanInput]) return cleanInput;

    const lowerInput = cleanInput.toLowerCase();

    // 2. Correspondance directe insensible à la casse
    for (const key of Object.keys(projects)) {
        if (key.toLowerCase() === lowerInput) return key;
    }

    // 3. Slugification standard sans apostrophes (ex: The Returner's Road -> the-returners-road-to-retirement)
    const slugWithoutQuotes = lowerInput
        .replace(/['’]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');

    if (projects[slugWithoutQuotes]) return slugWithoutQuotes;

    // 4. Slugification standard avec tirets
    const slugStandard = lowerInput
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');

    if (projects[slugStandard]) return slugStandard;

    // 5. Comparaison exacte par titre (insensible à la casse)
    for (const [key, p] of Object.entries(projects)) {
        if (p.title && p.title.trim().toLowerCase() === lowerInput) {
            return key;
        }
    }

    // 6. Comparaison alphanumérique pure (ignore ponctuation, tirets, espaces)
    const alphaInput = lowerInput.replace(/[^a-z0-9]/g, '');
    if (alphaInput.length >= 3) {
        for (const [key, p] of Object.entries(projects)) {
            const alphaKey = key.replace(/[^a-z0-9]/g, '');
            const alphaTitle = (p.title || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            if (alphaKey === alphaInput || alphaTitle === alphaInput) {
                return key;
            }
        }

        // 7. Recherche par sous-chaîne (ex: "genius grandson" pour Genius Grandson of the Loan Shark King)
        for (const [key, p] of Object.entries(projects)) {
            const alphaKey = key.replace(/[^a-z0-9]/g, '');
            const alphaTitle = (p.title || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            if (alphaTitle.includes(alphaInput) || alphaKey.includes(alphaInput) || (alphaInput.length >= 6 && alphaInput.includes(alphaTitle))) {
                return key;
            }
        }
    }

    return null;
}

/**
 * Envoie les métadonnées et le staff du projet à l'API Laravel pour persistance intégrale en base
 */
async function syncProjectToSite(project) {
    if (!project || !project.slug) return;
    try {
        const apiUrl = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
        const secret = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';
        await fetch(`${apiUrl}/api/bot/save-fiche`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Bot-Token': secret,
            },
            body: JSON.stringify({
                slug: project.slug,
                title: project.title,
                drive_url: project.drive_url || null,
                va_url: project.va_url || null,
                va_chapter_number: project.va_chapter || 0,
                cover_image: project.cover_image || null,
                is_rush: !!project.is_rush,
                discord_role_id: project.discord_role_id || null,
                trad_user: project.staff?.trad || null,
                check_user: project.staff?.check || null,
                clean_user: project.staff?.clean || null,
                edit_user: project.staff?.edit || null,
                qc_user: project.staff?.qc || null,
                step_trad_chapter: project.steps?.trad || 0,
                step_check_chapter: project.steps?.check || 0,
                step_clean_chapter: project.steps?.clean || 0,
                step_edit_chapter: project.steps?.edit || 0,
                step_qc_chapter: project.steps?.qc || 0,
            }),
        });
    } catch (err) {
        console.warn(`[Sync API] Échec sync fiche ${project.slug}:`, err.message);
    }
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
    const steps = project.steps || { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
    const staff = project.staff || {};

    desc += `**— Avancement & Prochains Chapitres —**\n`;
    desc += `✏️ **Trad**  > \`[${steps.trad || 0}]\` ➔ **Prochain : Ch. ${(steps.trad || 0) + 1}** — ${staff.trad || '*Non assigné*'}\n`;
    desc += `✅ **Check** > \`[${steps.check || 0}]\` ➔ **Prochain : Ch. ${(steps.check || 0) + 1}** — ${staff.check || '*Non assigné*'}\n`;
    desc += `🧹 **Clean** > \`[${steps.clean || 0}]\` ➔ **Prochain : Ch. ${(steps.clean || 0) + 1}** — ${staff.clean || '*Non assigné*'}\n`;
    desc += `✏️ **Edit**  > \`[${steps.edit || 0}]\` ➔ **Prochain : Ch. ${(steps.edit || 0) + 1}** — ${staff.edit || '*Non assigné*'}\n`;
    desc += `🔍 **QC**    > \`[${steps.qc || 0}]\` ➔ **Prochain : Ch. ${(steps.qc || 0) + 1}** — ${staff.qc || '*Non assigné*'}\n\n`;

    // Compteurs
    desc += `**— Chapitres —**\n`;
    desc += `🇬🇧 **VA :** \` ${project.va_chapter || 0} \`   |   🇫🇷 **FR :** \` ${steps.qc || steps.edit || 0} \`\n`;

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
    const steps = project.steps || { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
    const nextTrad = (steps.trad || 0) + 1;
    const nextCheck = (steps.check || 0) + 1;
    const nextClean = (steps.clean || 0) + 1;
    const nextEdit = (steps.edit || 0) + 1;
    const nextQC = (steps.qc || 0) + 1;

    const row1 = new ActionRowBuilder().addComponents(
        new ButtonBuilder()
            .setCustomId(`step_trad_${project.slug}`)
            .setLabel(`Trad ➔ Ch. ${nextTrad}`)
            .setEmoji('✏️')
            .setStyle(ButtonStyle.Primary),
        new ButtonBuilder()
            .setCustomId(`step_check_${project.slug}`)
            .setLabel(`Check ➔ Ch. ${nextCheck}`)
            .setEmoji('✅')
            .setStyle(ButtonStyle.Success),
        new ButtonBuilder()
            .setCustomId(`step_clean_${project.slug}`)
            .setLabel(`Clean ➔ Ch. ${nextClean}`)
            .setEmoji('🧹')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_edit_${project.slug}`)
            .setLabel(`Edit ➔ Ch. ${nextEdit}`)
            .setEmoji('📝')
            .setStyle(ButtonStyle.Secondary),
        new ButtonBuilder()
            .setCustomId(`step_qc_${project.slug}`)
            .setLabel(`QC ➔ Ch. ${nextQC}`)
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

    if (project.va_url) {
        row2.addComponents(
            new ButtonBuilder()
                .setLabel('🌐 Ouvrir VA')
                .setStyle(ButtonStyle.Link)
                .setURL(project.va_url)
        );
    }

    return [row1, row2];
}

/**
 * Synchronise les mangas depuis l'API Laravel vers le stockage local du bot
 */
let syncPromise = null;

/**
 * Synchronise les mangas depuis l'API Laravel vers le cache et stockage local du bot
 * Déduplication des requêtes en vol pour éviter toute surcharge réseau.
 */
async function syncProjectsWithSite() {
    if (syncPromise) return syncPromise;
    syncPromise = doSyncProjectsWithSite().finally(() => {
        syncPromise = null;
    });
    return syncPromise;
}

async function doSyncProjectsWithSite() {
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

        // Élagage strict : seules les œuvres officielles traduites par la team (non externes) sont conservées dans le bot staff
        const siteTeamSlugs = new Set(data.mangas.filter(m => !m.is_external).map(m => m.slug));
        for (const slug of Object.keys(currentProjects)) {
            if (!siteTeamSlugs.has(slug) || currentProjects[slug].is_external) {
                delete currentProjects[slug];
                changed = true;
            }
        }

        for (const manga of data.mangas) {
            const slug = manga.slug;
            const isExternal = Boolean(manga.is_external);

            // Ignore les projets externes (seules les œuvres produites par la team sont gérées par le staff bot)
            if (isExternal) {
                if (currentProjects[slug]) {
                    delete currentProjects[slug];
                    changed = true;
                }
                continue;
            }

            const existing = currentProjects[slug] || {};

            const mergedStaff = {
                trad: manga.staff?.trad || existing.staff?.trad || null,
                check: manga.staff?.check || existing.staff?.check || null,
                clean: manga.staff?.clean || existing.staff?.clean || null,
                edit: manga.staff?.edit || existing.staff?.edit || null,
                qc: manga.staff?.qc || existing.staff?.qc || null,
            };

            const autoBase = (manga.latest_fr_chapter && manga.latest_fr_chapter > 0) ? manga.latest_fr_chapter : 0;
            const mergedSteps = {
                trad: (manga.steps?.trad !== undefined && manga.steps.trad > 0) ? manga.steps.trad : (existing.steps?.trad ?? autoBase),
                check: (manga.steps?.check !== undefined && manga.steps.check > 0) ? manga.steps.check : (existing.steps?.check ?? autoBase),
                clean: (manga.steps?.clean !== undefined && manga.steps.clean > 0) ? manga.steps.clean : (existing.steps?.clean ?? autoBase),
                edit: (manga.steps?.edit !== undefined && manga.steps.edit > 0) ? manga.steps.edit : (existing.steps?.edit ?? autoBase),
                qc: (manga.steps?.qc !== undefined && manga.steps.qc > 0) ? manga.steps.qc : (existing.steps?.qc ?? autoBase),
            };

            currentProjects[slug] = {
                ...existing,
                slug: manga.slug,
                title: manga.title ? manga.title.trim() : (existing.title || slug),
                is_external: isExternal,
                team_name: manga.team_name || existing.team_name || null,
                discord_role_id: isExternal ? null : (manga.discord_role_id || existing.discord_role_id || null),
                drive_url: manga.drive_url || existing.drive_url || null,
                va_url: manga.va_url || existing.va_url || null,
                va_chapter: (manga.va_chapter !== undefined && manga.va_chapter !== null) ? manga.va_chapter : (existing.va_chapter || 0),
                cover_url: manga.cover_url || existing.cover_url || null,
                cover_image: manga.cover_image || existing.cover_image || null,
                is_rush: Boolean(manga.is_rush !== undefined ? manga.is_rush : existing.is_rush),
                staff: mergedStaff,
                steps: mergedSteps,
            };
            changed = true;
        }

        if (changed) {
            saveProjects(currentProjects);
            console.log(`✅ Synchronisation réussie : ${Object.keys(currentProjects).length} projets disponibles en mémoire.`);
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
            .setDescription('Crée ou met à jour une fiche de projet (Drive, VA, Staff) directement depuis Discord')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
            .addStringOption(option =>
                option.setName('nom')
                    .setDescription('Nom ou sélection du projet (ex: The Returner\'s Road, Genius Grandson...)')
                    .setRequired(true)
                    .setAutocomplete(true)
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
            .addNumberOption(option =>
                option.setName('premier_chapitre')
                    .setDescription('Premier chapitre à produire (ex: 20 pour que le 1er clic valide le Ch. 20)')
                    .setRequired(false)
            )
            .addNumberOption(option =>
                option.setName('chapitre_actuel')
                    .setDescription('Dernier chapitre déjà fait / point de reprise (ex: 19 pour reprendre au 20)')
                    .setRequired(false)
            )
            .addStringOption(option =>
                option.setName('image')
                    .setDescription('Lien de l\'affiche/couverture du projet')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('trad')
                    .setDescription('Traducteur (Trad)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('check')
                    .setDescription('Correcteur (Check)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('clean')
                    .setDescription('Cleaneur (Clean)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('edit')
                    .setDescription('Éditeur / Lettrage (Edit)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('qc')
                    .setDescription('Contrôle Qualité (QC)')
                    .setRequired(false)
            ),
        new SlashCommandBuilder()
            .setName('config-staff')
            .setDescription('Assigne les membres du staff et met à jour les liens (Drive, VA, etc.) d\'un projet')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Projet concerné')
                    .setRequired(true)
                    .setAutocomplete(true)
            )
            .addNumberOption(option =>
                option.setName('chapitre_actuel')
                    .setDescription('Point de reprise global pour toutes les étapes (ex: 19 pour repartir au 20)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('trad')
                    .setDescription('Traducteur (Trad)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('check')
                    .setDescription('Correcteur (Check)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('clean')
                    .setDescription('Cleaneur (Clean)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('edit')
                    .setDescription('Éditeur / Lettrage (Edit)')
                    .setRequired(false)
            )
            .addUserOption(option =>
                option.setName('qc')
                    .setDescription('Contrôle Qualité (QC)')
                    .setRequired(false)
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
                option.setName('role')
                    .setDescription('Étape spécifique (optionnel)')
                    .setRequired(false)
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
                    .setDescription('Membre pour l\'étape spécifique')
                    .setRequired(false)
            ),
        new SlashCommandBuilder()
            .setName('reprise')
            .setDescription('Configure la reprise d\'un projet en indiquant le premier chapitre à produire')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Projet concerné')
                    .setRequired(true)
                    .setAutocomplete(true)
            )
            .addNumberOption(option =>
                option.setName('premier_chapitre')
                    .setDescription('Premier chapitre à produire (ex: 20 pour que le 1er clic valide le Ch. 20)')
                    .setRequired(true)
            )
            .addStringOption(option =>
                option.setName('etape')
                    .setDescription('Étape(s) concernée(s) (défaut: toutes les étapes)')
                    .setRequired(false)
                    .addChoices(
                        { name: '🌐 Toutes les étapes (Reprise globale)', value: 'all' },
                        { name: '✏️ Traduction seule', value: 'trad' },
                        { name: '✅ Correction (Check) seule', value: 'check' },
                        { name: '🧹 Clean seul', value: 'clean' },
                        { name: '📝 Édition seule', value: 'edit' },
                        { name: '🔍 QC seul', value: 'qc' },
                    )
            ),
        new SlashCommandBuilder()
            .setName('set-chapitre')
            .setDescription('Modifie le numéro de chapitre d\'une étape ou de toutes les étapes (reprise)')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
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
                        { name: '🌐 Toutes les étapes (Reprise globale)', value: 'all' },
                        { name: '✏️ Trad', value: 'trad' },
                        { name: '✅ Check', value: 'check' },
                        { name: '🧹 Clean', value: 'clean' },
                        { name: '📝 Edit', value: 'edit' },
                        { name: '🔍 QC', value: 'qc' },
                        { name: '🇬🇧 Dernier VA', value: 'va' },
                    )
            )
            .addNumberOption(option =>
                option.setName('numero')
                    .setDescription('Dernier chapitre fait (ex: 19 pour que le prochain clic valide le 20)')
                    .setRequired(true)
            ),
        new SlashCommandBuilder()
            .setName('rush')
            .setDescription('Active ou désactive le mode Rush pour un projet prioritaire')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
            .addStringOption(option =>
                option.setName('manga')
                    .setDescription('Nom du projet')
                    .setRequired(true)
                    .setAutocomplete(true)
            ),
        new SlashCommandBuilder()
            .setName('role-projet')
            .setDescription('Associe un rôle Discord de notification à une œuvre')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
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
            .setDescription('Vérifie et crée automatiquement les rôles Discord manquants pour tous les mangas')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator),
        new SlashCommandBuilder()
            .setName('panneau-roles')
            .setDescription('Déploie ou met à jour le panneau interactif de sélection des rôles de séries')
            .setDefaultMemberPermissions(PermissionFlagsBits.Administrator)
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

// Autocomplétion dynamique des projets (uniquement les œuvres produites/traduites par notre team)
client.on('interactionCreate', async interaction => {
    if (!interaction.isAutocomplete()) return;

    try {
        const focusedValue = interaction.options.getFocused().toLowerCase();
        const projects = loadProjects();
        const list = Object.values(projects);

        const filtered = list
            .filter(p => !p.is_external && (p.title.toLowerCase().includes(focusedValue) || p.slug.toLowerCase().includes(focusedValue)))
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

    // Commandes de configuration strictement réservées aux administrateurs
    const ADMIN_COMMANDS = [
        'creer-fiche',
        'config-staff',
        'set-chapitre',
        'reprise',
        'rush',
        'role-projet',
        'sync-roles',
        'panneau-roles',
    ];

    if (ADMIN_COMMANDS.includes(commandName)) {
        if (!isUserAdmin(interaction.member, interaction.user, interaction.guild)) {
            return await interaction.reply({
                content: '❌ **Accès refusé** : Cette commande est strictement réservée aux administrateurs du serveur.',
                ephemeral: true,
            });
        }
    }

    // Acknowledge immédiat (< 50ms) pour éliminer tout risque d'expiration des 3 secondes Discord
    const isEphemeral = commandName === 'sync-roles' || commandName === 'panneau-roles';
    await interaction.deferReply({ ephemeral: isEphemeral });

    let projects = loadProjects();

    try {
        // /creer-fiche
        if (commandName === 'creer-fiche') {
            const nom = interaction.options.getString('nom');
            const drive = interaction.options.getString('drive');
            const va = interaction.options.getString('va');
            const vaChap = interaction.options.getNumber('va_chapitre');
            const img = interaction.options.getString('image');

            const tradUser = interaction.options.getUser('trad');
            const checkUser = interaction.options.getUser('check');
            const cleanUser = interaction.options.getUser('clean');
            const editUser = interaction.options.getUser('edit');
            const qcUser = interaction.options.getUser('qc');

            // Recherche si le projet existe déjà (par slug, titre, ou variante)
            const matchedKey = findProjectKey(projects, nom);
            const slug = matchedKey || nom.toLowerCase().replace(/['’]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
            const existing = (matchedKey ? projects[matchedKey] : projects[slug]) || {};

            const finalTitle = (existing.title && (nom === slug || nom === matchedKey))
                ? existing.title
                : (nom && nom !== slug ? nom.trim() : (existing.title || nom));

            const staff = {
                trad: tradUser ? `<@${tradUser.id}>` : (existing.staff?.trad || null),
                check: checkUser ? `<@${checkUser.id}>` : (existing.staff?.check || null),
                clean: cleanUser ? `<@${cleanUser.id}>` : (existing.staff?.clean || null),
                edit: editUser ? `<@${editUser.id}>` : (existing.staff?.edit || null),
                qc: qcUser ? `<@${qcUser.id}>` : (existing.staff?.qc || null),
            };

            const premierChap = interaction.options.getNumber('premier_chapitre');
            const chapActuel = interaction.options.getNumber('chapitre_actuel');

            let defaultCh = 0;
            let repriseNotice = '';

            if (premierChap !== null && premierChap !== undefined && premierChap >= 1) {
                defaultCh = Math.max(0, Math.floor(premierChap) - 1);
                repriseNotice = `\n🔄 **Reprise configurée :** Démarrage au **Chapitre ${premierChap}** (${defaultCh} chapitres antérieurs comptabilisés). Le premier clic sur chaque bouton validera le **Ch. ${premierChap}**.`;
            } else if (chapActuel !== null && chapActuel !== undefined && chapActuel >= 0) {
                defaultCh = chapActuel;
                repriseNotice = `\n🔄 **Point de départ configuré :** Les étapes partent du chapitre **${chapActuel}** (le prochain clic validera le chapitre **${chapActuel + 1}**).`;
            } else if (existing.steps && existing.steps.trad !== undefined) {
                defaultCh = existing.steps.trad;
            }

            const steps = existing.steps ? { ...existing.steps } : {
                trad: defaultCh,
                check: defaultCh,
                clean: defaultCh,
                edit: defaultCh,
                qc: defaultCh,
            };

            if ((premierChap !== null && premierChap !== undefined) || (chapActuel !== null && chapActuel !== undefined)) {
                steps.trad = defaultCh;
                steps.check = defaultCh;
                steps.clean = defaultCh;
                steps.edit = defaultCh;
                steps.qc = defaultCh;
            }

            projects[slug] = {
                ...existing,
                slug,
                title: finalTitle,
                drive_url: (drive && drive.trim()) ? drive.trim() : (existing.drive_url || null),
                va_url: (va && va.trim()) ? va.trim() : (existing.va_url || null),
                va_chapter: (vaChap !== null && vaChap !== undefined && vaChap >= 0) ? vaChap : (existing.va_chapter || 0),
                cover_url: (img && img.trim()) ? img.trim() : (existing.cover_url || null),
                cover_image: existing.cover_image || null,
                is_rush: existing.is_rush || false,
                is_external: existing.is_external || false,
                discord_role_id: existing.discord_role_id || null,
                team_name: existing.team_name || null,
                staff,
                steps,
            };

            saveProjects(projects);
            syncProjectToSite(projects[slug]);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.editReply({
                content: `🎉 **Fiche enregistrée avec succès pour « ${projects[slug].title} » !**${repriseNotice}\nToutes les informations sont synchronisées avec le site et le Drive.`,
                embeds: [embed],
                components,
                files,
            });
        }

        // /config-staff
        else if (commandName === 'config-staff') {
            const rawManga = interaction.options.getString('manga');
            let matchedKey = findProjectKey(projects, rawManga);

            if (!matchedKey) {
                await syncProjectsWithSite();
                projects = loadProjects();
                matchedKey = findProjectKey(projects, rawManga);
            }

            if (!matchedKey || !projects[matchedKey]) {
                return await interaction.editReply({ content: `❌ Projet « ${rawManga} » introuvable. Utilisez \`/creer-fiche\` d'abord pour l'enregistrer.` });
            }

            const slug = matchedKey;
            const project = projects[slug];

            if (!project.staff) {
                project.staff = { trad: null, check: null, clean: null, edit: null, qc: null };
            }

            const tradUser = interaction.options.getUser('trad');
            const checkUser = interaction.options.getUser('check');
            const cleanUser = interaction.options.getUser('clean');
            const editUser = interaction.options.getUser('edit');
            const qcUser = interaction.options.getUser('qc');
            const singleRole = interaction.options.getString('role');
            const singleMember = interaction.options.getUser('membre');

            const drive = interaction.options.getString('drive');
            const va = interaction.options.getString('va');
            const vaChap = interaction.options.getNumber('va_chapitre');
            const chapActuel = interaction.options.getNumber('chapitre_actuel');

            if (drive && drive.trim()) project.drive_url = drive.trim();
            if (va && va.trim()) project.va_url = va.trim();
            if (vaChap !== null && vaChap !== undefined && vaChap >= 0) project.va_chapter = vaChap;

            if (chapActuel !== null && chapActuel !== undefined && chapActuel >= 0) {
                if (!project.steps) {
                    project.steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
                }
                project.steps.trad = chapActuel;
                project.steps.check = chapActuel;
                project.steps.clean = chapActuel;
                project.steps.edit = chapActuel;
                project.steps.qc = chapActuel;
            }

            const assignments = {};
            if (tradUser) assignments.trad = `<@${tradUser.id}>`;
            if (checkUser) assignments.check = `<@${checkUser.id}>`;
            if (cleanUser) assignments.clean = `<@${cleanUser.id}>`;
            if (editUser) assignments.edit = `<@${editUser.id}>`;
            if (qcUser) assignments.qc = `<@${qcUser.id}>`;
            if (singleRole && singleMember) assignments[singleRole] = `<@${singleMember.id}>`;

            const hasLinkUpdate = Boolean((drive && drive.trim()) || (va && va.trim()) || (vaChap !== null && vaChap !== undefined) || (chapActuel !== null && chapActuel !== undefined));

            if (Object.keys(assignments).length === 0 && !hasLinkUpdate) {
                return await interaction.editReply({
                    content: '⚠️ Veuillez renseigner au moins un membre, un lien ou un chapitre de reprise (ex: `trad: @Membre`, `chapitre_actuel: 19`, `drive: https://...`, etc.).',
                });
            }

            for (const [stepKey, memberMention] of Object.entries(assignments)) {
                project.staff[stepKey] = memberMention;
            }
            saveProjects(projects);
            syncProjectToSite(project);

            const roleLabels = {
                trad: '✏️ Traduction (Trad)',
                check: '✅ Correction (Check)',
                clean: '🧹 Clean',
                edit: '📝 Édition (Edit)',
                qc: '🔍 Contrôle Qualité (QC)',
            };

            const summaryList = Object.entries(assignments)
                .map(([stepKey, memberMention]) => `• ${roleLabels[stepKey] || stepKey.toUpperCase()} : ${memberMention}`)
                .join('\n');

            const coverInfo = resolveProjectCover(project);
            const embed = buildFicheEmbed(project, coverInfo);
            const components = buildFicheComponents(project);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            let replyMsg = `✅ **Configuration mise à jour pour « ${project.title} » !**`;
            if (chapActuel !== null && chapActuel !== undefined && chapActuel >= 0) {
                replyMsg += `\n🔄 **Point de reprise défini :** Chapitre **${chapActuel}** (le prochain clic validera le chapitre **${chapActuel + 1}**).`;
            }
            if (summaryList) replyMsg += `\n${summaryList}`;

            await interaction.editReply({
                content: replyMsg,
                embeds: [embed],
                components,
                files,
            });
        }

        // /role-projet
        else if (commandName === 'role-projet') {
            const rawManga = interaction.options.getString('manga');
            const role = interaction.options.getRole('role');

            let slug = findProjectKey(projects, rawManga);
            if (!slug) {
                await syncProjectsWithSite();
                projects = loadProjects();
                slug = findProjectKey(projects, rawManga) || rawManga;
            }

            if (!projects[slug]) {
                return await interaction.editReply({ content: '❌ Projet introuvable.' });
            }

            projects[slug].discord_role_id = role.id;
            saveProjects(projects);
            syncProjectToSite(projects[slug]);

            // Sync avec l'API Laravel non-bloquante
            const apiUrl = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
            const secret = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';
            fetch(`${apiUrl}/api/bot/link-role`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Bot-Token': secret,
                },
                body: JSON.stringify({ slug, role_id: role.id }),
            }).catch(e => {
                console.error('Erreur link-role API:', e.message);
            });

            await interaction.editReply({
                content: `✅ Le rôle <@&${role.id}> est désormais associé aux annonces de **${projects[slug].title}** ! Les sorties de chapitres mentionneront ce rôle avec un badge cliquable.`,
            });
        }

        // /sync-roles
        else if (commandName === 'sync-roles') {
            await syncProjectsWithSite();
            await ensureProjectRoles();
            await interaction.editReply({
                content: '✅ Tous les rôles Discord des projets ont été vérifiés et synchronisés avec succès avec le site !',
            });
        }

        // /panneau-roles
        else if (commandName === 'panneau-roles') {
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

        // /reprise
        else if (commandName === 'reprise') {
            const rawManga = interaction.options.getString('manga');
            const premierChap = interaction.options.getNumber('premier_chapitre');
            const etape = interaction.options.getString('etape') || 'all';

            let slug = findProjectKey(projects, rawManga);
            if (!slug) {
                await syncProjectsWithSite();
                projects = loadProjects();
                slug = findProjectKey(projects, rawManga) || rawManga;
            }

            if (!projects[slug]) {
                return await interaction.editReply({ content: '❌ Projet introuvable.' });
            }

            if (!projects[slug].steps) {
                projects[slug].steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
            }

            const dernierFait = Math.max(0, Math.floor(premierChap) - 1);

            if (etape === 'all') {
                projects[slug].steps = {
                    trad: dernierFait,
                    check: dernierFait,
                    clean: dernierFait,
                    edit: dernierFait,
                    qc: dernierFait,
                };
            } else {
                projects[slug].steps[etape] = dernierFait;
            }
            saveProjects(projects);
            syncProjectToSite(projects[slug]);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            const stepLabels = {
                trad: 'TRADUCTION (Trad)',
                check: 'CORRECTION (Check)',
                clean: 'CLEAN',
                edit: 'ÉDITION (Edit)',
                qc: 'QC',
            };

            const labelMsg = etape === 'all'
                ? `🔄 **Reprise globale configurée pour « ${projects[slug].title} » !**\n`
                    + `• État initialisé avec **${dernierFait}** chapitres déjà existants.\n`
                    + `👉 **Premier chapitre à produire : Chapitre ${premierChap}**\n`
                    + `👉 Le prochain clic sur n'importe quel bouton validera directement le **Chapitre ${premierChap}**.`
                : `🔄 **Reprise configurée pour ${stepLabels[etape] || etape.toUpperCase()} sur « ${projects[slug].title} » !**\n`
                    + `• Étape initialisée avec **${dernierFait}** chapitres faits.\n`
                    + `👉 Le prochain clic sur ce bouton validera directement le **Chapitre ${premierChap}**.`;

            await interaction.editReply({
                content: labelMsg,
                embeds: [embed],
                components,
                files,
            });
        }

        // /set-chapitre
        else if (commandName === 'set-chapitre') {
            const rawManga = interaction.options.getString('manga');
            const role = interaction.options.getString('role');
            const numero = interaction.options.getNumber('numero');

            let slug = findProjectKey(projects, rawManga);
            if (!slug) {
                await syncProjectsWithSite();
                projects = loadProjects();
                slug = findProjectKey(projects, rawManga) || rawManga;
            }

            if (!projects[slug]) {
                return await interaction.editReply({ content: '❌ Projet introuvable.' });
            }

            if (!projects[slug].steps) {
                projects[slug].steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
            }

            if (role === 'all') {
                projects[slug].steps = {
                    trad: numero,
                    check: numero,
                    clean: numero,
                    edit: numero,
                    qc: numero,
                };
            } else if (role === 'va') {
                projects[slug].va_chapter = numero;
            } else {
                projects[slug].steps[role] = numero;
            }
            saveProjects(projects);
            syncProjectToSite(projects[slug]);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            const labelMsg = role === 'all'
                ? `🌐 **Toutes les étapes** configurées au chapitre **${numero}** sur **${projects[slug].title}** !\n👉 **Reprise :** Le prochain clic sur n'importe quel bouton validera le chapitre **${numero + 1}**.`
                : `✅ Chapitre pour **${role.toUpperCase()}** mis à jour à **${numero}** sur **${projects[slug].title}**.\n👉 Le prochain clic validera le chapitre **${numero + 1}**.`;

            await interaction.editReply({
                content: labelMsg,
                embeds: [embed],
                components,
                files,
            });
        }

        // /fiche
        else if (commandName === 'fiche') {
            const rawManga = interaction.options.getString('manga');
            let slug = findProjectKey(projects, rawManga);

            if (!slug) {
                await syncProjectsWithSite();
                projects = loadProjects();
                slug = findProjectKey(projects, rawManga) || rawManga;
            }

            if (!projects[slug]) {
                return await interaction.editReply({
                    content: `❌ Projet « ${rawManga} » non trouvé. Demandez à un administrateur d'initialiser sa fiche avec \`/creer-fiche\` !`,
                });
            }

            const project = projects[slug];
            const coverInfo = resolveProjectCover(project);
            const embed = buildFicheEmbed(project, coverInfo);
            const components = buildFicheComponents(project);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.editReply({
                embeds: [embed],
                components,
                files,
            });
        }

        // /rush
        else if (commandName === 'rush') {
            const rawManga = interaction.options.getString('manga');
            let slug = findProjectKey(projects, rawManga);

            if (!slug) {
                await syncProjectsWithSite();
                projects = loadProjects();
                slug = findProjectKey(projects, rawManga) || rawManga;
            }

            if (!projects[slug]) {
                return await interaction.editReply({ content: '❌ Projet introuvable.' });
            }

            projects[slug].is_rush = !projects[slug].is_rush;
            saveProjects(projects);
            syncProjectToSite(projects[slug]);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            const status = projects[slug].is_rush ? '🚨 **MODE RUSH ACTIVÉ**' : '✅ Mode Rush désactivé';
            await interaction.editReply({
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
                return await interaction.editReply('📊 Aucun chapitre enregistré pour l\'instant. Cliquez sur les boutons des fiches pour comptabiliser le travail !');
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

            await interaction.editReply({ embeds: [embed] });
        }

        // /agenda
        else if (commandName === 'agenda') {
            const list = Object.values(projects);
            const late = list.filter(p => p.is_rush || ((p.va_chapter || 0) - (p.steps?.trad || 0)) >= 3);

            if (late.length === 0) {
                return await interaction.editReply('🎉 **Tout est nickel !** Aucun projet en retard ou en mode rush.');
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

            await interaction.editReply({ embeds: [embed] });
        }
    } catch (err) {
        console.error(`Erreur commande ${commandName}:`, err);
        if (interaction.deferred && !interaction.replied) {
            await interaction.editReply({ content: '⚠️ Une erreur est survenue lors de l\'exécution de la commande.' }).catch(() => {});
        } else if (!interaction.replied) {
            await interaction.reply({ content: '⚠️ Une erreur est survenue.', ephemeral: true }).catch(() => {});
        }
    }
});

// Clics sur les Boutons
client.on('interactionCreate', async interaction => {
    if (!interaction.isButton()) return;

    try {
        const customId = interaction.customId;

        // Boutons d'étape (Trad, Check, Clean, Edit, QC) - Anti Miss-Click avec confirmation
        if (customId.startsWith('step_')) {
            const parts = customId.split('_');
            const step = parts[1]; // trad, check, clean, edit, qc
            const rawSlug = parts.slice(2).join('_');

            const projects = loadProjects();
            let slug = findProjectKey(projects, rawSlug);
            if (!slug) {
                await syncProjectsWithSite();
                slug = findProjectKey(projects, rawSlug) || rawSlug;
            }

            if (!projects[slug]) {
                return await interaction.reply({ content: '❌ Projet introuvable.', ephemeral: true });
            }

            // Vérification stricte des permissions staff / assignation
            const perm = canUserPerformStep(interaction.member, interaction.user, projects[slug], step, interaction.guild);
            if (!perm.allowed) {
                return await interaction.reply({
                    content: `❌ **Action refusée :** ${perm.reason}`,
                    ephemeral: true,
                });
            }

            if (!projects[slug].steps) {
                projects[slug].steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
            }

            const currentVal = projects[slug].steps[step] || 0;
            const targetChapter = currentVal + 1;

            // Vérification de l'ordre du workflow (Check après Trad, QC après Edit, etc.)
            const isAdmin = isUserAdmin(interaction.member, interaction.user, interaction.guild);
            const orderCheck = validateWorkflowOrder(projects[slug], step, targetChapter, isAdmin);
            if (!orderCheck.valid) {
                return await interaction.reply({
                    content: orderCheck.reason,
                    ephemeral: true,
                });
            }

            const confirmId = `${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
            pendingConfirmations.set(confirmId, {
                userId: interaction.user.id,
                slug,
                step,
                targetChapter,
                channelId: interaction.channelId,
                messageId: interaction.message?.id || null,
                createdAt: Date.now(),
            });

            const stepLabels = {
                trad: 'TRADUCTION (Trad)',
                check: 'CORRECTION (Check)',
                clean: 'CLEAN',
                edit: 'ÉDITION (Edit)',
                qc: 'CONTRÔLE QUALITÉ (QC)',
            };
            const label = stepLabels[step] || step.toUpperCase();

            const confirmRow = new ActionRowBuilder().addComponents(
                new ButtonBuilder()
                    .setCustomId(`confirm_step_${confirmId}`)
                    .setLabel(`Valider le Ch. ${targetChapter}`)
                    .setEmoji('✅')
                    .setStyle(ButtonStyle.Success),
                new ButtonBuilder()
                    .setCustomId(`cancel_step_${confirmId}`)
                    .setLabel('Annuler (Fausse manip)')
                    .setEmoji('❌')
                    .setStyle(ButtonStyle.Secondary)
            );

            return await interaction.reply({
                content: `🛡️ **Confirmation requise (Anti Miss-Click)**\n\nTu t'apprêtes à valider l'étape **${label}** pour le **Chapitre ${targetChapter}** de **${projects[slug].title}**.\n\n👉 *Confirme uniquement si ce chapitre est bien terminé et prêt pour la suite.*`,
                components: [confirmRow],
                ephemeral: true,
            });
        }

        // Annulation d'un clic (Fausse manip)
        else if (customId.startsWith('cancel_step_')) {
            const confirmId = customId.replace('cancel_step_', '');
            pendingConfirmations.delete(confirmId);
            return await interaction.update({
                content: '❌ **Action annulée**. Aucun chapitre n\'a été comptabilisé et aucune notification n\'a été envoyée.',
                components: [],
            });
        }

        // Confirmation de validation d'étape
        else if (customId.startsWith('confirm_step_')) {
            const confirmId = customId.replace('confirm_step_', '');
            const data = pendingConfirmations.get(confirmId);

            if (!data) {
                return await interaction.update({
                    content: '⚠️ Cette confirmation a expiré ou a déjà été traitée.',
                    components: [],
                });
            }

            if (interaction.user.id !== data.userId) {
                return await interaction.reply({
                    content: '❌ Seul l\'utilisateur ayant cliqué sur l\'étape peut confirmer cette action.',
                    ephemeral: true,
                });
            }

            pendingConfirmations.delete(confirmId);

            const { slug, step, targetChapter, channelId, messageId } = data;
            const projects = loadProjects();

            if (!projects[slug]) {
                return await interaction.update({
                    content: '❌ Projet introuvable.',
                    components: [],
                });
            }

            if (!projects[slug].steps) {
                projects[slug].steps = { trad: 0, check: 0, clean: 0, edit: 0, qc: 0 };
            }

            projects[slug].steps[step] = targetChapter;
            saveProjects(projects);

            recordContribution(
                interaction.user.id,
                interaction.user.tag || interaction.user.username,
                slug,
                step,
                targetChapter
            );

            // Synchronisation asynchrone non-bloquante avec l'API Laravel
            const apiUrl = process.env.API_BASE_URL || 'http://127.0.0.1:8000';
            const secret = process.env.DISCORD_BOT_SECRET || 'hiddenscan-secret-bot-key';
            fetch(`${apiUrl}/api/bot/step-done`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Bot-Token': secret,
                },
                body: JSON.stringify({
                    slug,
                    step,
                    chapter_number: targetChapter,
                    discord_user_id: interaction.user.id,
                    discord_user_name: interaction.user.tag || interaction.user.username,
                }),
            }).catch(err => {
                console.warn(`[Sync API] Échec step-done ${slug}:`, err.message);
            });

            const stepLabels = {
                trad: 'TRADUCTION (Trad)',
                check: 'CORRECTION (Check)',
                clean: 'CLEAN',
                edit: 'ÉDITION (Edit)',
                qc: 'CONTRÔLE QUALITÉ (QC)',
            };

            // Mise à jour de la réponse éphémère
            await interaction.update({
                content: `✅ **Chapitre ${targetChapter} (${stepLabels[step]}) validé avec succès pour « ${projects[slug].title} » !**`,
                components: [],
            });

            // Mise à jour de la fiche d'origine dans le salon
            if (channelId && messageId) {
                try {
                    const channel = await client.channels.fetch(channelId).catch(() => null);
                    if (channel) {
                        const ficheMsg = await channel.messages.fetch(messageId).catch(() => null);
                        if (ficheMsg) {
                            const coverInfo = resolveProjectCover(projects[slug]);
                            const embed = buildFicheEmbed(projects[slug], coverInfo);
                            const components = buildFicheComponents(projects[slug]);
                            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];
                            await ficheMsg.edit({
                                embeds: [embed],
                                components,
                                files,
                            });
                        }
                    }
                } catch (err) {
                    console.warn('Erreur mise à jour message fiche:', err.message);
                }
            }

            // Bouton de Rollback (15 minutes) sur l'annonce publique
            const rollbackId = `${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
            const rollbackRow = new ActionRowBuilder().addComponents(
                new ButtonBuilder()
                    .setCustomId(`rollback_step_${rollbackId}`)
                    .setLabel(`Annuler (Rollback Ch. ${targetChapter})`)
                    .setEmoji('↩️')
                    .setStyle(ButtonStyle.Secondary)
            );

            // Notification publique du staff dans le salon
            const adminMentions = getAdminMentions(interaction.guild);
            let nextAction = '';
            let targetMention = null;

            if (step === 'trad') {
                nextAction = 'la **Correction (Check)**';
                targetMention = projects[slug].staff?.check || null;
            } else if (step === 'clean' || step === 'check') {
                nextAction = 'l\'**Édition (Edit)**';
                targetMention = projects[slug].staff?.edit || null;
            } else if (step === 'edit' || step === 'qc') {
                nextAction = 'la **Publication**';
                targetMention = adminMentions;
            }

            let publicMsg = '';
            if (step === 'edit') {
                publicMsg = `🎉 <@${interaction.user.id}> vient de terminer l'**ÉDITION (Edit)** pour le chapitre **${targetChapter}** de **${projects[slug].title}** !\n🚀 **Le chapitre est terminé et prêt à être publié !**\n📢 Notification admins : ${adminMentions}`;
            } else if (step === 'qc') {
                publicMsg = `🎉 <@${interaction.user.id}> vient de valider le **QC** pour le chapitre **${targetChapter}** de **${projects[slug].title}** !\n🚀 **Le chapitre est validé et prêt à être publié !**\n📢 Notification admins : ${adminMentions}`;
            } else {
                publicMsg = `🎉 <@${interaction.user.id}> vient de terminer la **${stepLabels[step]}** pour le chapitre **${targetChapter}** de **${projects[slug].title}** !`;
                if (targetMention) {
                    publicMsg += `\n👉 C'est ton tour pour ${nextAction} : ${targetMention}`;
                } else {
                    publicMsg += `\n👉 Prochaine étape : ${nextAction} *(aucun membre assigné pour le moment)*`;
                }
            }

            const targetChannel = interaction.channel || (await client.channels.fetch(channelId).catch(() => null));
            if (targetChannel) {
                const publicAnnounce = await targetChannel.send({
                    content: publicMsg,
                    components: [rollbackRow],
                    allowedMentions: { parse: ['users', 'roles'] },
                }).catch(err => {
                    console.warn('Erreur envoi notification publique:', err.message);
                    return null;
                });

                if (publicAnnounce) {
                    pendingRollbacks.set(rollbackId, {
                        userId: interaction.user.id,
                        slug,
                        step,
                        targetChapter,
                        channelId,
                        ficheMessageId: messageId,
                        announcementMessageId: publicAnnounce.id,
                        createdAt: Date.now(),
                    });
                }
            }
        }

        // Bouton d'annulation (Rollback dans les 15 minutes)
        else if (customId.startsWith('rollback_step_')) {
            const rollbackId = customId.replace('rollback_step_', '');
            const data = pendingRollbacks.get(rollbackId);

            if (!data) {
                return await interaction.reply({
                    content: '⚠️ Le délai d\'annulation (15 minutes) a expiré pour cette action ou elle a déjà été annulée.',
                    ephemeral: true,
                });
            }

            const isAdmin = isUserAdmin(interaction.member, interaction.user, interaction.guild);
            if (interaction.user.id !== data.userId && !isAdmin) {
                return await interaction.reply({
                    content: `❌ Seul <@${data.userId}> ou un administrateur peut annuler cette validation.`,
                    ephemeral: true,
                });
            }

            pendingRollbacks.delete(rollbackId);

            const { slug, step, targetChapter, channelId, ficheMessageId } = data;
            const projects = loadProjects();

            const previousChapter = Math.max(0, targetChapter - 1);
            if (projects[slug] && projects[slug].steps) {
                projects[slug].steps[step] = previousChapter;
                saveProjects(projects);
            }

            removeContribution(data.userId, slug, step, targetChapter);

            const stepLabels = {
                trad: 'TRADUCTION (Trad)',
                check: 'CORRECTION (Check)',
                clean: 'CLEAN',
                edit: 'ÉDITION (Edit)',
                qc: 'CONTRÔLE QUALITÉ (QC)',
            };

            // Mise à jour de la fiche d'origine
            if (channelId && ficheMessageId) {
                try {
                    const channel = await client.channels.fetch(channelId).catch(() => null);
                    if (channel) {
                        const ficheMsg = await channel.messages.fetch(ficheMessageId).catch(() => null);
                        if (ficheMsg && projects[slug]) {
                            const coverInfo = resolveProjectCover(projects[slug]);
                            const embed = buildFicheEmbed(projects[slug], coverInfo);
                            const components = buildFicheComponents(projects[slug]);
                            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];
                            await ficheMsg.edit({
                                embeds: [embed],
                                components,
                                files,
                            });
                        }
                    }
                } catch (err) {
                    console.warn('Erreur fiche update lors du rollback:', err.message);
                }
            }

            // Mise à jour du message d'annonce public
            await interaction.update({
                content: `↩️ ~~Validation Ch. ${targetChapter} (${stepLabels[step] || step.toUpperCase()}) par <@${data.userId}>~~\n⚠️ **Action annulée par <@${interaction.user.id}>.** Le compteur de l'étape est revenu au Ch. **${previousChapter}**.`,
                components: [],
                allowedMentions: { parse: [] },
            });
        }

        // Bouton Rush (Réservé aux administrateurs)
        else if (customId.startsWith('toggle_rush_')) {
            if (!isUserAdmin(interaction.member, interaction.user, interaction.guild)) {
                return await interaction.reply({
                    content: '❌ **Action refusée** : Seuls les administrateurs peuvent activer ou désactiver le mode Rush.',
                    ephemeral: true,
                });
            }

            await interaction.deferUpdate().catch(e => console.warn('deferUpdate warning:', e.message));

            const slug = customId.replace('toggle_rush_', '');
            const projects = loadProjects();
            if (!projects[slug]) return;

            projects[slug].is_rush = !projects[slug].is_rush;
            saveProjects(projects);

            // Sync API en tâche de fond
            syncProjectToSite(projects[slug]);

            const coverInfo = resolveProjectCover(projects[slug]);
            const embed = buildFicheEmbed(projects[slug], coverInfo);
            const components = buildFicheComponents(projects[slug]);
            const files = (coverInfo && coverInfo.isAttachment) ? [coverInfo.attachment] : [];

            await interaction.editReply({
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

            const toAdd = [];
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
                toAdd.push(role);
            }

            if (toAdd.length > 0) {
                try {
                    await member.roles.add(toAdd.map(r => r.id));
                } catch (e) {
                    for (const r of toAdd) {
                        try {
                            await member.roles.add(r.id);
                        } catch (err) {
                            failed.push({ role: r, reason: err.message });
                        }
                    }
                }
            }

            const added = toAdd.filter(r => !failed.some(f => f.role.id === r.id));

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

            const toRemove = [];
            const failed = [];

            for (const roleId of panelRoleIds) {
                const role = interaction.guild.roles.cache.get(roleId);
                if (!role || !member.roles.cache.has(roleId)) continue;
                if (!role.editable) {
                    failed.push({ role, reason: 'Hiérarchie Discord (rôle placé au-dessus du bot)' });
                    continue;
                }
                toRemove.push(role);
            }

            if (toRemove.length > 0) {
                try {
                    await member.roles.remove(toRemove.map(r => r.id));
                } catch (e) {
                    for (const r of toRemove) {
                        try {
                            await member.roles.remove(r.id);
                        } catch (err) {
                            failed.push({ role: r, reason: err.message });
                        }
                    }
                }
            }

            const removed = toRemove.filter(r => !failed.some(f => f.role.id === r.id));

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

        const toAdd = [];
        const toRemove = [];
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
                    toAdd.push(role);
                }
            } else if (!isSelected && hasRole) {
                if (!role.editable) {
                    failed.push({ role, action: 'retrait', reason: 'Hiérarchie Discord (rôle placé au-dessus du bot)' });
                } else {
                    toRemove.push(role);
                }
            }
        }

        if (toAdd.length > 0) {
            try {
                await member.roles.add(toAdd.map(r => r.id));
            } catch (e) {
                for (const r of toAdd) {
                    try { await member.roles.add(r.id); } catch (err) { failed.push({ role: r, action: 'ajout', reason: err.message }); }
                }
            }
        }

        if (toRemove.length > 0) {
            try {
                await member.roles.remove(toRemove.map(r => r.id));
            } catch (e) {
                for (const r of toRemove) {
                    try { await member.roles.remove(r.id); } catch (err) { failed.push({ role: r, action: 'retrait', reason: err.message }); }
                }
            }
        }

        const added = toAdd.filter(r => !failed.some(f => f.role.id === r.id && f.action === 'ajout'));
        const removed = toRemove.filter(r => !failed.some(f => f.role.id === r.id && f.action === 'retrait'));

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
