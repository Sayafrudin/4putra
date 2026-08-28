// Data Definition — gunakan data dari database jika tersedia, fallback ke hardcoded
const cardData = window.CarouselData && window.CarouselData.length > 0
    ? window.CarouselData
    : [
        { title: "Sun Conure", image: "/img/sunc.png" },
        { title: "African Grey", image: "/img/afgrey.png" },
        { title: "Verde Macaw", image: "/img/verde.png" },
        { title: "Buffon Macaw", image: "/img/buffon2.png" },
    ];

// Logic Initialization dengan optimasi performa
const initMarquee = () => {
    const wrapper = document.getElementById('marquee-wrapper');
    const track = document.getElementById('marquee-track');
    const container = document.getElementById('cards-container');

    if (!wrapper || !track || !container) return;

    // Build HTML sekali saja
    const renderData = [...cardData, ...cardData];
    const html = renderData.map((card) => `
        <div class="card-item w-56 mx-4 h-[20rem] relative group shrink-0 rounded-2xl overflow-hidden transition-transform duration-300 hover:scale-90">
            <img src="${card.image}" alt="${card.title}" class="w-full h-full object-cover" loading="lazy" decoding="async" />
            <div class="card-overlay flex items-center justify-center px-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 absolute bottom-0 left-0 w-full h-full bg-black/85">
                <p class="text-white text-xl font-semibold text-center">${card.title}</p>
            </div>
        </div>
    `).join('');

    container.innerHTML = html;

    // CSS Animation untuk marquee
    const duration = cardData.length * 2500;
    track.style.animationDuration = `${duration}ms`;

    // Gunakan event delegation untuk hover
    let isPaused = false;
    wrapper.addEventListener('mouseenter', () => {
        if (!isPaused) {
            track.style.animationPlayState = 'paused';
            isPaused = true;
        }
    }, { passive: true });

    wrapper.addEventListener('mouseleave', () => {
        if (isPaused) {
            track.style.animationPlayState = 'running';
            isPaused = false;
        }
    }, { passive: true });
};

// Jalankan saat DOM ready + re-render setelah navigasi Turbo Drive
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMarquee);
} else {
    initMarquee();
}
document.addEventListener('turbo:load', initMarquee);
