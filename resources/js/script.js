// 1. Data Definition (Single Source of Truth)
const cardData = [{
    title: "Sun Conure",
    image: "https://img.sanishtech.com/u/a7ad485e6d61e81a397bd88edd29a569.png",
},
{
    title: "African Grey",
    image: "https://img.sanishtech.com/u/c80d736a40fd81ac769888b4ad873066.png",
},
{
    title: "Verde Macaw",
    image: "https://img.sanishtech.com/u/19bc6276b3078cab7a8eb6072b1ae04f.png",
},
{
    title: "Buffon Macaw",
    image: "https://img.sanishtech.com/u/27644595b50ffb75fdd1141f0969830d.png",
},
];

// 2. Logic Initialization
const initMarquee = () => {
    const wrapper = document.getElementById('marquee-wrapper');
    const track = document.getElementById('marquee-track');
    const container = document.getElementById('cards-container');

    // Kita menduplikasi data array agar looping animasi translateX(-50%) berjalan mulus
    // Konsep: [A, B, C, D] -> [A, B, C, D, A, B, C, D]
    const renderData = [...cardData, ...cardData];

    // 3. Dynamic Duration Calculation
    // Sesuai logic React: cardData.length (asli) * 2500ms
    const duration = cardData.length * 2500;
    track.style.animationDuration = `${duration}ms`;

    // 4. Efficient DOM Injection
    // Menggunakan map dan join string jauh lebih cepat daripada createElement satu per satu
    container.innerHTML = renderData.map((card, index) => `
                <div class="w-56 mx-4 h-[20rem] relative group hover:scale-90 transition-all duration-300 shrink-0">
                    <img src="${card.image}" alt="card" class="w-full h-full object-cover" loading="lazy" />
                    <div class="flex items-center justify-center px-4 opacity-0 group-hover:opacity-100 transition-all duration-300 absolute bottom-0 backdrop-blur-md left-0 w-full h-full bg-black/20 rounded-lg">
                        <p class="text-white text-xl font-semibold text-center">${card.title}</p>
                    </div>
                </div>
            `).join('');

    // 5. Event Listeners untuk Pause/Play
    // Ini jauh lebih ringan daripada React State re-render
    wrapper.addEventListener('mouseenter', () => {
        track.style.animationPlayState = 'paused';
    });

    wrapper.addEventListener('mouseleave', () => {
        track.style.animationPlayState = 'running';
    });
};

// Execute saat DOM ready
document.addEventListener('DOMContentLoaded', initMarquee);