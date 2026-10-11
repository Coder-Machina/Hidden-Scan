import { animate, createTimeline, stagger } from 'animejs';
import html2canvas from 'html2canvas';

// Expose on window for Alpine.js and Blade templates
window.anime = animate;
window.createTimeline = createTimeline;
window.stagger = stagger;
window.html2canvas = html2canvas;

// Helper global function for animating counters with Anime.js
window.animateCounter = function(el, targetVal, duration = 1200) {
    if (!el) return;
    const obj = { val: 0 };
    animate(obj, {
        val: targetVal,
        round: 1,
        ease: 'outExpo',
        duration: duration,
        onUpdate: function() {
            el.innerHTML = Number(obj.val).toLocaleString();
        }
    });
};

// Helper for Murim Qi Breakthrough Aura Burst
window.triggerQiBreakthroughAnim = function(containerSelector) {
    const el = document.querySelector(containerSelector);
    if (!el) return;

    animate(containerSelector + ' .qi-pulse-ring', {
        scale: [0.8, 1.4],
        opacity: [0.9, 0],
        ease: 'outCubic',
        duration: 1600,
        loop: true
    });
};

// Helper for exporting Disciple Card to PNG image
window.exportDiscipleCard = async function(cardElementId, fileName = 'hidden-scan-carte-disciple.png') {
    const cardEl = document.getElementById(cardElementId);
    if (!cardEl || !window.html2canvas) return;

    try {
        const canvas = await window.html2canvas(cardEl, {
            backgroundColor: '#0a0d14',
            scale: 2, // Retinal high resolution
            useCORS: true,
            allowTaint: true,
        });

        const link = document.createElement('a');
        link.download = fileName;
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (err) {
        console.error('Erreur export carte disciple:', err);
    }
};
